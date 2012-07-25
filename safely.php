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
	$is_varname = '/^([A-Z,a-z]|_)+$/';
	$has_tags = '/(<[A-Z,a-z]+|<\/[A-Z,a-z]+>)/';
	$validation_map = array();
	
	foreach ($obj as $key => $value) {
		if (preg_match($is_integer, $value) === 1) {
			$validation_map[$key] = "Integer";
		} else if (preg_match($is_float, $value) === 1) {
			$validation_map[$key] = "Float";
		} else if (preg_match($is_varname, $value) === 1) {
			$validation_map[$key] = "Varname";
		} else if (preg_match($has_tags, $value) === 1) {
			$validation_map[$key] = "HTML";
		} else {
			$validation_map[$key] = "Text";
		}
	}
	
	return $validation_map;
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
		return preg_replace('/![A-Z,a-z,_]+/', "", $value);
	case 'html':
		return mysql_real_escape_string($value);
	case 'text':
		return mysql_real_escape_string(strip_tags($value));
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
	
	if ($validation_map === NULL) {
		$validation_map = makeValidationMap($_POST, false);
	}
	forEach($validation_map as $key => $format) {
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
	
	if ($validation_map === NULL) {
		$validation_map = makeValidationMap($_SERVER, false);
	}
	forEach($validation_map as $key => $format) {
		if (isset($_SERVER[$key])) {
			$results[$key] = makeAs($_SERVER[$key], $format);
		}
	}
	return $results;
}



?>