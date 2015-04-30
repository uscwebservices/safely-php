<?php
/**
 * tests for db.php.
 */
if (php_sapi_name() !== "cli") {
        echo "Must be run from the command line." , PHP_EOL;
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

function testLogOutput ($MYSQL_CONNECTION_URL) {
    global $assert;

    $db = new Db($MYSQL_CONNECTION_URL);
    $db->setLog("echo");
    $db->logIt("This is my log message via echo", true);
    echo 'Should have seen a line logged with "echo" with <pre> elements.' , PHP_EOL;
    $db->setLog("error_log");
    $db->logIt("This is my log message via error_log()", true);
    echo 'Should have seen a line logged to error_log().' , PHP_EOL;
    return "Requires manual visual check";
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

function testEmbeddedQuestionMarks($MYSQL_CONNECTION_URL) {
    global $assert;
    
    $db = new Db($MYSQL_CONNECTION_URL);
    $db->open();
    $sql = 'CREATE TABLE IF NOT EXISTS test_events (' .
            'event_id INTEGER AUTO_INCREMENT PRIMARY KEY, '.
            'title VARCHAR(255) NOT NULL, ' .
            'subtitle VARCHAR(255) DEFAULT "", ' .
            'summary TEXT, description TEXT, ' .
            'venue VARCHAR(255) DEFAULT "", ' .
            'campus VARCHAR(255) DEFAULT "", ' .
            'building_code VARCHAR(255) DEFAULT "", ' .
            'room VARCHAR(255) DEFAULT "", ' .
            'address TEXT, ' .
            'cost TEXT, ' .
            'organizer VARCHAR(50) DEFAULT NULL, ' .
            'contact_phone VARCHAR(38) DEFAULT NULL, ' .
            'contact_email VARCHAR(45) DEFAULT NULL, ' .
            'rsvp_email VARCHAR(45) DEFAULT NULL, ' .
            'rsvp_url VARCHAR(255) DEFAULT "", ' .
            'website_url VARCHAR(255) DEFAULT NULL, ' .
            'ticket_url VARCHAR(255) DEFAULT NULL, ' .
            'feature_candidate smallint(1) DEFAULT NULL, ' .
            'user_id INTEGER DEFAULT 0, ' .
            'username VARCHAR(255) DEFAULT "", ' .
            'display_name VARCHAR(255) DEFAULT "", ' .
            'parent_calendar_id INTEGER DEFAULT 0, ' .
            'parent_calendar VARCHAR(255) DEFAULT "", ' .
            'scratch_pad TEXT, ' .
            'created DATETIME DEFAULT "0000-00-00 00:00:00", ' .
            'updated TIMESTAMP, ' .
            'publication_date DATETIME DEFAULT NULL)';
    $db->executeSQL($sql);
    
    $event_id =  906300;
    $event = array(
        "event_id" => 906300,
        "title" => "",
        "subtitle" => "USC School of Cinematic Arts",
        "summary" => "",
        "description" => "",
        "cost" => "Free",
        "organizer" => "aago@cinema.usc.edu",
        "contact_phone" => "(213) 740-2330",
        "contact_email" => "",
        "rsvp_email" => "",
        "rsvp_url" => "http:\/\/cinema.usc.edu\/events\/reservation.cfm?id=13787",
        "website_url" => "http:\/\/cinema.usc.edu\/events\/event.cfm?id=13787",
        "ticket_url" => "",
        "campus" => "",
        "venue" => "The Albert and Dana Broccoli Theatre, SCA 112, George Lucas Buil",
        "building_code" => "",
        "room" => "SCA 112",
        "address" => "",
        "feature_candidate" => 0,
        "user_id" => 0,
        "username" => "guest",
        "display_name" => "guest",
        "scratch_pad" => "",
        "created" => "2013-10-10 16:16:01",
        "updated" => "2013-10-14 12:33:20",
        "publication_date" => "0001-01-01 00:00:00",
        "calendar_id" => 32,
        "parent_calendar_id" => 32,
        "parent_calendar" => "USC Public Events"
	);
    $sql = 'INSERT INTO test_events (event_id, title, subtitle, summary, ' .
        'description, ' .
        'venue, campus, building_code, room, address, cost, ' .
        'organizer, contact_phone, contact_email, ' .
        'rsvp_email, rsvp_url, website_url, ticket_url, feature_candidate, ' .
        'user_id, username, display_name, parent_calendar_id, parent_calendar, ' .
        'scratch_pad, created, updated, publication_date) VALUES (' .
        '?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '. 
        '?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ' .
        '?, ?, ?, ?, ?, ?, ?)';
    $isOK = $db->executeSQL($sql, array(
        $event['event_id'],
        $event['title'],
        $event['subtitle'],
        $event['summary'],
        $event['description'],
        $event['venue'],
        $event['campus'],
        $event['building_code'],
        $event['room'],
        $event['address'],
        $event['cost'],
        
        $event['organizer'],
        $event['contact_phone'],
        $event['contact_email'],
        $event['rsvp_email'],
        $event['rsvp_url'],
        $event['website_url'],
        $event['ticket_url'],
        $event['feature_candidate'],
        $event['user_id'],
        $event['username'],
        
        $event['display_name'],
        $event['parent_calendar_id'],
        $event['parent_calendar'],
        $event['scratch_pad'],
        $event['created'],
        $event['updated'],
        $event['publication_date']), true);

    
    $sql = 'SELECT event_id FROM test_events WHERE event_id = ?';
    $db->executeSQL($sql, array($event_id));
    $row = $db->getRow();
    $assert->ok($row !== false, "Should get back a row of data for $event_id");
    $assert->equal($event_id, $row['event_id'], "Should get matching event id $event_id");
    $sql = 'DELETE FROM test_events WHERE event_id = ?';
    $r = $db->executeSQL($sql, array($event_id));
    $assert->ok($r, "Should get true from delete event_id $event_id");
    $db->close();
    return "OK";
}


echo 'Starting [' , $argv[0] , '] ...' , PHP_EOL;
if (version_compare(phpversion(), '5.5.0', '>=') === true) {
    echo "\tmysqli_* driver test\n";
    // Test mysqli_* driver
    testConstructor($MYSQL_CONNECTION_URL);
    testOpenClose($MYSQL_CONNECTION_URL);
    testSQL($MYSQL_CONNECTION_URL);
    testEmbeddedQuestionMarks($MYSQL_CONNECTION_URL);
}
if (version_compare(phpversion(), '5.3.0', '>=') === true) {
    // Test mysql_* driver
    echo "\tmysql_* driver test\n";
    $MYSQL_CONNECTION_URL = preg_replace('|mysqli|', 'mysql', $MYSQL_CONNECTION_URL, 1);
    testConstructor($MYSQL_CONNECTION_URL);
    testOpenClose($MYSQL_CONNECTION_URL);
    testSQL($MYSQL_CONNECTION_URL);
    testEmbeddedQuestionMarks($MYSQL_CONNECTION_URL);
} else {
    die("ERROR: this library assumes at least version 5.3.x of PHP.");
}
testLogOutput($MYSQL_CONNECTION_URL);
echo 'Success!' , PHP_EOL;
?>
