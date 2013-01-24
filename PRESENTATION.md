Below is some background material I put together for a presentation. Your milleage may very.

USC Web Council<br />
January 24, 2013<br />
R. S. Doiel, ITS Web Services<br />
[http://github.com/uscwebservices/safely-php](http://github.com/uscwebservices/safely-php)


<center>_SQL injection via PHP_</center>
<center>__protecting legacy websites with safely-php__</center>


# Part 1
(context)

## My recent audit work

* A number of sites have come up with problems recently
* One common problem sticks out - SQL injection via PHP
* It is preventable
* It is also fixable

## It is a big deal.

* Risk of exposing contents of your database
* Risk of exposing content of other databases
* Compromised data costs dollars, trust and time

This lead to safely-php being written to make it easier to retrofit legacy PHP 
with simple sanitization and validation functions.


## Today's talk

* SQL Injection
* Involving PHP and MySQL
* Legacy code


## We're going to explore

* Finding the problem
* Fixing the problem with Validation
* Fixing the problem with Sanitization


## When we're done

* A basic idea of how to scan a PHP file to find problems
* Have an approach to fixing things
* Understand how to put together a regularly repeatable plan to keep your site safe


## Some Bad news

* To put what we're talking about into practice means looking at ALL your PHP code
* Legacy sites tend to have lots of files (and lots of lines of code)
* Legacy sites to have code repeated in lots of place (due to the practice of copy and modify)
* Legacy tend to lack co-herency as things tend to grow organically after a certain point


## Some Good News

* There is a simple approach to finding problems (follow the data)
* With a little effort you can also avoid the problems
* You can repeat this process regularly to limit your risk moving forward 
* It also gets easier as things get pruned, fixed or replaced at each iteration


## Terminology

* Inputs
	* Don't trust them
	* Confirm they are what you expect (validation)
* SQL statements
* Database access methods
	* e.g. mysql_query()
* Validation
* Sanitization


## Def: Inputs (in PHP)

* $_GET
* $_POST
* $_SERVER
* $_FILE


## Def: SQL statements

It's easer to show you some examples:

```SQL
	SELECT name, address, phone, email FROM contacts WHERE name LIKE "Doiel";
	INSERT INFO contacts (name, address, phone, email) VALUES 
		("Fred", "321 First St. Anytown, Utopia", "123-456-7890", "fred@example.com");
	SHOW DATABASES;
	SHOW TABLES;
	SELECT * FROM user;
```

Typically these are assembled by your PHP script based on inputs.


## Def: Database access methods (PHP v5.3 and earlier)

Here's the most common ones:

* mysql_query()
* mysql_db_query()
* mysql_unbuffered_query()

(Any function beginning with "mysql_" needs to be looked at)


## Def: Validation

Making sure the input is exactly what you expected (e.g. if you were expecting the answer "1", "2" or "3" 
but your PHP program allows "hello" as an answer you have a validation problem. Probably an injection problem too)


## Def: Sanitization (sometimes harder, but also helpful)

In data processing this is analogous to water sanitization.  You're trying to remove all the bad or dangerous elements.  If as example you were accepting someone's email address and you received an input of "john.doe@example.com<script>alert('hello there');</script>". A sanitization function might remove "<script>...</script>"  and only return "john.doe@example.com".   (this last example is a common case of JavaScript injection if you presented the results without sanitization to the screen).  

### Short definition:

Sanitization attempts to remove dangerous sequences of characters from a string.

PHP functions useful in sanitization include: strip_tags(), str_replace(), uudecode(), preg_replace()


## Recap:

* Validation tells you if this input is acceptable
* Sanitization changes the input removing the dangerous stuff

Questions?


# Part 2
(narrowing the problem)

## Input concerns

* Anytime your website accepts information
* E.g. form submissions, URL requests
* PHP global variables such as $_GET, $_POST, $_SERVER and $_FILE

This need to be validated, sanitized or both (doesn't hurt to do both).


## Two big tasks

* Checking inputs
* Checking SQL statement construction


## Checking inputs

* Find the places where you use or assign inputs
* Sanitize and validate before assignment or use


## Checking SQL statement construction

* Start by finding the occurrences of mysql_query(), mysql_db_query(), mysql_unbuffered_query
* Trace backward and see how the SQL statement executed is form
	* Is it explicit?
	* Are you using PHP input directly? If so stop, fix you code.
	* Is it calculated? 
* Where do those variable come from?
* Have they be validated/sanitized before having their values assigned?


## For the whole website

Repeat checking inputs and how ALL SQL statements are constructed for each PHP file.


## Tips

* If you don't need the PHP program, remove it
* If you see code that has the same "shape" consolidate it
* Be explicit when you can
* Avoid using PHP Globals directly
* Better yet, Don't use PHP Globals directly at all, use safely-php instead
* Limit the number of PHP files
* Limit the number of lines of PHP code


Questions?


# Part 3
(quick digretion about code audits)

## How do I audit?

* Make a list of files with PHP
* For each file with PHP
	* I look at all inputs and make sure they are validated and sanitized before use
	* I review the construction of ALL SQL statements executed and make sure they are safe

## Find files with PHP

* Look for <?, <?php (case insensitive), or possibly <%
* If these are included you have PHP that can be executed
* Searching the file
	* On Unix - find, grep
	* On a Mac (assuming you've copied the files there) you can use spotlight
	* On a Window's machine use search content (ask a Windows person)
	* Some text/code editors have a "find in files" or "Search Project" 
		* e.g. Textwrangler, BBEdit, Textmate, Dreamweaver???


## When you find input

(these require fixing via sanitization and validation)

* Is a PHP global used directly by a PHP function?
* Is a PHP global used in a explicit assignment?
* Is a PHP global used to build a string? [example]


# Part 4 
(examples)


## Example of bad inputs

```php
	<?php
	// Don't echo/use a PHP global directly
	echo $_GET['name'];
	// Don't assign a PHP global value without sanitization and validation
	$cnt = $_GET['cnt'];
	// Don't assemble a string from PHP globals (e.g. both $_GET['name'] 
	// and $cnt are problems here)
	$msg = $_GET['name'] . ' has a count of ' . $cnt;
	echo $msg;
	// This SQL statement will suffer from injection for both $cnt AND from $_GET['name']
	$sql = "SELECT email FROM contacts WHERE cnt = '" . $cnt . "' and name = '" .
		$_GET['name'];
	mysql_query($sql);
```
 
This example is vunerable to both page and SQL injection. It must be fixed!


## Safer version

```php
	<?php
	// Set some benign default values.
	$name = "";
	$cnt = 0;
	// Make sure the name is safe (i.e. is Alphabetical characters)
	if (ctype_alpha($_GET['name'])){
		// This is OK because we made sure it was valid before assignment
		$name = trim($_GET['name']);
	}
	if (intval($_GET['cnt']) > 0) {
			// This is OK because we made sure it was valid before assignment
			$cnt = intval($_GET['cnt']);
	}
	// Now we're safe. $cnt and $name are predictable
	$sql = "SELECT email FROM contacts WHERE cnt = '$cnt' and name = '$name'";
	mysql_query($sql);
```

The raw inputs are no longer echo back to the browser. The SQL statement has been assembled
from safe copies of the input.

* Using basic PHP functions is good but can be tedious.
* This is really true with legacy code.
* Introducing safely-php - a simple library to add some sanitization and validation to your legacy PHP.

But wait, in can be easier!


## Safely-php

* Already installed on central web server (e.g. /www/assets/safely-php/safely.php)
	* Web services keeps this up to date
* Or download it directly from Github
	* https://github.com/uscwebservices/safely-php

### Sanitization with safely-php

```php
	<?php
	require_once("/www/assets/safely-php/safely.php");

	$get = safeGET(); // This is performing basic sanitization only
	if (isset($get['cnt']) && isset($get['name'])) {
		// Now we're safe. $get['cnt'] and $get['name'] are predictable
		$sql = "SELECT email FROM contacts WHERE cnt = '" . 
		$get['cnt'] . "' and name = '" . $get['name'] . "'";
		mysql_query($sql);
		// do stuff with the results...
	} else {
		die("We have a problem Huston. Those vars are all wrong!");
	}
```


### Simple validation with safely-php

Sanitize and simple validate with safely-php

```php
	<?php
	require_once("/www/assets/safely-php/safely.php");
	// Make a validation map
	$validation_map = array(
		"cnt" => "Integer",
		"name" => "Text"
	);

	$get = safeGET($validation_map); // This is performing basic sanitization and validation
	if (isset($get['cnt']) && isset($get['name'])) {
		// Now we're safe. $get['cnt'] and $get['name'] are predictable
		$sql = "SELECT email FROM contacts WHERE cnt = '" . 
		$get['cnt'] . "' and name = '" . $get['name'] . "'";
		mysql_query($sql);
		// do stuff with the results...
	} else {
		die("We have a problem Huston. Those vars are all wrong!");
	}
```

That's it. It is that simple.


## Examples of problems and solutions

Q: Is this example Safe?

```php
	<?php
	// We're checking if count is 1, 2, or 3 before assigning
	if (in_array($_POST['count'], array(1,2,3))') {
		$my_count = $_POST['count'];
	}
```

A: Yes, we validate before we assign the value to $my_count;


Q: Will $sql ever suffer from injection?

```php
	<?php
	$sql = "SELECT name, address, phonenumber, email FROM myfriends " .
		"WHERE email = 'rsdoiel@usc.edu'";
	mysql_query($sql);
```

A: No, Because it is not form from an unchanging statement

Q: Can we have injection here?

```php
	<?php
	$sql = "SELECT name, address, phonenumber, email FROM myfriends WHERE email = '" .
		$email . "'";
	mysql_query($sql);
```

A: Maybe, it is not clear how $email as assigned.  You need to do more research.


Q: Is this Ok?

```php
	<?php
	$safe_emails = array(
		"rsdoiel@usc.edu",
		"borland@usc.edu",
		"tommy.trojana@usc.edu",
		"tina.trojan@usc.edu"
	);
	$email = "";
	if (in_array(trim($_POST['email']), $safe_emails)) {
		$email = trim($_POST['email']);
	}
	$sql = "SELECT name, address, phonenumber, email FROM myfriends WHERE email = '" .
		$email . "'";
	mysql_query($sql);
```

A: Sure, you are explicitly checking the email address in $_POST['email'] against a
fixed set of known values.

Explanation:

```php
	<?php
	// Assume we only have four friends in myfriends table
	$safe_emails = array(
		"rsdoiel@usc.edu",
		"borland@usc.edu",
		"tommy.trojana@usc.edu",
		"tina.trojan@usc.edu"
	);
	$email = "";// So far $email is safe.
	if (in_array(trim($_POST['email']), $safe_emails)) {
		// Only update $email if it is safe
		$email = trim($_POST['email']);
	}
	// Now we can build our SQL statement
	$sql = "SELECT name, address, phonenumber, email FROM myfriends WHERE email = '" .
		$email . "'";
	mysql_query($sql);
```


Q: Is this code OK?

```php
	<?php
	$sql = "SELECT name, address, phonenumber, email FROM myfriends WHERE email = '" .
		$_POST['email'] . "'";
	mysql_query($sql);
```

A: No, it is very injectable,  **Good News**, this is easy to fix with safely.

```php
	<?php
	require_once("/www/assets/safely-php/safely.php");
	$post = safePOST();
	$sql = "SELECT name, address, phonenumber, email FROM myfriends WHERE email = '" .
		$post['email'] . "'";
	mysql_query($sql);
```


# Enough Code already!

## Prevent problems

1) find your inputs (e.g. $_GET, $_POST, $_SERVER, $_FILE)
	* Make sure all inputs are validated and sanitized
2) find out where your building your SQL statements
	* Prefer explicit statements to calculated statements
	* Only build calculated statements from safe (i.e. validated) variables
3) consolidating your database interaction will make it easier to audit (as well as update later)

# In closing

* Always validate and sanitize your inputs (or use safely-php to do it for you)
* Consolidate your DB interactions so you can review fewer files and lines of PHP
* Remove unused PHP files so you don't have to review them next time
* Audit your legacy code
	* do it regularly, mark it on the calendar

(If you like use safely-php to validate and sanitize your legacy or even new code)

# Finally!

* Safely-php at http://github.com/uscwebservices/safely-php.git
