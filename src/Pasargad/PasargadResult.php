<?php

namespace Larabookir\Gateway\Pasargad;


class PasargadResult
{
	public static function errorMessage($state) {
		$state = strtolower((string)$state);
		switch ($state) {
			case "canceled by user" :
				$message = _("Canceled By User");
				break;
			case "invalid amount" :
				$message = _("Invalid Amount");
				break;
			case "invalid transaction" :
				$message = _("Invalid Transaction");
				break;
			case "invalid card number" :
				$message = _("Invalid Card Number");
				break;
			case "no such issuer" :
				$message = _("No Such Issuer");
				break;
			case "expired card pick up" :
				$message = _("Expired Card Pick Up");
				break;
			case "allowable pin tries exceeded pick up" :
				$message = _("Allowable PIN Tries Exceeded Pick Up");
				break;
			case "incorrect pin" :
				$message = _("Incorrect PIN");
				break;
			case "exceeds withdrawal amount limit" :
				$message = _("Exceeds Withdrawal Amount Limit");
				break;
			case "transaction cannot be completed" :
				$message = _("Transaction Cannot Be Completed");
				break;
			case "response received too late" :
				$message = _("Response Received Too Late");
				break;
			case "suspected fraud pick up" :
				$message = _("Suspected Fraud Pick Up");
				break;
			case "no sufficient funds" :
				$message = _("No Sufficient Funds");
				break;
			case "issuer down slm" :
				$message = _("Issuer Down Slm");
				break;
			case "tme error" :
				$message = _("TME Error");
				break;
			// errorNumber
			case "-1" :
				$message = _("Internal Error");
				break;
			case "-3" :
				$message = _("TME Error");
				break;
			case "-4" :
				$message = _("‪Merchant Authentication Failed‬‬");
				break;
			case "-6" :
				$message = _("Transaction Refunded");
				break;
			case "-7" :
				$message = _("‫‪Transaction Id Empty");
				break;
			case "-8" :
				$message = _("‫‪Parameter is too long");
				break;
			case "-9" :
				$message = _("‫‪amount value is invalid");
				break;
			case "-10" :
				$message = _("‫‪Transaction Id Not Base64");
				break;
			case "-11" :
				$message = _("‫‪Parameter is too short");
				break;
			case "-12" :
				$message = _("‫‪amount value is invalid");
				break;
			case "-13" :
				$message = _("refund ‫‪amount value is invalid");
				break;
			case "-14" :
				$message = _("Transaction Id invalid");
				break;
			case "-15" :
				$message = _("refund ‫‪amount value is float!");
				break;
			case "-16" :
				$message = _("Internal Error");
				break;
			case "-17" :
				$message = _("refund ‫‪amount is not saman");
				break;
			case "-18" :
				$message = _("Merchant IP Invalid");
				break;

			case "refunded amount" :
				$message = _("Refunded Amount");
				break;

			default :
				$message = _("UNKOWN_ERROR");
				break;
		}

		return $message;
	}
}
