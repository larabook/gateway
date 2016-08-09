getresult.php
<?php
	include_once 'parser.php';

	$fields = array('invoiceUID' => $_GET['tref'] );
	$result = post2https($fields,'https://pep.shaparak.ir/CheckTransactionResult.aspx');
	$array = makeXMLTree($result);

    echo("<br /><br /><h1>");
    echo $array["resultObj"]["result"];
    echo("</h1>")
?>
