<?php

/**
 * @author www.softiran.org
 * @copyright 2016
 */
//// User complet this value : ////
$MerchantId = 'A4CA';
$admin_email = 'aa@aa.aa';
$sha1Key = '22338240992352910814917221751200141041845518824222260';
///////////////////////////////////
session_start();
$Err = '';
if($_POST['action'] == 'pay')
{

    if(intval($_POST['PayAmount']) >= 1000)
    {
        if(!empty($_POST['fullname']))
        {
            $_SESSION['merchantId'] = $MerchantId;
            $_SESSION['sha1Key'] = $sha1Key;
            $_SESSION['admin_email'] = $admin_email;
            $_SESSION['amount'] =$_POST['PayAmount'] ;
            $_SESSION['PayOrderId'] =$_POST['PayOrderId'];
            $_SESSION['fullname'] =$_POST['fullname'];
            $_SESSION['email'] =$_POST['email'];
            $revertURL = 'http://'.$_SERVER[HTTP_HOST].dirname($_SERVER[PHP_SELF]).'/back.php';

            $client = new SoapClient('https://ikc.shaparak.ir/XToken/Tokens.xml', array('soap_version'   => SOAP_1_1));

            $params['amount'] =  $_SESSION['amount'];
            $params['merchantId'] = $MerchantId;
            $params['invoiceNo'] = $_POST['PayOrderId'];
            $params['paymentId'] = $_POST['PayOrderId'];
            $params['specialPaymentId'] = $_POST['PayOrderId'];
            $params['revertURL'] = $revertURL;
            $params['description'] = "";
            $result = $client->__soapCall("MakeToken", array($params));
            $_SESSION['token'] = $result->MakeTokenResult->token;
            $data['token'] = $_SESSION['token'];
            $data['merchantId'] = $_SESSION['merchantId'];
            redirect_post('https://ikc.shaparak.ir/TPayment/Payment/index',$data);
        }
        else
        {
            $Err .='نام را وارد کنید<br/>';
        }
    }else
    {
        $Err .='مبلغ صحیح نیست <br/>';
    }

}
function redirect_post($url, array $data)
{

    echo '<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<title>در حال اتصال ...</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
	#main {
	    background-color: #F1F1F1;
	    border: 1px solid #CACACA;
	    height: 90px;
	    left: 50%;
	    margin-left: -265px;
	    position: absolute;
	    top: 200px;
	    width: 530px;
	}
	#main p {
	    color: #757575;
	    direction: rtl;
	    font-family: Arial;
	    font-size: 16px;
	    font-weight: bold;
	    line-height: 27px;
	    margin-top: 30px;
	    padding-right: 60px;
	    text-align: right;
	}
    </style>
        <script type="text/javascript">
            function closethisasap() {
                document.forms["redirectpost"].submit();
            }
        </script>
    </head>
    <body onload="closethisasap();">';
    echo '<form name="redirectpost" method="post" action="'.$url.'">';

    if ( !is_null($data) ) {
        foreach ($data as $k => $v) {
            echo '<input type="hidden" name="' . $k . '" value="' . $v . '"> ';
        }
    }

    echo' </form><div id="main">
<p>درحال اتصال به درگاه بانک</p></div>
    </body>
    </html>';

    exit;
}
?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>پرداخت ساده ایرانکیش</title>
    <style>
        .textbox{
            font:9pt Tahoma;
        }
        table tr td{
            font:9pt Tahoma;
        }
    </style>
</head>
<body style="background-color: #efefef;">
<center>
    <form action="" method="POST">
        <input type="hidden" class="textbox" name="action" id="action" value="pay"/>
        <div style="background-color: #bbe1ff;text-align: center;padding:10px;margin-top:10%;">
            <font color="red" style="font-family: Tahoma;font-size: 13px;"><?php echo $Err; ?></font>
            <table style="background-color: #fff;" align="center">
                <tr>
                    <td colspan="2">
                        <center>
                            <img src="irankish.jpg" />
                            <br />
                        </center>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">
                        <input type="text" class="textbox" name="fullname" id="fullname" value="<?php echo $_POST['fullname']; ?>"  style="text-align:right;" />
                    </td>
                    <td>نام پرداخت کننده</td>
                </tr>
                <tr>
                    <td style="text-align: right;">
                        <input type="text" class="textbox" name="PayOrderId" id="PayOrderId" value="<?php echo time(); ?>" readonly="readonly"/>
                    </td>
                    <td>شماره سفارش</td>
                </tr>
                <tr>
                    <td style="direction: rtl;text-align: right;">
                        <input type="text" class="textbox" name="PayAmount" id="PayAmount" value="<?php echo $_POST['PayAmount']; ?>" style="text-align:left;" />
                        &nbsp;ریال
                    </td>
                    <td>مبلغ</td>
                </tr>
                <tr>
                    <td style="text-align: right;"><input type="email" class="textbox" name="email" value="<?php echo $_POST['email']; ?>" id="email"/> </td>
                    <td>ایمیل</td>
                </tr>
                <tr>
                    <td style="text-align: right;direction:rtl;">
                        <input type="submit" class="textbox" value="پرداخت آنلاین" />
                        <input type="button" onclick="window.history.back();" class="textbox" value="انصراف" />
                    </td>
                    <td>&nbsp; </td>
                </tr>
            </table>
        </div>
    </form>
    <a href="http://www.softiran.org" target="_blank" title="softiran">www.softiran.org</a>
</center>
<!--//################################## softiran.org ###################################### //-->

</body>
</html>


<html>
<body>
<script>
    var form = document.createElement("form");
    form.setAttribute("method", "POST");
    form.setAttribute("action", "{!! $gateUrl !!}");
    form.setAttribute("target", "_self");

    var params = {
        Amount: '{{$amount}}',
    };

    for(var key in params){

        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("name", key);
        hiddenField.setAttribute("value", params[key]);

        form.appendChild(hiddenField);
    }


    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
</script>
</body>
</html>

