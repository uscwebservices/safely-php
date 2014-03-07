<?php
/**
 * safely_test.php - varify that the safely functions can protected
 * against expected vunerabilities.
 */

if (php_sapi_name() !== "cli") {
	echo "Must be run from the command line." . PHP_EOL;
	exit(1);
}
error_reporting(E_ALL | E_STRICT);
@date_default_timezone_set(date_default_timezone_get());

require('assert.php');
$_POST = array();
$_SERVER = array();

include('safely.php');

function testSupportFunctions () {
	global $assert;
	
	// Test basic GET args
	$_GET["int"] = "1";
	$_GET["float"] = "2.1";
	$_GET["varname"] = "my_var_name";
	$_GET["html"] = "This is a <b>html</b>.";
	$_GET["text"] = "This is plain text.";
	$expected_map = array(
		"int" => "Integer",
		"float" => "Float",
		"varname" => "Varname",
		"html" => "HTML",
		"text" => "Text"
	);
	$results = defaultValidationMap($_GET);
	$assert->ok($results, "Should get back an array for defaultValidationMap()");
	foreach ($expected_map as $key => $value) {
		$assert->ok(isset($results[$key]), "Must have $key in results");
		$assert->equal($results[$key], $expected_map[$key], "results != expected for [$key], got " . print_r($results, true));
	}
	foreach ($results as $key => $value) {
		$assert->ok(isset($expected_map[$key]), "Unexpected $key in results" . print_r($results, true));
	}
	
	return "OK";
}


function testGETProcessing () {
	global $assert;

	// Test $_GET processing works
	$_GET = array(
		"one" => "1",
		"two" => "2.1",
		"three" => "my_var_name",
		"four" => "This is a string."
	);
	$expected_map = array(
		"one" => "1",
		"two" => "2.1",
		"three" => "my_var_name",
		"four" => "This is a string.",
	);
	
	$results = safeGET();
	
	$assert->ok($results, "Should have results from safeGET()");
	foreach ($expected_map as $key => $value) {
		$assert->ok(isset($results[$key]), "Must have $key in results");
		$assert->equal($results[$key], $expected_map[$key], "results != expected for [$key], got " . print_r($results, true));
	}
	foreach ($results as $key => $value) {
		$assert->ok(isset($expected_map[$key]), "Unexpected $key in results" . print_r($results, true));
	}
	
	return "OK";
}

function testPOSTProcessing () {
	global $assert;

	// Test $_POST processing works
	$_POST = array(
		"one" => "1",
		"two" => "2.1",
		"three" => "my_var_name",
		"four" => "This is a string."
	);
	$expected_map = array(
		"one" => "1",
		"two" => "2.1",
		"three" => "my_var_name",
		"four" => "This is a string.",
	);
	
	$results = safePOST();
	
	$assert->ok($results, "Should have results from safePOST()");
	foreach ($expected_map as $key => $value) {
		$assert->ok(isset($results[$key]), "Must have $key in results");
		$assert->equal($results[$key], $expected_map[$key], "results != expected for [$key], got " . print_r($results, true));
	}
	foreach ($results as $key => $value) {
		$assert->ok(isset($expected_map[$key]), "Unexpected $key in results" . print_r($results, true));
	}
	
	return "OK";
}

function testSERVERProcessing () {
	global $assert;

	// Test $_POST processing works
	$_SERVER = array(
		"one" => "1",
		"two" => "2.1",
		"three" => "my_var_name",
		"four" => "This is a string."
	);
	$expected_map = array(
		"one" => "1",
		"two" => "2.1",
		"three" => "my_var_name",
		"four" => "This is a string.",
	);
	
	$results = safeSERVER();
	
	$assert->ok($results, "Should have results from safeSERVER()");
	foreach ($expected_map as $key => $value) {
		$assert->ok(isset($results[$key]), "Must have $key in results");
		$assert->equal($results[$key], $expected_map[$key], "results != expected for [$key], got " . print_r($results, true));
	}
	foreach ($results as $key => $value) {
		$assert->ok(isset($expected_map[$key]), "Unexpected $key in results" . print_r($results, true));
	}
	
	return "OK";
}

function testSafeStrToTime() {
    global $assert;
    $s = "2014-01-01 00:00:00";
    try {
        $dt = safeStrToTime($s);
    } catch (Exception $exception) {
        $assert->fail("Shouldn't get this exception: " . $exception);
    }
    
    $s = "bogus date here.";
    $exception_thrown = false;
    try {
        $dt = safeStrToTime($s);
    } catch(Exception $exception) {
        $exception_thrown = true;
    }
    $assert->ok($exception_thrown, "Should have thrown an exception on converting $s");
    return "OK";
}

function testVarnameLists() {
    global $assert;
    $s = "one,two,three";
    $r = makeAs($s, "varname_list");
    $assert->equal($s, $r, "[$s] == [$r]");
    $e = 'one,two,three';
    $s = '$' . 'one,two,' . '$' . 'three';
    $r = makeAs($s, "varname_list");
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");
    
    return "OK";
}

function testPRCEExpressions() {
    global $assert;

    $re = "\([0-9][0-9][0-9]\)[0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]";
    $s = "(213)740-2925";
    $e = "(213)740-2925";
    $r = makeAs($s, $re);
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");

    $s = "(213)740-292592";
    $e = false;
    $r = makeAs($s, $re);
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");
    return "OK";
    
}

echo "Starting [" . $argv[0] . "]..." . PHP_EOL;

$assert->ok(function_exists("defaultValidationMap"), "Should have a defaultValidationMap function defined.");
$assert->ok(function_exists("safeGET"), "Should have a safeGET function defined.");
$assert->ok(function_exists("safePOST"), "Should have a safePOST function defined.");
$assert->ok(function_exists("safeSERVER"), "Should have a safeSERVER function defined.");

echo "\tTesting support functions: " . testSupportFunctions() . PHP_EOL;
echo "\tTesting get processing: " . testGETProcessing() . PHP_EOL;
echo "\tTesting post processing: " . testPOSTProcessing() . PHP_EOL;
echo "\tTesting server processing: " . testSERVERProcessing() . PHP_EOL;
echo "\tTesting safeStrToTime process: " . testSafeStrToTime() . PHP_EOL;
echo "\tTesting Varname Lists process: " . testVarnameLists() . PHP_EOL;
echo "\tTesting PRCE expressions process: " . testPRCEExpressions() . PHP_EOL;

///$assert->fail("safeGET(), safePOST(), safeSERVER() tests not implemented.");
echo "Success!" . PHP_EOL;
?>
