safely-php
==========

A library for more securely handling of GET/POST objects in PHP

# Retrofiting legacy PHP projects

A common problem in supporting legacy PHP is that old code may not do 
enough or appropriate validation and this leads to potential injection
problems (XSS and SQL).  To mitigate this you need to do three things

* At the start of the PHP file require safely.php
* Before PHP code is executed then run safeGET(), safePOST(), and safeSERVER() as needed.

This might look something like -

	<?php
	// Added safely, RSD 2012-07-25
	require("/www/assets/safely-php/safely.php");
	$_GET = safeGET();
	$_POST = safePOST();
	
	// the rest of the old should now work safer.

# Using in new projects

When using safely in new projects you should provide an explicit validation
map.  This way we will not be vunerable to injected variables caused by
unsafe use of extract.

In this example their are three supported parameters - id, search, callback 
which are an Integer, Text and Varname respectively. Here's how you would
defined the validation map and then use it with your code.

	```PHP
	<?php
	require("/www/assets/safely-php/safely.php");
	$validation_map = array(
		"id" => "Integer",
		"search" => "Text",
		"callback" => "Varname"
	);
	
	$myGET = safeGET($validation_map);

	if ($myGET["id"] !== false) {
		// build your query safely
		$sql = "SELECT name, email FROM contacts WHERE id = " . 
		$myGET["id"];
	} else if ($myGET['search'] !== false) {
		$sql = "SELECT name, email FROM contacts WHERE (name LIKE \"" . 
			$myGET["search"] . "\" OR email LIKE \"" . $myGET["search"] . "\"";
	}
	
	$qry = mysql_query($sql);
	$users = mysql_fetch_assoc($qry);

	if ($myGET["id"] !== false) {
		header("Content-Type: application/javascript");
		echo renderAsJSONP($users, $callback);
	} else {
		header("Content-Type: application/json");
		echo json_encode($users, true);
	}
	?>
	```

