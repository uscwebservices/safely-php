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
 * makeValidationMap - given an example $obj, calculate
 * a viable validation map to safely use with other requests.
 * @param $obj - e.g. $_GET, $_POST or $_SERVER
 * @param $do_urldecode - flag to trigger urldecode of values before
 * analysize the content.
 * @return a validation map array
 */
function makeValidationMap ($obj, $do_urldecode = false) {
	$is_integer = '/^[0-9]+$/';
	$is_float = '/^[0-9]+\.[0-9]+$/';
	$is_varname = '/^([A-Z,a-z]|_|[0-9])+$/';
	$has_tags = '/(<[A-Z,a-z]+|<\/[A-Z,a-z]+>)/';
	$validation_map = array();
	
	foreach ($obj as $key => $value) {
        if (isset($value)) {
		    if (preg_match($is_integer, "$value") === 1) {
			    $validation_map[$key] = "Integer";
		    } else if (preg_match($is_float, "$value") === 1) {
			    $validation_map[$key] = "Float";
		    } else if (preg_match($is_varname, "$value") === 1) {
			    $validation_map[$key] = "Varname";
		    } else if (preg_match($has_tags, "$value") === 1) {
			    $validation_map[$key] = "HTML";
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
 * @param $format - the format to render (i.e. integer, float, varname, html, text)
 * @return a safe version of value in the format requested or false if a problem.
 */
function makeAs ($value, $format) {
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
	case 'varname':
		return preg_replace('/\W/u', "", $value);
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
	}
	return false;
}

/**
 * safeGET - if necessary generate a default validation object and
 * process the global $_GET returning a sanitized version.
 * @param $validation_map - You can supply an explicit validation map.
 * @return the sanitized verion of $_GET.
 */
function safeGET ($validation_map = NULL) {
	global $_GET;
	$results = array();

	if ($validation_map === NULL) {
		$validation_map = makeValidationMap($_GET, true);
	}
	forEach($validation_map as $key => $format) {
		$key = makeAs($key, "varname");
		if (isset($_GET[$key])) {
			$results[$key] = makeAs($_GET[$key], $format);
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
 */
function safePOST ($validation_map = NULL) {
	global $_POST;
	$results = array();
	
	if ($validation_map === NULL) {
		$validation_map = makeValidationMap($_POST, false);
	}
	forEach($validation_map as $key => $format) {
		$key = makeAs($key, "varname");
		if (isset($_POST[$key])) {
			$results[$key] = makeAs($_POST[$key], $format);
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
 */
function safeSERVER ($validation_map = NULL) {
	global $_SERVER;
	$results = array();
	
	if ($validation_map === NULL) {
		$validation_map = makeValidationMap($_SERVER, false);
	}
	forEach($validation_map as $key => $format) {
		$key = makeAs($key, "varname");
		if (isset($_SERVER[$key])) {
			$results[$key] = makeAs($_SERVER[$key], $format);
		}
	}
	return $results;
}



?>
