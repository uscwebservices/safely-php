<?php
/**
 * api.php - a demonstrating the useful of safely.php and restful.php to produce
 * a safe and simple RESTful JSON service.
 */
require_once("../../safely.php");
require_once("../../restful.php");

// We will use these constants to keep code readable and immutable.
define("HELLO_WORLD_ROUTE", "/");// This is a basic default route.
define("HELLO_NAME_ROUTE", "/first-name/[A-Z][a-z]+");// This is the name route

/**
 * safely.php - provides simple validation of $_SERVER, $_GET, $_POST PHP globals.
 */
//
// This section will sanitize our inputs which in this case is PATH_INFO variable
// provided by the PHP Global Object $_SERVER. This section uses safely.php for processing.
//

// Here's the map we're interested, only the PATH_INFO variable with the following
// PRCE RegExp.
$valid_server_map = array(
    "PATH_INFO" => "(" . HELLO_WORLD_ROUTE . "|" . HELLO_NAME_ROUTE . ")"
);

// Now get a safe version of the $_SERVER variable
$server = safeSERVER($valid_server_map, true);

// Now we can check to see if we have a safe PATH_INFO
$path_info = $server['PATH_INFO'];
if ($path_info === false) {
    // We have a bad reqeust if path_info is false. Let the browser know
    header("Bad request", true, 400);
    // Exit the script here to avoid risking anything leaking out.
    echo '{"status": "error", "message": "Invalid name"}';
    exit(1);
}


/**
 * restful.php - provides a small library for assembling and scaling URL driven
 * RESTful API or websites.
 */

// We have something that appears safe.  Now let's setup and process our routes.
// This is where restful.php comes in.

// Step. 1 define your route handlers. It is easiest to use the PHP 5.4 syntax for
// functions we want to pass as a variable.
$helloWorldHandler = function ($path, $options) {
    // We always return the same message for this route.
    $headers = array();
    $headers[] = fmtHeader("OK", true, 200);
    $headers[] = fmtHeader("Content-Type: application/json");
    $content = '{"message": "Hello World!"}';
    // We assemble a standard response using the fmtResponse() function.
    return fmtResponse($headers, $content);
};

$helloNameHandler = function ($path, $options) {
    // This time we need to parse the path for the name.
    $name = str_replace("/first-name/", "", $path);
    $headers = array();
    $headers[] = fmtHeader("Content-Type: application/json", true);
    if (!$name) {
       error_log("ERROR: name not valid"); 
       $headers[] = fmtHeader("Bad Request", true, 400);
       $content = '{"status": "error", "message": "Invalid name"}';
    } else {
        $headers[] = fmtHeader("OK", true, 200);
        $content = json_encode(array("message" => $name), true);
    }
    return fmtResponse($headers, $content);
};

// Step. 2 define your routes. This associates a URL with a specific handler.. 

// $routes will hold a table of routes along with their handler association and any options supplied.
$routes = array();

// $options can be used to pass things into the handler. 
// Typically this is configuration related values. In our example it is empty.
$options = array();

$routes[] = fmtRoute("^" . HELLO_WORLD_ROUTE . "$", $helloWorldHandler, $options);
$routes[] = fmtRoute("^". HELLO_NAME_ROUTE . "$", $helloNameHandler, $options);


// Now that we have defined how to safely process the PATH_INFO value and
// how to handle the two routes supported by the appication we need to wire up
// our actual API code.
if (isset($server['PATH_INFO'])) {
    // Render takes care of sending headers and content back to the browser.
    renderRoute(executeRoute($server['PATH_INFO'], $routes));
} else {
    // Otherwise run our default route.
    renderRoute(executeRoute("/", $routes));
}
?>
