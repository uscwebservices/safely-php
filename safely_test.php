<?php
/**
 * safely_test.php - varify that the safely functions can protected
 * against expected vunerabilities.
 */

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
	$results = makeValidationMap($_GET);
	$assert->ok($results, "Should get back an array for makeValidationMap()");
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

echo "Starting [" . $argv[0] . "]..." . PHP_EOL;

$assert->ok(function_exists("makeValidationMap"), "Should have a makeValidationMap function defined.");
$assert->ok(function_exists("safeGET"), "Should have a safeGET function defined.");
$assert->ok(function_exists("safePOST"), "Should have a safePOST function defined.");
$assert->ok(function_exists("safeSERVER"), "Should have a safeSERVER function defined.");

echo "\tTesting support functions: " . testSupportFunctions() . PHP_EOL;
echo "\tTesting get processing: " . testGETProcessing() . PHP_EOL;
echo "\tTesitng post processing: " . testPOSTProcessing() . PHP_EOL;
echo "\tTesitng server processing: " . testSERVERProcessing() . PHP_EOL;

///$assert->fail("safeGET(), safePOST(), safeSERVER() tests not implemented.");
echo "Success!" . PHP_EOL;
?>