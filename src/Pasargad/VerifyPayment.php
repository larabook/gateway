<?php 
	require_once("RSAProcessor.class.php"); 
	require_once ("parser.php");
	
	$fields = array(
						'MerchantCode' => '111111', 			//shomare ye moshtari e shoma.
						'TerminalCode' => '111111', 			//shomare ye terminal e shoma.
						'InvoiceNumber' => '1111',  			//shomare ye factor tarakonesh.
						'InvoiceDate' => '2014/02/18 11:43:46', //tarikh e tarakonesh.
						'amount' => '1000', 					//mablagh e tarakonesh. faghat adad.
						'TimeStamp' => date("Y/m/d H:i:s"), 	//zamane jari ye system.
						'sign' => '' 							//reshte ye ersali ye code shode. in mored automatic por mishavad. 
					);
	
	$processor = new RSAProcessor("certificate.xml",RSAKeyType::XMLFile);
	
	$data = "#". $fields['MerchantCode'] ."#". $fields['TerminalCode'] ."#". $fields['InvoiceNumber'] ."#". $fields['InvoiceDate'] ."#". $fields['amount'] ."#". $fields['TimeStamp'] ."#";
	$data = sha1($data,true);
	$data =  $processor->sign($data);
	$fields['sign'] =  base64_encode($data); // base64_encode 
	
	$sendingData =  "MerchantCode=". $merchantCode ."&TerminalCode=". $terminalCode ."&InvoiceNumber=". $invoiceNumber ."&InvoiceDate=". $invoiceDate ."&amount=". $amount ."&TimeStamp=". $timeStamp ."&sign=".$fields['sign'];
	$verifyresult = post2https($fields,'https://pep.shaparak.ir/VerifyPayment.aspx');
	$array = makeXMLTree($verifyresult);
	var_dump($array);
	echo("<br /><br /><h1>");
	echo $array["resultObj"]["verifyresult"];
	echo("</h1>")
?>

