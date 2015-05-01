<?php
/**
 * safely_test.php - varify that the safely functions can protected
 * against expected vunerabilities.
 */
define("SAFELY_ALLOW_UNSAFE", true);
if (php_sapi_name() !== "cli") {
	echo "Must be run from the command line." , PHP_EOL;
	exit(1);
}
error_reporting(E_ALL | E_STRICT);
@date_default_timezone_set(date_default_timezone_get());

echo 'Requiring assert.php' , PHP_EOL;
require('assert.php');
$_POST = array();
$_SERVER = array();

echo 'Loading safely.php' , PHP_EOL;
include('safely.php');

function testIsFilename () {
    global $assert;

    $assert->ok(isValidFilename("three.txt"), "/one/two/three.txt should be valid");
    $assert->ok(isValidFilename("/one/two/three.txt"), "/one/two/three.txt should be valid");
    $assert->ok(!isValidFilename("../etc/passwd"), "../etc/passwd should not be accepted as a valid filename");
    $assert->ok(!isValidFilename("../etc/passwdpoiweopirepoewripewroperiwporpweoiewrpoiwerpoierwpoirepwoweripoewpoewrpoewripeowripewroierwpoierwpopeowirpoewrirewpoweripewroipeworiewproierwpierwpoierwpoirwepoierwpoiwerpoewirpewirperwipoerirepoierpowiepoweierpoiewrpoewrirewp"), "../etc/passwd should not be accepted as a valid filename");

    return "OK";
}

function testSupportFunctions () {
	global $assert;
	
	// Test basic GET args
    $_GET = array();
	$_GET["int"] = "1";
	$_GET["float"] = "2.1";
	$_GET["varname"] = "my_var_name";
	$_GET["html"] = "This is a <b>html</b>.";
	$_GET["text"] = "This is plain text.";
    $_GET["boolean"] = "true";
    $_GET["url"] = "http://www.usc.edu";
    $_GET["email"] = "ttrojan@usc.edu";
	$expected_map = array(
		"int" => "Integer",
		"float" => "Float",
		"varname" => "Varname",
		"html" => "HTML",
		"text" => "Text",
        "boolean" => "Boolean",
        "url" => "Url",
        "email" => "Email"
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

function testImprovedURLHandling () {
    global $assert;

    $_GET = array("url" => "http://example.com");
    $expected = "http://example.com";
    $result = safeGET(array("url" => "url"));
    $assert->equal($result['url'], $expected, "expected $expected");

    $_GET = array("url" => "www.example.com");
    $expected = "http://www.example.com";
    $result = safeGET(array("url" => "url"));
    $assert->equal($result['url'], $expected, "expected $expected");

    return "OK";
}

function testFixHTMLQuotes () {
    global $assert;
    $s = '<p>Test of "quotes" in string.</p>';
    $result = fix_html_quotes($s);
    $expected = '<p>Test of &quot;quotes&quot; in string.</p>';
    $assert->equal($result, $expected, "Expected entities: " . $result);

    $s = 'Test of "quotes" in <b>string</b>.';
    $result = fix_html_quotes($s);
    $expected = 'Test of &quot;quotes&quot; in <b>string</b>.';
    $assert->equal($result, $expected, "Expected entities: " . $result);


    $s = 'Test of "quotes" before <a href="http://example.com">link</a>.';
    $result = fix_html_quotes($s);
    $expected = 'Test of &quot;quotes&quot; before <a href="http://example.com">link</a>.';
    $assert->equal($result, $expected, "Expected entities: " . $result);

    return "OK";
}


function testGETProcessing () {
	global $assert;

	// Test $_GET processing works
	$_GET = array(
		"one" => "1",
		"two" => "2.1",
		"three" => "my_var_name",
		"four" => "This is a string.",
        "five_six" => "this is five underscore six",
        "seven-eight" => "this is seven dash eight"
	);
	$expected_map = array(
		"one" => "1",
		"two" => "2.1",
		"three" => "my_var_name",
		"four" => "This is a string.",
        "five_six" => "this is five underscore six",
        "seven-eight" => "this is seven dash eight"
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

    // Test processing PATH_INFO against a known path structure.
    $term_code_regexp = "20[0-9][0-9][1-3]";
    $section_code_regexp = "[0-9][0-9][0-9][0-9][0-9]";
    $_SERVER['PATH_INFO'] = '/20142/33361';
    $results = safeSERVER(array(
            "PATH_INFO" => '/' . $term_code_regexp . '/' . $section_code_regexp
        ));
    $assert->ok($results, "Should have results from safeSERVER() for PATH_INFO");
    $assert->ok($results['PATH_INFO'], "PATH_INFO should not be false");
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
    $e = true;
    $s = 'true';
    $r = makeAs($s, 'boolean');
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");
    $e = true;
    $s = '1';
    $r = makeAs($s, 'boolean');
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");
    $e = false;
    $s = 'false';
    $r = makeAs($s, 'boolean');
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");
    $e = false;
    $s = '0';
    $r = makeAs($s, 'boolean');
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");
    $e = false;
    $s = 'blahblah';
    $r = makeAs($s, 'boolean');
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");
    
    return "OK";
}

function testPRCEExpressions() {
    global $assert;

    $re = "\([0-9][0-9][0-9]\)[0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]";
    $s = "(213)740-2925";
    $e = "(213)740-2925";
    $r = makeAs($s, $re, true);
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");

    $s = "(213)740-292592";
    $e = false;
    $r = makeAs($s, $re, true);
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");

    return "OK";
    
}

function testMakeAs() {
    global $assert;

    $s = "one1";
    $e = "one1";
    $r = makeAs($s, "varname", false);
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");

    $s = 1;
    $e = false;
    $r = makeAs($s, "varname", false);
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");

    $s = "http://www.usc.edu";
    $e = "http://www.usc.edu";
    $r = makeAs($s, "Url", false);
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");

    $s = "htp://www.usc.edu";
    $e = false;
    $r = makeAs($s, "Url", false);
    $assert->equal($e, $r, "[$e] == [$r] for [$s]");

    $valid_email_examples = array(
        'ttrojan@usc.edu',
        'niceandsimple@example.com',
        'very.common@example.com',
        'a.little.lengthy.but.fine@dept.example.com',
        'disposable.style.email.with+symbol@example.com',
        'other.email-with-dash@example.com',
        '"very.(),:;<>[]\".VERY.\"very@\\ \"very\".unusual"@strange.example.com'
    );
    foreach ($valid_email_examples as $s) {
        $e = $s;
        $r = makeAs($s, "Email", false);
        $assert->equal($e, $r, "[$e] == [$r] for [$s]");
    }
    $invalid_email_examples = array(
        '3@c@ttrojan@usc.edu',
        'Abc.example.com',
        'A@b@c@example.com',
        'a"b(c)d,e:f;g<h>i[j\k]l@example.com',
        'just"not"right@example.com',
        'this is"not\allowed@example.com',
        'this\ still\"not\\allowed@example.com'
    );
    foreach ($invalid_email_examples as $s) {
        $e = false;
        $r = makeAs($s, "Email", false);
        $assert->equal($e, $r, "[$e] == [$r] for [$s]");
    }

    $text=<<<EOT
Frédéric Hurlet, Université Paris-Ouest, Nanterre La Défense, will present "Spaces of Indignity. Being deprived of friendship of the prince and banned from court" to the Pre-Modern Mediterrean seminar series on April 21st.
EOT;

    $text_expected=<<<EOT
Fr&#233;d&#233;ric Hurlet, Universit&#233; Paris-Ouest, Nanterre La D&#233;fense, will present &quot;Spaces of Indignity. Being deprived of friendship of the prince and banned from court&quot; to the Pre-Modern Mediterrean seminar series on April 21st.
EOT;

    $result = makeAs($text, 'HTML', true);
    $assert->equal($text_expected, $result, "\n[$text_expected]\n[$result]\n");

    // Christel Muller example.
    $text=<<<EOT
Christel Müller, Université Paris-Ouest Nanterre La Défense, will present "The 'common emporion of Greece': groups and subgroups of foreigners in Late Hellenistic Delos' to the Pre-Modern Mediterranean" seminar on Monday, April 20th.
EOT;

    $text_expected=<<<EOT
Christel M&#252;ller, Universit&#233; Paris-Ouest Nanterre La D&#233;fense, will present &quot;The &apos;common emporion of Greece&apos;: groups and subgroups of foreigners in Late Hellenistic Delos&apos; to the Pre-Modern Mediterranean&quot; seminar on Monday, April 20th.
EOT;
    $result = makeAs($text, 'HTML', true);
    $assert->equal($text_expected, $result, "\n[$text_expected]\n[$result]\n");
    

    $text=<<<EOT
Professor Müller's paper aims at observing the changes that have affected, during the Late Hellenistic period (c. 150-50 BCE), the exceptional Delian 'social laboratory', an Athenian colony at this time according to Pierre Roussel: these changes concern the composition, but also the perception and self presentation of the diverse groups of foreigners that had their residence on the island (Athenians, Romans/Italians, other 'Greeks'...). The main documents here are the inscriptions, first the decrees of the so-called Athenian cleruchy, then later on the numerous dedications made by these groups collectively.
EOT;

    $text_expected=<<<EOT
Professor M&#252;ller&apos;s paper aims at observing the changes that have affected, during the Late Hellenistic period (c. 150-50 BCE), the exceptional Delian &apos;social laboratory&apos;, an Athenian colony at this time according to Pierre Roussel: these changes concern the composition, but also the perception and self presentation of the diverse groups of foreigners that had their residence on the island (Athenians, Romans/Italians, other &apos;Greeks&apos;...). The main documents here are the inscriptions, first the decrees of the so-called Athenian cleruchy, then later on the numerous dedications made by these groups collectively.
EOT;

    $result = makeAs($text, 'HTML', true);
    $assert->equal($text_expected, $result, "\n[$text_expected]\n[$result]\n");
    

    $text=<<<EOT
Christel Müller is Professor of Greek History at the University of Paris Ouest Nanterre La Défense. Her main subjects of interest are: society and institutions of Hellenistic Greece, Boeotian epigraphy, the economy and society of colonisation (Black Sea). She is the author of D'Olbia à Tanaïs: Territoires et réseaux d'échanges dans la Mer Noire septentrionale aux époques classique et hellénistique (2010), the co-author of Archéologie historique de la Gréce antique, 3rd ed. (2014) and the co-editor of Les Italiens dans le monde grec (2002), Identités et cultures dans le monde méditerranéen antique (2002), Citoyenneté et participation à la basse époque hellénistique (2005) and Identité ethnique et culture matérielle dans le monde grec (2014).
EOT;

    $text_expected=<<<EOT
Christel M&#252;ller is Professor of Greek History at the University of Paris Ouest Nanterre La D&#233;fense. Her main subjects of interest are: society and institutions of Hellenistic Greece, Boeotian epigraphy, the economy and society of colonisation (Black Sea). She is the author of D&apos;Olbia &#224; Tana&#239;s: Territoires et r&#233;seaux d&apos;&#233;changes dans la Mer Noire septentrionale aux &#233;poques classique et hell&#233;nistique (2010), the co-author of Arch&#233;ologie historique de la Gr&#233;ce antique, 3rd ed. (2014) and the co-editor of Les Italiens dans le monde grec (2002), Identit&#233;s et cultures dans le monde m&#233;diterran&#233;en antique (2002), Citoyennet&#233; et participation &#224; la basse &#233;poque hell&#233;nistique (2005) and Identit&#233; ethnique et culture mat&#233;rielle dans le monde grec (2014).
EOT;

    $result = makeAs($text, 'HTML', true);
    $assert->equal($text_expected, $result, "\n[$text_expected]\n[$result]\n");
    
    return "OK";
}

function testSelectMultiple() {
    global $assert;

    $_POST = array(
        'select_multiple' => array(
            '1',
            '2',
            'The Fox'
        )
    );

    $post = safePOST(array(
        'select_multiple' => 'Array_Integers'
    ));

    $assert->equal($post['select_multiple'][0], '1', 'First element should be "1"');
    $assert->equal($post['select_multiple'][1], '2', 'Second element should be "2"');
    $assert->equal(isset($post['select_multiple'][2]), false, "Third element should not be there. " . print_r($post, true));
    return "OK";
}

function testUTF2HTML() {
    global $assert;
    
    $s = '<a href="#jim">Jim</a> said, ' . html_entity_decode('&ldquo;') . 'I' . 
        html_entity_decode('&apos;') . 's here now.' . html_entity_decode('&rdquo;');
    $e = '<a href="#jim">Jim</a> said, &#8220;I&apos;s here now.&#8221;';
    if (version_compare(PHP_VERSION, '5.4.3', '<')) {
        // PHP 5.3.3 translate this way.
        $e = '<a href="#jim">Jim</a> said, &ldquo;I&apos;s here now.&rdquo;';
    }
    $r = utf2html($s);
    $assert->equal($e, $r, "[$e] != [$s]");
    /*FIXME: not sure a solution for this one yet. 
    // Strip \u009c, \u009d, \u0080
    $s = '&#195;&#162;&#194;\u0080&#194;\u009cPicturing Ovid in Pompeii&#195;&#162;&#194;\u0080&#194;\u009d Peter Knox (University of Colorado)';
    $e = '&#195;&#162;&#194;&#194;Picturing Ovid in Pompeii&#195;&#162;&#194;#194; Peter Knox (University of Colorado)';
    $r = utf2html($s);
    $assert->equal($e, $r, "[$e] != [$s]");
    */

    $s = '<a href="mylink.html">My Link</a>';
    $e = '<a href="mylink.html">My Link</a>';
    $r = utf2html($s);
    $assert->equal($e, $r, "[$e] != [$s]");

    $text=<<<EOT
Frédéric Hurlet, Université Paris-Ouest, Nanterre La Défense, will present "Spaces of Indignity. Being deprived of friendship of the prince and banned from court" to the Pre-Modern Mediterrean seminar series on April 21st.
EOT;

    $text_expected=<<<EOT
Fr&#233;d&#233;ric Hurlet, Universit&#233; Paris-Ouest, Nanterre La D&#233;fense, will present "Spaces of Indignity. Being deprived of friendship of the prince and banned from court" to the Pre-Modern Mediterrean seminar series on April 21st.
EOT;

    $result = utf2html($text);
    $assert->equal($text_expected, $result, "\n[$text_expected]\n[$result]\n");

    $text=<<<EOT
Like promotion, loss of social position in Rome tends to exist only when it becomes visible and is inserted as such in public space a way or another. This lecture will focus on two political practices which meant for concerned senators a loss of credit,and thus of status: first, the official act by which the prince or a member of the imperial family would withdraw his friendship from a senator (the so called renuntiatio amicitiae); then, the way a senator could be prevented from visiting the prince (theinterdictio aulae). This lecture will present the sources for these two practices focusing on the more practical aspects and their spatial inscription.
EOT;

    $text_expected=<<<EOT
Like promotion, loss of social position in Rome tends to exist only when it becomes visible and is inserted as such in public space a way or another. This lecture will focus on two political practices which meant for concerned senators a loss of credit,and thus of status: first, the official act by which the prince or a member of the imperial family would withdraw his friendship from a senator (the so called renuntiatio amicitiae); then, the way a senator could be prevented from visiting the prince (theinterdictio aulae). This lecture will present the sources for these two practices focusing on the more practical aspects and their spatial inscription.
EOT;

    $result = utf2html($text);
    $assert->equal($text_expected, $result, "\n[$text_expected]\n[$result]\n");


    $text=<<<EOT
Frédéric Hurlet is professor of History of the Roman World, University Paris Ouest Nanterre La Défense. He is Director of the Maison de l’Archéologie et de l’Ethnologie, René-Ginouvès. He published a few books and papers on the transition between Republic and Principate (first century BC-first century AD). He's working on the imperial power, the Roman aristocracy and the government of the Roman Empire.
EOT;

    $text_expected=<<<EOT
Fr&#233;d&#233;ric Hurlet is professor of History of the Roman World, University Paris Ouest Nanterre La D&#233;fense. He is Director of the Maison de l&#8217;Arch&#233;ologie et de l&#8217;Ethnologie, Ren&#233;-Ginouv&#232;s. He published a few books and papers on the transition between Republic and Principate (first century BC-first century AD). He's working on the imperial power, the Roman aristocracy and the government of the Roman Empire.
EOT;
   
    $result = utf2html($text);
    $assert->equal($text_expected, $result, "\n[$text_expected]\n[$result]\n");
    
    // Christel Muller example.
    $text=<<<EOT
Christel Müller, Université Paris-Ouest Nanterre La Défense, will present "The 'common emporion of Greece': groups and subgroups of foreigners in Late Hellenistic Delos' to the Pre-Modern Mediterranean" seminar on Monday, April 20th.
EOT;

    $text_expected=<<<EOT
Christel M&#252;ller, Universit&#233; Paris-Ouest Nanterre La D&#233;fense, will present "The 'common emporion of Greece': groups and subgroups of foreigners in Late Hellenistic Delos' to the Pre-Modern Mediterranean" seminar on Monday, April 20th.
EOT;
    $result = utf2html($text);
    $assert->equal($text_expected, $result, "\n[$text_expected]\n[$result]\n");
    

    $text=<<<EOT
Professor Müller's paper aims at observing the changes that have affected, during the Late Hellenistic period (c. 150-50 BCE), the exceptional Delian 'social laboratory', an Athenian colony at this time according to Pierre Roussel: these changes concern the composition, but also the perception and self presentation of the diverse groups of foreigners that had their residence on the island (Athenians, Romans/Italians, other 'Greeks'...). The main documents here are the inscriptions, first the decrees of the so-called Athenian cleruchy, then later on the numerous dedications made by these groups collectively.
EOT;

    $text_expected=<<<EOT
Professor M&#252;ller's paper aims at observing the changes that have affected, during the Late Hellenistic period (c. 150-50 BCE), the exceptional Delian 'social laboratory', an Athenian colony at this time according to Pierre Roussel: these changes concern the composition, but also the perception and self presentation of the diverse groups of foreigners that had their residence on the island (Athenians, Romans/Italians, other 'Greeks'...). The main documents here are the inscriptions, first the decrees of the so-called Athenian cleruchy, then later on the numerous dedications made by these groups collectively.
EOT;

    $result = utf2html($text);
    $assert->equal($text_expected, $result, "\n[$text_expected]\n[$result]\n");
    

    $text=<<<EOT
Christel Müller is Professor of Greek History at the University of Paris Ouest Nanterre La Défense. Her main subjects of interest are: society and institutions of Hellenistic Greece, Boeotian epigraphy, the economy and society of colonisation (Black Sea). She is the author of D'Olbia à Tanaïs: Territoires et réseaux d'échanges dans la Mer Noire septentrionale aux époques classique et hellénistique (2010), the co-author of Archéologie historique de la Gréce antique, 3rd ed. (2014) and the co-editor of Les Italiens dans le monde grec (2002), Identités et cultures dans le monde méditerranéen antique (2002), Citoyenneté et participation à la basse époque hellénistique (2005) and Identité ethnique et culture matérielle dans le monde grec (2014).
EOT;

    $text_expected=<<<EOT
Christel M&#252;ller is Professor of Greek History at the University of Paris Ouest Nanterre La D&#233;fense. Her main subjects of interest are: society and institutions of Hellenistic Greece, Boeotian epigraphy, the economy and society of colonisation (Black Sea). She is the author of D'Olbia &#224; Tana&#239;s: Territoires et r&#233;seaux d'&#233;changes dans la Mer Noire septentrionale aux &#233;poques classique et hell&#233;nistique (2010), the co-author of Arch&#233;ologie historique de la Gr&#233;ce antique, 3rd ed. (2014) and the co-editor of Les Italiens dans le monde grec (2002), Identit&#233;s et cultures dans le monde m&#233;diterran&#233;en antique (2002), Citoyennet&#233; et participation &#224; la basse &#233;poque hell&#233;nistique (2005) and Identit&#233; ethnique et culture mat&#233;rielle dans le monde grec (2014).
EOT;

    $result = utf2html($text);
    $assert->equal($text_expected, $result, "\n[$text_expected]\n[$result]\n");

  // NOTE: The micron should not cause the text to be changed. This test confirms the text is preserved by utf2html()
    $text=<<<EOT
Standard historiography of the church in Kyoto during the Christian Century (1549-1650) does not mention the remarkable leader Naito Julia. Julia, who was once the abbess of Jōdo convent, became a successful evangelist for the Jesuit mission in Japan. She founded and led a society of Christian women catechists, whom people called the Miyaco no bicuni [bikuni] ("nuns of Kyoto"). Between 1600 and 1612, Julia and her nuns preached, engaged in religious disputations, catechized, baptized hundreds of persons, and provided pastoral care for the new converts. Although none of the women's writings survive to tell us about their ideas and experiences, Fucan Fabian's 1607 Myōtei mondō suggests how they may have understood differences between Buddhism, Shinto, Confucianism, Taoism and Christianity.
EOT;

    $text_expected=<<<EOT
Standard historiography of the church in Kyoto during the Christian Century (1549-1650) does not mention the remarkable leader Naito Julia. Julia, who was once the abbess of Jōdo convent, became a successful evangelist for the Jesuit mission in Japan. She founded and led a society of Christian women catechists, whom people called the Miyaco no bicuni [bikuni] ("nuns of Kyoto"). Between 1600 and 1612, Julia and her nuns preached, engaged in religious disputations, catechized, baptized hundreds of persons, and provided pastoral care for the new converts. Although none of the women's writings survive to tell us about their ideas and experiences, Fucan Fabian's 1607 Myōtei mondō suggests how they may have understood differences between Buddhism, Shinto, Confucianism, Taoism and Christianity.
EOT;

    $result = utf2html($text);
    $assert->equal($text_expected, $result, "\n[$text_expected]\n[$result]\n");
    
    return "OK";
}

function testAttributeCleaning() {
    global $assert;

    $s = '<div><a href="mylink.html" title="fred" style="font-size:20">Fred</a></div>';
    $e = '<div><a href="mylink.html" title="fred">Fred</a></div>';
    $r = strip_attributes($s);
    $assert->equal($e, $r, "[$e] != [$s]");

    $s = '<div><a href="mylink.html" title="fred" style="font-size:20">Fred</a></div>';
    $e = escape('<div><a href="mylink.html" title="fred">Fred</a></div>');
    $r = makeAs($s, "HTML");
    $assert->equal($e, $r, "[$e] != [$r]");
    $pos = strpos($r, 'href=');
    $assert->notEqual($pos, false, "$pos should not be false.");
    $pos = strpos($r, 'style=');
    $assert->equal($pos, false, "[$e] != [$s]");

    $s = '<div><a href="javascript:alert(\'Something bad\');" title="fred" style="font-size:20">Fred</a></div>';
    $e = escape('<div><a title="fred">Fred</a></div>');
    $r = makeAs($s, "HTML");
    $assert->equal($e, $r, "[$e] != [$r]");
    $pos = strpos($r, 'href=');
    $assert->equal($pos, false, "$pos should be false.");
    $pos = strpos($r, 'style=');
    $assert->equal($pos, false, "$pos should be false");
    return "OK";
}

function testSafeJSON() {
    global $assert;

    $validation_map = array(
        'return_to_url' => '(\w+|\.|-|[0-9])+',
        'event_id' => 'Integer',
        'calendar_id' => 'Integer',
        'title' => 'text',
        'subtitle' => 'HTML',
        'summary' => 'HTML',
        'description' => 'HTML',
        'dates' => 'Text', 
        'start' => 'Text',
        'end' => 'Text',
        'venue' => 'Text',
        'building_code' => 'Text',
        'campus' => 'Text',
        'room' => 'Text',
        'address' => 'Text',
        'url' => 'url',
        'contact_phone' => 'Text',
        'sponsors' => 'Text',
        'cost' => 'Text',
        'rsvp_email' => 'email',
        'rsvp_url' => 'url',
        'ticket_url' => 'url',
        'categories' => 'Text',
        'audiences' => 'Text',
        'notes' => 'Text',
        'contact_email' => 'email',
        'feature_candidate' => 'Boolean');

    $badjson =<<<BAD_JSON
{"event_id":"913298","title":"<a href =\"javascript:whs(1)\">click<\/a>","subtitle":"","summary":"<script>function whs(val) { assert(val); }</script>click<a  href =\"javascript:whs(1)\">click<\/a>","description":"<a  href =\"javascript:whs(1)\">click<\/a>","cost":"","contact_phone":"","contact_email":"","rsvp_email":"","rsvp_url":"","url":"","ticket_url":"","campus":"University Park","venue":"125th Anniversary Fountain","building_code":"","room":"1234","address":"125th Anniversary Fountain","feature_candidate":"0","username":"dd_064","name":"WhiteHat Audit Account","scratch_pad":"","created":"2014-11-06 12:52:50","updated":"2014-11-06 12:52:50","publication_date":"0000-00-00 00:00:00","parent_calendar_id":"32","parent_calendar":"USC Public Events","sponsors":[],"audiences":[],"schedule":"11\/25\/2014: 03:00 - 05:00","dates":"11\/25\/2014","occurrences":[{"start":"2014-11-25 03:00:00","end":"2014-11-25 05:00:59"}],"first_occurrence":"2014-11-25 03:00:00","last_occurrence":"2014-11-25 03:00:00","next_occurrence":"2014-11-25 03:00:00","categories":{"32":["Theater"]},"attachments":{"32":{"image_o":{"mime_type":"image\/jpeg","url":"https:\/\/web-app.usc.edu\/event-images\/32\/913298\/whs_xss_test.jpg"}}},"status":{"32":{"status":"draft","calendar_id":"32"}},"start":"3:00","end":"5:00","error_status":"OK"}
BAD_JSON;

   $result = json_decode($badjson, true);
   $result = safeJSON($badjson, $validation_map, false);
   $assert->ok(is_array($result), "Should get an array type back");
   $assert->ok(is_integer($result['event_id']), "Should have an integer value for event_id");
   $assert->equal($result['event_id'], 913298, "have an event id of 913298");
   $assert->ok(is_string($result['title']), "title should be string " . gettype($result['title']));
   $assert->equal($result['title'], "click", "title wrong.");
   $assert->equal(strpos($result['summary'], "<script>"), false, "Should move script element");
   return "OK";
}

function testHREFCleaning() {
   global $assert;

   $validation_map = array(
        "title" => "HTML"
       );
   $_POST = array(
        "title" => 'Injection <a href="javascript:alert(\"Something Bad\")">Test</a>.'
       );
   $expected_result = array(
        "title" => 'Injection <a >Test</a>.'
       );
   $result = safePOST($validation_map);
   $assert->equal($result['title'], $expected_result['title'], "Should have a clean href in title: ". $result['title']);

   $_POST = array(
        "title" => 'Injection <a href=' . "'" . 'javascript:alert("Something Bad")' . "'" . '>Test</a>.'
       );
   $result = safePOST($validation_map);
   $assert->equal($result['title'], $expected_result['title'], "Should have a clean href in title: ". $result['title']);
   return "OK";
}

function testAnchorElementSantization() {
    global $assert;

    $validation_map = array(
       "txt" => "HTML"
    );

    $badjson =<<<BAD_JSON
{"txt": "<a href=\"javascript:alert('test')\">click</a>"}
BAD_JSON;
    $result = safeJSON($badjson, $validation_map, false);
    $assert->equal(strpos($result["txt"], "javascript"), false, "Javascript href should get removed.");
    $goodjson =<<<GOOD_JSON
{"txt": "<a href=\"http://example.com\">click</a>"}
GOOD_JSON;
    $result = safeJSON($goodjson, $validation_map, false);
    $assert->ok(strpos($result["txt"], "http://example.com") !== false, "href should not get removed.");
    return "OK";
}

function testHTMLQuoteHandling () {
    global $assert;

    $_GET = array('title' => '<p>Test of "quotes"</p>');
    $result = safeGET(array('title' => 'HTML'));
    $assert->equal($result['title'], '<p>Test of &quot;quotes&quot;</p>', "Should convert quotes to entity: " . $result['title']);
    return "OK";
}

function testCleanScriptElements() {
    global $assert;

    $raw = '<script>alert("Oops this is bad.");</script>This is a title.';
    $expect = 'alert("Oops this is bad.");This is a title.';
    $result = strip_tags($raw, SAFELY_ALLOWED_HTML);
    $assert->equal($result, $expect, "strip_tags() failed." . $result);

    // lib version should convert the " to &lquo; and &rquo;
    $expect = 'alert(&quot;Oops this is bad.&quot;);This is a title.';

    $_GET = array("title" => $raw);
    $result = safeGET(array('title' => 'HTML'));
    $assert->equal($result['title'], $expect, 'Failed to correct ' . $raw);


    $_POST = array("title" => $raw);
    $result = safePOST(array('title' => 'HTML'));
    $assert->equal($result['title'], $expect, 'Failed to correct ' . $raw);
    return "OK";
}

function testSaneUnicodeSupportPCRE() {
    global $assert;

    $allowInternational = false;
    if (defined('PCRE_VERSION')) {
        if (intval(PCRE_VERSION) >= 7) { // constant available since PHP 5.2.4
                $allowInternational = true;
        }
    }
    $assert->ok($allowInternational, "PCRE should support proper UTF-8, may need to compile with --enable-unicode-properties");
    return "OK";
}

echo "Starting [" , $argv[0] , "]..." , PHP_EOL;

$assert->ok(function_exists("defaultValidationMap"), "Should have a defaultValidationMap function defined.");
$assert->ok(function_exists("safeGET"), "Should have a safeGET function defined.");
$assert->ok(function_exists("safePOST"), "Should have a safePOST function defined.");
$assert->ok(function_exists("safeSERVER"), "Should have a safeSERVER function defined.");
$assert->ok(function_exists("safeJSON"), "Should have a safeJSON function defined.");

echo "\tTesting testIsFilename: " , testIsFilename() , PHP_EOL;
echo "\tTesting testUTF2HTML: " , testUTF2HTML() , PHP_EOL;
echo "\tTesting testAttributeCleaning: " , testAttributeCleaning() , PHP_EOL;
echo "\tTesting testHREFCleaning: " , testHREFCleaning() , PHP_EOL;
echo "\tTesting testSaneUnicodeSupportPCRE: " , testSaneUnicodeSupportPCRE() , PHP_EOL;
echo "\tTesting testCleanScriptElements: " , testCleanScriptElements() , PHP_EOL;
echo "\tTesting testImprovedURLHandling: " , testImprovedURLHandling() , PHP_EOL;
echo "\tTesting testFixHTMLQuotes: " , testFixHTMLQuotes() , PHP_EOL;
echo "\tTesting testHTMLQuoteHandling: " , testHTMLQuoteHandling() , PHP_EOL;
echo "\tTesting testSelectMultiple: " , testSelectMultiple() , PHP_EOL;
echo "\tTesting testMakeAs: " , testMakeAs() , PHP_EOL;
echo "\tTesting support functions: " , testSupportFunctions() , PHP_EOL;
echo "\tTesting get processing: " , testGETProcessing() , PHP_EOL;
echo "\tTesting post processing: " , testPOSTProcessing() , PHP_EOL;
echo "\tTesting server processing: " , testSERVERProcessing() , PHP_EOL;
echo "\tTesting safeStrToTime process: " , testSafeStrToTime() , PHP_EOL;
echo "\tTesting Varname Lists process: " , testVarnameLists() , PHP_EOL;
echo "\tTesting PRCE expressions process: " , testPRCEExpressions() , PHP_EOL;
echo "\tTesting testSafeJSON: " , testSafeJSON() , PHP_EOL;
echo "\tTesting testAnchorElementSantization: " , testAnchorElementSantization() , PHP_EOL;
echo "Success!" , PHP_EOL;
?>
