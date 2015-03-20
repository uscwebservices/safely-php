<?php
/**
 * safely.php - Library functions to retrofit legacy PHP code to more
 * safely handle $_GET, $_POST and $_SERVER to prevent common exploits
 * like XSS and SQL injection.
 *
 * @author R. S. Doiel, <rsdoiel@usc.edu>
 * copyright (c) 2012 all rights reserved
 * University of Southern California
 */
if (defined("SAFELY_ALLOW_UNSAFE") === false) {
    define("SAFELY_ALLOW_UNSAFE", false);
}
if (defined("SAFELY_ALLOWED_HTML") === false) {
    // List of safe elements derived from https://developer.mozilla.org/en-US/docs/Web/HTML/Element
    $safe_element_list = '<a><abbr><acronym><address><area><article><aside><audio><b><base><basefont><bdi><bdo><bgsound><big><blink><blockquote><body><br><button><canvas><caption><center><cite><code><col><colgroup><content><data><datalist><dd><decorator><del><details><dfn><dialog><dir><div><dl><dt><element><em><embed><fieldset><figcaption><figure><font><footer><form><frame><frameset><h1><h2><h3><h4><h5><h6><head><header><hgroup><hr><html><i><iframe><img><input><ins><isindex><kbd><keygen><label><legend><li><link><listing><main><map><mark><marquee><menu><menuitem><meta><meter><nav><nobr><noframes><noscript><object><ol><optgroup><option><output><p><param><picture><plaintext><pre><progress><q><rp><rt><ruby><s><samp><section><select><shadow><small><source><spacer><span><strike><strong><style><sub><summary><sup><table><tbody><td><template><textarea><tfoot><th><thead><time><title><tr><track><tt><u><ul><var><video><wbr><xmp>';

    define("SAFELY_ALLOWED_HTML", $safe_element_list); 
}

/**
 * utf2html - convert UTF-8 characters to appropriate HTML entities.
 * Take from the comments at http://us1.php.net/manual/en/function.mb-encode-numericentity.php
 * by dan@boxuk.com.
 * @param $utf2html_string - the string to convert.
 * @return the converted string.
 */
function utf2html ($utf2html_string, $is_hex = false) {
    $f = 0xffff;
    $convmap = array(
    /* <!ENTITY % HTMLlat1 PUBLIC "-//W3C//ENTITIES Latin 1//EN//HTML">
    %HTMLlat1; */
    160,  255, 0, $f,
    /* <!ENTITY % HTMLsymbol PUBLIC "-//W3C//ENTITIES Symbols//EN//HTML">
    %HTMLsymbol; */
    402,  402, 0, $f,  913,  929, 0, $f,  931,  937, 0, $f,
    945,  969, 0, $f,  977,  978, 0, $f,  982,  982, 0, $f,
    8226, 8226, 0, $f, 8230, 8230, 0, $f, 8242, 8243, 0, $f,
    8254, 8254, 0, $f, 8260, 8260, 0, $f, 8465, 8465, 0, $f,
    8472, 8472, 0, $f, 8476, 8476, 0, $f, 8482, 8482, 0, $f,
    8501, 8501, 0, $f, 8592, 8596, 0, $f, 8629, 8629, 0, $f,
    8656, 8660, 0, $f, 8704, 8704, 0, $f, 8706, 8707, 0, $f,
    8709, 8709, 0, $f, 8711, 8713, 0, $f, 8715, 8715, 0, $f,
    8719, 8719, 0, $f, 8721, 8722, 0, $f, 8727, 8727, 0, $f,
    8730, 8730, 0, $f, 8733, 8734, 0, $f, 8736, 8736, 0, $f,
    8743, 8747, 0, $f, 8756, 8756, 0, $f, 8764, 8764, 0, $f,
    8773, 8773, 0, $f, 8776, 8776, 0, $f, 8800, 8801, 0, $f,
    8804, 8805, 0, $f, 8834, 8836, 0, $f, 8838, 8839, 0, $f,
    8853, 8853, 0, $f, 8855, 8855, 0, $f, 8869, 8869, 0, $f,
    8901, 8901, 0, $f, 8968, 8971, 0, $f, 9001, 9002, 0, $f,
    9674, 9674, 0, $f, 9824, 9824, 0, $f, 9827, 9827, 0, $f,
    9829, 9830, 0, $f,
    /* <!ENTITY % HTMLspecial PUBLIC "-//W3C//ENTITIES Special//EN//HTML">
    %HTMLspecial; */
    /* These ones are excluded to enable HTML: 34, 38, 60, 62 */
    338,  339, 0, $f,  352,  353, 0, $f,  376,  376, 0, $f,
    710,  710, 0, $f,  732,  732, 0, $f, 8194, 8195, 0, $f,
    8201, 8201, 0, $f, 8204, 8207, 0, $f, 8211, 8212, 0, $f,
    8216, 8218, 0, $f, 8218, 8218, 0, $f, 8220, 8222, 0, $f,
    8224, 8225, 0, $f, 8240, 8240, 0, $f, 8249, 8250, 0, $f,
    8364, 8364, 0, $f, $is_hex);
    // FIXME: need to strip \u0080, \u009c, \u009d ...
    return mb_encode_numericentity($utf2html_string, $convmap, "UTF-8");
}

/**
 * safeStrToTime - process a strtotime but THROW an exception of parse is bad.
 * @param $s - string to parse
 * @param $offset - a date object to parse relative to.
 * @return a time object or throw an exception if parse fails.
 */
function safeStrToTime ($s, $offset = false) {
    if ($offset === false) {
        $time = strtotime($s);
    } else {
        $time = strtotime($s, $offset);
    }
    if ($time === false || $time === -1) {
        throw new Exception ("Can't parse date: $s");
    }
    return $time;
}

/**
 * isValidUrl - check to see if string parses into expected parts of a URL.
 * @param $s - string to check
 * @param $protocols - the list of accepted protocols, defaults to http, https, mailto, tel, sftp, ftp
 * @return true if a URL false otherwise
 */
function isValidUrl($s, $protocols = null) {
    if (filter_var($s, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    if ($protocols === null) {
        $protocols = array(
            'http', 'https', 'ftp', 'sftp', 'mailto', 'tel'
        );
    }
    $parts = parse_url($s);
    if (isset($parts['scheme']) && in_array($parts['scheme'], $protocols) &&
        isset($parts['host']) && trim($parts['host']) !== "") {
        return true;
    }
    return false;
}

/**
 * isValidFilename - check to see if the string conforms to a valid filename (alaphnumeric plus .,_,-)
 * @param $s - string to check
 * @return true if valid filename false otherwise.
 */
function isValidFilename($s) {
    if (!preg_match('/^(?:[a-z0-9_-]|\/|\.(?!\.))+$/iD', $s) || mb_strlen($s, "UTF-8") >= 250) {
        return false;
    }
    return true;
}

/**
 * isValidEmail - simple check for probably valid email address.
 * @param $s - the string to check
 * @return the validated string or false if it appears not to be an email address.
 */
function isValidEmail($s) {
    if (filter_var($s, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }
    return true;
}

/**
 * defaultValidationMap - given an example $obj, calculate
 * a viable validation map to safely use with other requests.
 * Note this is a restricted map since auto-detection is not precise.
 * E.g. If you want to validate with RegExp then you need to manually 
 * create your map.
 * @param $obj - e.g. $_GET, $_POST or $_SERVER
 * @param $do_urldecode - flag to trigger urldecode of values before
 * analysize the content.
 * @return a validation map array
 */
function defaultValidationMap ($obj, $do_urldecode = false) {
    $is_varname = '/^([A-Z,a-z]|_|[0-9])+$/';
    $has_tags = '/(<[A-Z,a-z]+|<\/[A-Z,a-z]+>)/';
    $validation_map = array();
    
    foreach ($obj as $key => $value) {
        if (isset($value)) {
            if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
                $validation_map[$key] = "Integer";
            } else if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
                $validation_map[$key] = "Float";
            } else if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null) {
                $validation_map[$key] = "Boolean";
            } else if (gettype($value) === "string" && preg_match($is_varname, $value) === 1) {
                $validation_map[$key] = "Varname";
            } else if (gettype($value) === "string" && preg_match($has_tags, $value) === 1) {
                $validation_map[$key] = "HTML";
            } else if (isValidUrl($value) === true) { 
                $validation_map[$key] = "Url";
            } else if (isValidEmail($value) === true) {
                $validation_map[$key] = "Email";
            } else {
                $validation_map[$key] = "Text";
            }
        }
    }
    return $validation_map;
}

/**
 * strip_attributes - remove attributes from HTML elements except for href, src, title
 * Based on stackexchange discussions at 
 * http://stackoverflow.com/questions/770219/how-can-i-remove-attributes-from-an-html-tag
 *
 * @param $s - the HTML string to be cleaned
 * @param $allowedattr - an array of the allowed attributes (e.g. href, src, title, alt)
 * @return HTML string with only allowed attributes.
 */
function strip_attributes($s, $allowedattr = array("href", "src", "title", "alt")) {
    if (preg_match_all("/<[^>]*\\s([^>]*)\\/*>/msiU", $s, $res, PREG_SET_ORDER)) {
       foreach ($res as $r) {
           $tag = $r[0];
           $attrs = array();
           preg_match_all("/\\s.*=(['\"]).*\\1/msiU", " " . $r[1], $split, PREG_SET_ORDER);
           foreach ($split as $spl) {
               $attrs[] = $spl[0];
           }
           $newattrs = array();
           foreach ($attrs as $a) {
               $tmp = explode("=", $a);
               if (trim($a) !== "" && isset($tmp[0]) && isset($tmp[1]) && trim($tmp[0]) !== "" && in_array(strtolower(trim($tmp[0])), $allowedattr)) {
                   // Only allowed attributes should get passed through
                   // but href must not contain a javascript protocol!
                   if (strpos(strtolower($tmp[1]), "javascript:") === false) {
                       // attribute should not have JS injection.
                       $newattrs[] = trim($a);
                   }
               }
           }
           $attrs = implode(" ", $newattrs);
           $rpl = str_replace($r[1], $attrs, $tag);
           $s = str_replace($tag, $rpl, $s);
       }
  }
  return $s;
}

/**
 * fix_html_quotes - Scan a string and when you find quotes in the CDATA convert
 * to appropriate entity.
 * @param $s - the string to check and convert.
 * @return string with quotes appropriately converted.
 */
function fix_html_quotes($s) {
    $a = str_split($s);
    $inCData = true;
    for ($i = 0; $i < count($a); $i += 1) {
        if ($a[$i] == '>') {
            $inCData = true;
        } else if ($a[$i] === '<') {
            $inCData = false;
        } else if ($inCData === true) {
            $a[$i] = str_replace(array('"', "'"), array('&quot;', '&apos;'), $a[$i]);
            //$a[$i] = htmlentities($a[$i], ENT_QUOTES | ENT_HTML5);
        }
    }
    return  implode('', $a);
}

/**
 * replace non-ascii characters with hex code
 * this replace mysql_real_escape_string because this requires a mysql
 * connection to exist.
 */
function escape($value) {
    // Handle multi-byte issues by converting to UTF-8
    // if needed.
    $from_encoding = mb_detect_encoding($value);
    if ($from_encoding === false) {
        die("character encoding detection failed!");
    } else if ($from_encoding !== "UTF-8") {
        $value = mb_convert_encoding($value, "UTF-8", $from_encoding);
    }

    $search  = array( "\\",   "\x00", "\n",  "\r",  "'",  '"',  "\x1a" );
    $replace = array( "\\\\", "\\0",  "\\n", "\\r", "\'", '\"', "\\Z" );

    return str_replace($search, $replace, $value);
}

/**
 * makeAs takes a value and renders it using the format
 * passed (e.g. Integer, Float, Html, Varname, Text)
 * @param $value - the value to be processed
 * @param $format - the format to render (i.e. integer, float, varname, 
 * varname_list, html, text and PRCE friendly regular expressions)
 * @param $verbose - error log the result of makeAs for regular expression.
 * @return a safe version of value in the format requested or false if a problem.
 */
function makeAs ($value, $format, $verbose = false) {
    switch (strtolower($format)) {
    case 'array_text':
        if (!is_array($value)) {
            return false;
        }
        $a = array();
        foreach($value as $i => $val) {
           $a[] = escape(strip_tags($val));
        }
        return $a;
    case 'array_integers':
        if (!is_array($value)) {
            return false;
        }
        $a = array();
        foreach($value as $i => $val) {
            if (is_numeric($val) && intval($val) !== false) {
               $a[] = intval($val); 
            }
        }
        return $a;
    case 'integer':
        $i = intval($value);
        if ("$i" == $value) {
            return $i;
        }
        break;
    case 'float':
        $f = floatval($value);
        if ("$f" == $value) {
            return $f;
        }
        break;
    case 'boolean':
        if ($value === 'true' || 
                $value === '1') {
            return true;
        }
        return false;
    case 'varname_dash':
        if (is_string($value)) {
            preg_match_all('/\w|[0-9]|_|-/', $value, $s);
            return implode('', $s[0]);
        }
        return false;
    case 'varname':
        if (is_string($value)) {
            preg_match_all('/\w|[0-9]|_/', $value, $s);
            return implode('', $s[0]);
        }
        return false;
    case 'varname_list':
        $parts = explode(',', $value);
        for ($i = 0; $i < count($parts); $i += 1) {
            // NOTE on replaced line: $parts[$i] = preg_replace('/\W/u', '', $parts[$i]);
            // PRCE was not compiled with UTF-8 support on web-app.usc.edu. 2015-02-19 RSD
           $parts[$i] = preg_replace('/\W/', '', $parts[$i]);
        }
        return implode(',', $parts);
    case 'html':
        if (gettype($value) === "string") {
           return escape(fix_html_quotes(strip_attributes(strip_tags(utf2html($value), SAFELY_ALLOWED_HTML))));
        }
        return false;
    case 'text':
        if(gettype($value) === "string") {
            return escape(strip_tags($value), false);
        }
        return false;
    case 'url':
        if (isValidUrl($value) === true) {
            return $value;
        }
        // Check to see if we're just missing protocol and try http://.
        if (strpos($value, '://') === false  && 
                isValidUrl('http://' . $value) === true) {
            return 'http://' . $value;
        }
        return false;
    case 'email':
        if (isValidEmail($value) === true) {
            return $value;
        }
        return false;
    case 'filename':
        if (isValidFilename($value) === true) {
            return $value;
        }
        return false;
    }
    // We haven't found one of our explicit formats so...
    $preg_result = preg_match(">" . '^' . 
        str_replace(">", "\>", $format) . '$' . ">",
        $value);

    if ($verbose === true) {
        error_log("value, format and preg_math result: $value $format -> $preg_result");
    }
    if ($preg_result === 1) {
        return $value;
    }
    return false;
}

/**
 * safeGET - if necessary generate a default validation object and
 * process the global $_GET returning a sanitized version.
 * @param $validation_map - You should supply an explicit validation map. Will allow NULL
 * if SAFELY_ALLOW_UNSAFE defined with true.
 * @param $verbose - log regexp makeAs results. (default is false)
 * @return the sanitized version of $_GET.
 */
function safeGET ($validation_map = NULL, $verbose = false) {
    global $_GET;
    $results = array();

    if (SAFELY_ALLOW_UNSAFE === true && $validation_map === NULL) {
        // We support limited auto-detect types otherwise App
        // Code needs to supply a validation map.
        $validation_map = defaultValidationMap($_GET, true);
    }
    foreach($validation_map as $key => $format) {
        // Since RESTful style allows dashes in the URLs we should support
        // that in GET args.
        $key = makeAs($key, "varname_dash", $verbose);
        if (isset($_GET[$key])) {
            $results[$key] = makeAs($_GET[$key], $format, $verbose);
        }
    }
    return $results;
}

/**
 * safePOST - if necessary generate a default validation object and
 * process the global $_POST returning a sanitized version.
 * @param $validation_map - You should supply an explicit validation map. Will allow NULL
 * if SAFELY_ALLOW_UNSAFE defined with true.
 * @return false if their is a problem otherwise the sanitized verion of
 * $_POST.
 * @param $verbose - log regexp makeAs results. (default is false)
 * @return the sanitized version of $_POST
 */
function safePOST ($validation_map = NULL, $verbose = false) {
    global $_POST;
    $results = array();
    
    if (SAFELY_ALLOW_UNSAFE === true && $validation_map === NULL) {
        $validation_map = defaultValidationMap($_POST, false);
    }
    foreach($validation_map as $key => $format) {
        $key = makeAs($key, "varname", $verbose);
        if (isset($_POST[$key])) {
            $results[$key] = makeAs($_POST[$key], $format, $verbose);
        }
    }
    return $results;
}

/**
 * safeSERVER - if necessary generate a default validation object and
 * process the global $_SERVER returning a sanitized version.
 * @param $validation_map - You should supply an explicit validation map. Will allow NULL
 * if SAFELY_ALLOW_UNSAFE defined with true.
 * @return false if their is a problem otherwise the sanitized verion of
 * $_SERVER.
 * @param $verbose - log regexp makeAs results. (default is false)
 * @return the sanitized version of $_SERVER
 */
function safeSERVER ($validation_map = NULL, $verbose = false) {
    global $_SERVER;
    $results = array();
    
    if ($validation_map === NULL) {
        $validation_map = defaultValidationMap($_SERVER, false);
    }
    foreach($validation_map as $key => $format) {
        $key = makeAs($key, "varname", $verbose);
        if (isset($_SERVER[$key])) {
            $results[$key] = makeAs($_SERVER[$key], $format, $verbose);
        }
    }
    return $results;
}

/**
 * safeJSON - validate a JSON response against expected data types.
 * @param $json_string (required)
 * @param $validation_map (required) - most be provided. Undefined fields are not passed.
 * a validate associative array.
 * @param $verbose (optional) - log regexp makeAs results. (default is false)
 * @return the santized version of $json_string.
 */
function safeJSON($json_string, $validation_map, $verbose = false) {
    $obj = json_decode($json_string, true);
    $results = array();
    
    foreach($validation_map as $key => $format) {
        $key = makeAs($key, "varname", $verbose);
        if (isset($obj[$key])) {
            $results[$key] = makeAs($obj[$key], $format, $verbose);
        }
    }
    return $results;
}

/**
 * safeFilename() - validate the string against being a safe filename like when you use $_FILE.
 * @param $filename (required) name to be validated
 * @param $verbose (optoinal) - log regexp makeAs results. (default is false)
 * @return the santized string or false
 */
function safeFilename($filename, $verbose = false) {
    return makeAs($filename, "filename", $verbose);
}
?>
