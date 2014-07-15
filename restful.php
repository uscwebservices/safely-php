<?php
/**
 * restful.php - create a simple functional style library for building RESTful http services.
 */

/**
 * mergepath - A simple function to implode an array into a valid
 * file system path. 
 *
 * @param $path_parts - an associative array of values which will be
 * used to assemble a new path.
 * @param $delim - (optional) the delimiter used to separate parts of a path.
 * Defaults to '/'.
 * @return a string of the path or false if a problem occurs 
 */
function mergepath ($path_parts, $delim = '/') {
  $new_path = '';
  
  foreach ($path_parts as $part) {
    if (substr(trim($part),0,1) == $delim) {
      $new_path .= trim($part);
    } else {
      $new_path .= $delim . trim($part);
    }
  }
  return $new_path;
}


/**
 * fmtHeader() - assemble a header for eventual processing by a renderRoute. Typically
 * you append the results of this function to a list of headers.
 * @param $message - the text, e.g. "Content-Type: text/plain"
 * @param $overwrite - set the replace status for processing with PHP's header() function.
 * @param $status_code - this is a HTTP 1.1 status code (e.g. 404, 503, 302)
 * @returns an array of one to three elements formatted for use by renderRoutes
 * Example:
 *   $myheaders = array();
 *   $myheaders[] = fmtHeader("", true, 200);
 *   $myheaders[] = fmtHeader("Content-Type: text/plain");
 */
function fmtHeader($message, $overwrite = null, $status_code = null) {
    $h = array($message);
    if ($overwrite !== null) {
        $h[] = $overwrite;
    }
    if ($status_code !== null) {
        $h[] = $status_code;
    }
    return $h;
}

/**
 * fmtReponse - assemble an array of headers and content into a full HTTP response.
 * @param $headers - an array of headers formatted with fmtHeader()
 * @param $content - the content to be sent back to the browser (e.g. HTML, JSON, etc);
 */
function fmtResponse($headers, $content) {
    return array(
        // An array of parameters to call with the PHP  headers function
        "HTTP_HEADER" => $headers,
        // The content to send to the web browser after headers are finished.
        "HTTP_CONTENT" => $content
    );
}

/**
 * defaultRoute - this basically sets up a 404 as this is the default route for a request
 * that is not valid. It is also an example of the function signature expected to by executeRoute();
 * @param $path_info - the path info value returned by $_SERVER['PATH_INFO']
 * @param $options - optional information used for processing the route (e.g. get args, validation)
 * @param $db - optional default open Db object or null (defaults to null)
 * @return formatted response of header and content.
 */
function defaultRoute($path_info, $options, $db = null) {
    $h = array();
    $h[] = fmtHeader("File Not Found", true, 404);
    $h[] = fmtHeader("Content-Type: text/plain", true);
    // If this was a user defined route handler they would use things like safeGET(), safePOST(), etc. to
    // process the request and validate things.
    return fmtResponse($h, "File Not Found");
}

/**
 * fmtRoute - build a simple Associative array which can be used to process routes
 * when calling a list of routes in executeRoute()
 * @param $path_reg_exp - a PRCE reg exp representing the target path
 * @param $callback - the callback to execute if route matches
 * @param $options - an associative array of options to pass to the callback function (e.g. validation rules)
 * @return an associative array describing the route to be processed.
 */
function fmtRoute($path_reg_exp, $callback, $options = null) {
    // Escape any | pipe symbols in the route so we have a valid pattern to pass to PCRE
    return array("path_reg_exp" => '#' . str_replace('#', '\#', $path_reg_exp) . '#',
                 "callback" => $callback,
                 "options" => $options);
}

/**
 * executeRoute - using a array of routes scan $_SERVER['PATH_INFO'] and execute
 * appropriate callbacks.
 * @param $path_info -  the urlencoded path to match, typically from $_SERVER['PATH_INFO'])
 * @param $routes - an array of routes constructed with fmtRoute().
 * @param $db - an optional open Db object or null, defaults to null
 * @return a PHP Associative array suitable for processing with renderRoute();
 */
function executeRoute($path_info, $routes, $db = null) {
    $path = urldecode($path_info);
    for ($i = 0; $i < count($routes); $i += 1) {
        if (preg_match($routes[$i]["path_reg_exp"], $path) === 1 ) {
            // We have a match so make the callback passing it any 
            // options defined in the route.
            return $routes[$i]["callback"]($path, $routes[$i]["options"], $db);
        }
    }
    // We really didn't find it so the default case is 404.
    return defaultRoute($path, null, $db);
}

/**
 * renderHeaders - emmit the HTTP header from a list of Headers
 * @param $headers - an array of headers like those produced by fmtReponse()
 * @return true
 * @sideeffect - headers are sent to the web server
 */
function renderHeaders($headers) {
    foreach($headers as $header_params) {
        if (count($header_params) === 1) {
            header($header_params[0]);
        } else if (count($header_params) === 2) {
            header($header_params[0], $header_params[1]);
        } else if (count($header_params) === 3) {
            header($header_params[0], $header_params[1], $header_params[2]);
        }
    }
    return true;
}

/**
 * renderRoute - emmit headers and contents described by the results of executeRoute()
 * renderRoute will calculate the Expires, Cache-Control and Etag for you. 
 * It optionally can gzip the contents as well with the $user_gzip option.
 *
 * @param $route_results
 * @param $use_gzip - if true gzip the output, defaults to false
 * @sideeffects emits headers and sends content to stdout
 * @return always true
 */
function renderRoute($route_results, $use_gzip = false) {
    if (isset($route_results['HTTP_HEADER'])) {
        $headers = $route_results['HTTP_HEADER'];
    } else {
        $headers = array();
    }

    if (isset($route_results['HTTP_CONTENT'])) {
        $content = $route_results['HTTP_CONTENT'];

        // Set the expiration, cache-content and etag headers
        $headers[] = fmtHeader('Expires: ' . date(DATE_RFC1123, strtotime("+1 year"))); 
        $headers[] = fmtHeader('Cache-Control: max-age=36000, s-maxage=360000'); 
        $headers[] = fmtHeader('ETag: ' . md5($content));
    }

    if ($use_gzip === true) {
        // Add the gzip compression
        $gzipoutput = gzencode($route_results['HTTP_CONTENT'], 6); 
        $headers[] = fmtHeader('Content-Encoding: gzip');
        $headers[] = fmtHeader('Content-Length: ' . strlen($gzipoutput));
        renderHeaders($headers);
        echo $gzipoutput;
        return true;
    }

    // Output headers
    renderHeaders($headers);
    // Out put content.
    echo $route_results["HTTP_CONTENT"];
    return true;
}
?>
