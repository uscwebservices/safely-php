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
            } else if (preg_match($is_varname, "$value") === 1) {
                $validation_map[$key] = "Varname";
            } else if (preg_match($has_tags, "$value") === 1) {
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

    $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
    $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

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
           $parts[$i] = preg_replace('/\W/u', '', $parts[$i]);
        }
        return implode(',', $parts);
    case 'html':
        return escape($value);
    case 'text':
        return escape(strip_tags($value));
    case 'url':
        if (isValidUrl($value) === true) {
            return $value;
        }
    case 'email':
        if (isValidEmail($value) === true) {
            return $value;
        }
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
 * @param $validation_map - You can supply an explicit validation map.
 * @param $verbose - log regexp makeAs results. (default is false)
 * @return the sanitized version of $_GET.
 */
function safeGET ($validation_map = NULL, $verbose = false) {
    global $_GET;
    $results = array();

    if ($validation_map === NULL) {
        // We support limited auto-detect types otherwise App
        // Code needs to supply a validation map.
        $validation_map = defaultValidationMap($_GET, true);
    }
    forEach($validation_map as $key => $format) {
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
 * @param $validation_map - You can supply an explicit validation map.
 * @return false if their is a problem otherwise the sanitized verion of
 * $_POST.
 * @param $verbose - log regexp makeAs results. (default is false)
 * @return the sanitized version of $_POST
 */
function safePOST ($validation_map = NULL, $verbose = false) {
    global $_POST;
    $results = array();
    
    if ($validation_map === NULL) {
        $validation_map = defaultValidationMap($_POST, false);
    }
    forEach($validation_map as $key => $format) {
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
 * @param $validation_map - You can supply an explicit validation map.
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
    forEach($validation_map as $key => $format) {
        $key = makeAs($key, "varname", $verbose);
        if (isset($_SERVER[$key])) {
            $results[$key] = makeAs($_SERVER[$key], $format, $verbose);
        }
    }
    return $results;
}
?>
