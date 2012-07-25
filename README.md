safely-php
==========

A library for more securely handling of GET/POST objects in PHP

# Mockup of library idea

```JavaScript
/**
 * safely.php - utility methods for improving code quality and 
 * safety in Web Services PHP based projects.
 *
 * @author R. S. Doiel, <rsdoiel@usc.edu>
 * copyright (c) 2012 all rights reserved
 * University of Southern California
 */

// A validation map is a key/value pair where the
// key matches a key in $_POST or $_GET and the
// value in the validation map is course level
// type of sanitation you want. List of validation
// types supported:
// * String - mysql_real_escape_string(), tags allowed
// * Text - mysql_real_escape_string(), tags stripped
// * Integer - validate with is_int() returned integer or false
// * Float - validation as a fractional decimal number, return as float or false
// * Varname - alpha numeric plus underscore only

/* Example Validation Map
  $validation_map = array(
		"id" => "Integer",
		"callback" => "Varname",
		"search" => "Text"
	);
*/

// makeValidationMap will try to guess the types
// of data being passed and generate an appropraite
// validation map. In most cases this means input
// will be treated as a Text where any tags are
// stripped
function makeValidationMap($obj, $use_urldecode == false) {
	$validation_map = array();
	foreach ($obj as $key => $value) {
		if ($use_urldecode === true) {
			$value = urldecode($value);
		}
		// FIXME: Now guess the appropriate type
		if (is_int($value) {
		} else if (is_float($value)) {
		}
		$validation_map[$key] = "Text";
	}
	return $validation_map;
}


// safeGET returns a sanitized version of $_GET
// based on the validation_map object supplied
// If a $_GET key is not listed in the validation
// map then it is not returned as "safe"
/* Example using safeGET(),safePOST() to retro fit legacy code.
	$_GET = safeGET();
	$_POST = safePOST();
 */
function safeGET ($validiation_map = NULL) {
	if ($validation_map === NULL) {
		$validation_map = makeValidationMap($_GET, true);
	}
	// Something went wrong so return false.
	return false;
}

function safePOST ($validation_map) {
	if ($validation_map === NULL) {
		$validation_map = makeValidationMap($_POST, false);
	}
}

function safeSERVER ($validation_map) {
	if ($validation_map === NULL) {
		$validation_map = makeValidationMap($_SERVER, true);
	}
}
```
