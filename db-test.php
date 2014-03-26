<?php
/**
 * tests for db.php.
 */
if (php_sapi_name() !== "cli") {
        echo "Must be run from the command line." . PHP_EOL;
        exit(1);
}
error_reporting(E_ALL | E_STRICT);
@date_default_timezone_set(date_default_timezone_get());

if (file_exists("assert.php")) {
    require_once("assert.php");
}
require_once('db.php');

if (file_exists('con-test.php')) {
    include('con-test.php');
} else {
    die("You need to create con-test.php to test the db library." . PHP_EOL);
}


function testConstructor ($MYSQL_CONNECTION_URL) {
    global $assert;
    $scheme = parse_url($MYSQL_CONNECTION_URL, PHP_URL_SCHEME);
    $dbname = basename(parse_url($MYSQL_CONNECTION_URL, PHP_URL_PATH));
    $dbuser = parse_url($MYSQL_CONNECTION_URL, PHP_URL_USER);
    $dbpasswd = parse_url($MYSQL_CONNECTION_URL, PHP_URL_PASS);
    $dbhost = parse_url($MYSQL_CONNECTION_URL, PHP_URL_HOST);

    // using a dummy MySQL connection string to test object creation.
    $obj = new Db($MYSQL_CONNECTION_URL);
    $assert->equal($scheme, $obj->db_type, "expected scheme: $scheme");
    $assert->equal($dbuser, $obj->db_user, "expected db username: $dbuser");
    $assert->equal($dbpasswd, $obj->db_password, 'expected db password: ******');
    $assert->equal($dbhost, $obj->db_host, 'expected (db hostname: ' . $dbhost . ')');
    $assert->equal($dbname, $obj->db_name, 'expected (db namae: ' . $dbname . ')');
    $assert->equal(null, $obj->link, 'expected link to be null');
    return "OK";
}

function testOpenClose($MYSQL_CONNECTION_URL) {
    global $assert;
    
    $obj = new Db($MYSQL_CONNECTION_URL);
    $obj->open();
    $assert->notEqual(false, $obj->link, "link should not be false after open." . print_r($obj, true));
    $obj->close();
    $assert->equal(null, $obj->link, "link should be null after close.");
    return "OK";
}

function testSQL($MYSQL_CONNECTION_URL) {
    global $assert;
    
    $obj = new Db($MYSQL_CONNECTION_URL);
    $obj->open();
    // Drop the test DB if it already exists
    $r = $obj->executeSQL("CREATE TABLE IF NOT EXISTS test_persistence (id INTEGER AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), level INTEGER, success FLOAT)");
    $assert->ok($r, "Should (re)created the table test_persistence");
    $r = $obj->executeSQL("DELETE FROM test_persistence");
    $assert->ok($r, "Should delete any existing rows in test_persistence.");
    $r = $obj->executeSQL("SELECT * FROM test_persistence");
    $assert->ok($r, "Should get a true response from executeSQL().");
    $row_count = $obj->rowCount();
    $assert->equal(0, $row_count, "Should get zero rows" . print_r($obj->rows, true));

    $r = $obj->executeSQL("INSERT INTO test_persistence (name, level, success) VALUES (?, ?, ?)",
                          array("fred", 3, 0.98), true);
    $assert->ok($r, "Should get a true response from executeSQL().");
    $assert->equal(1, $obj->rowsAffected(), "Should have one new row");

    $r = $obj->executeSQL("SELECT * FROM test_persistence");
    $row_count = $obj->rowCount();
    $assert->equal(1, $row_count, "Should get one row" . print_r($obj->rows, true));
    $assert->ok($r, "Should get a true response from executeSQL().");
    $row = $obj->getRow();
    $assert->equal("fred", $row['name'], "name should be fred" . print_r($row, true));
    $assert->equal(3, $row['level'], "level should be 3" . print_r($row, true));
    $assert->equal(0.98, round($row['success'], 2), "success should be 0.98" . print_r($row, true));

    $obj->close();
    return "OK";
}

echo 'Starting [' . $argv[0] . '] ...' . PHP_EOL;
if (version_compare(phpversion(), '5.5.0', '>=') === true) {
    echo "\tmysqli_* driver test\n";
    // Test mysqli_* driver
    testConstructor($MYSQL_CONNECTION_URL);
    testOpenClose($MYSQL_CONNECTION_URL);
    testSQL($MYSQL_CONNECTION_URL);
}
if (version_compare(phpversion(), '5.3.0', '>=') === true) {
    // Test mysql_* driver
    echo "\tmysql_* driver test\n";
    $MYSQL_CONNECTION_URL = preg_replace('|mysqli|', 'mysql', $MYSQL_CONNECTION_URL, 1);
    testConstructor($MYSQL_CONNECTION_URL);
    testOpenClose($MYSQL_CONNECTION_URL);
    testSQL($MYSQL_CONNECTION_URL);
} else {
    die("ERROR: this library assumes at least version 5.3.x of PHP.");
}
echo 'Success!' . PHP_EOL;
?>
