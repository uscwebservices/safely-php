<?php
include('../safely.php');
if (isset($_POST['the_selected'])) {
    $the_selected = json_encode($_POST['the_selected'], JSON_PRETTY_PRINT);
}
$the_selected_filtered1 = json_encode(safePOST(array(
    'the_selected' => 'Array_Integers'
)), JSON_PRETTY_PRINT);
$the_selected_filtered2 = json_encode(safePOST(array(
    'the_selected' => 'Array_Text'
)), JSON_PRETTY_PRINT);
?>
<!DOCTYPE>
<html>
    <head>
        <title>select multiple example</title>
    </head>
    <body>
        <section>Unfiltered $_POST['the_selected'] output: <pre><?php echo $the_selected; ?></pre></section>
        <section>Filtered for integers $post2['the_selected'] output: <pre><?php echo $the_selected_filtered1; ?></pre></section>
        <section>Filtered for text strings $post2['the_selected'] output: <pre><?php echo $the_selected_filtered2; ?></pre></section>
        <p>Make a selection and press submit to see the processed output.</p>
        <form method="post" action="">
            <div><label>Make a selection: </label>
                <select name="the_selected[]" multiple>
                    <option value="1">One</option>
                    <option value="2">Two</option>
                    <option value="3">Three</option>
                    <option value="4">Four</option>
                    <option value="A Fox">A Fox</option>
                </select>
            </div>
            <div><input type="submit" name="submit" value="submit selections" /></div>
        </form>
    </body>
</html>
