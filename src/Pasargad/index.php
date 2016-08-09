<?php 
require_once("RSAProcessor.class.php"); 

$processor = new RSAProcessor("certificate.xml",RSAKeyType::XMLFile);
$merchantCode = 111111; // كد پذيرنده
$terminalCode = 111111; // كد ترمينال
$amount = 1; // مبلغ فاكتور
$redirectAddress = "http://???????/PHPSample/getresult.php"; 

$invoiceNumber = 16525; //شماره فاكتور
$timeStamp = date("Y/m/d H:i:s");
$invoiceDate = date("Y/m/d H:i:s"); //تاريخ فاكتور
$action = "1003"; 	// 1003 : براي درخواست خريد 
$data = "#". $merchantCode ."#". $terminalCode ."#". $invoiceNumber ."#". $invoiceDate ."#". $amount ."#". $redirectAddress ."#". $action ."#". $timeStamp ."#";
$data = sha1($data,true);
$data =  $processor->sign($data); // امضاي ديجيتال 
$result =  base64_encode($data); // base64_encode 
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
  <head>
  </head>
  <body>
  <form method="POST" action="https://pep.shaparak.ir/gateway.aspx" target="_self">
	  <input name="invoiceNumber" value="1470654703">
	  <input name="invoiceDate" value="2016/08/08 15:41:43">
	  <input name="amount" value="1000">
	  <input name="terminalCode" value="628092">
	  <input name="merchantCode" value="627261">
	  <input name="timeStamp" value="2016/08/08 15:41:43">
	  <input name="action" value="1003">
	  <input name="sign" value="U1Wj5/qwoVi3oDE5VhycaFEkfcAva1k+6nnUEWgtFroE5ko/AyuF+sm9oXIC/gleW5Ze0vLknWyjhG66KGMwIlD4yWJtx4RUSgRr0FuTvejv84B82LwuOX+ge7RcltT3nMkA3PhXac/U/W8QJmrWDC1f6/rEbaf8ljgUBqaC4nw=">
	  <input name="redirectAddress" value="">
  </form>
  
  
<form Id='Form2' Method='post' Action='https://pep.shaparak.ir/gateway.aspx'>
	invoiceNumber<input type='text' name='invoiceNumber' value='<?= $invoiceNumber ?>' /><br />
	invoiceDate<input type='text' name='invoiceDate' value='<?= $invoiceDate ?>' /><br />
	amount<input type='text' name='amount' value='<?= $amount ?>' /><br />
	terminalCode<input type='text' name='terminalCode' value='<?= $terminalCode ?>' /><br />
	merchantCode<input type='text' name='merchantCode' value='<?= $merchantCode ?>' /><br />
	redirectAddress<input type='text' name='redirectAddress' value='<?= $redirectAddress ?>' /><br />
	timeStamp<input type='text' name='timeStamp' value='<?= $timeStamp ?>' /><br />
	action<input type='text' name='action' value='<?= $action ?>' /><br />
	sign<input type='text' name='sign' value='<?= $result ?>' /><br />
	<input type='submit' name='submit' value='Checkout' />
</form>
  </body>
</html>
