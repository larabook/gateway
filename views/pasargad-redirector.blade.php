<html>
    <body>
        <script>
        	var form = document.createElement("FORM");
        	form.setAttribute("method", "POST");
        	form.setAttribute("action", "{{$url}}");
        	form.setAttribute("target", "_self");

            var invoiceNumber = document.createElement("input");
			invoiceNumber.setAttribute("name", "invoiceNumber");
			invoiceNumber.setAttribute("value", "{{$invoiceNumber}}");

            form.appendChild(invoiceNumber);

			var invoiceDate = document.createElement("input");
			invoiceDate.setAttribute("name", "invoiceDate");
			invoiceDate.setAttribute("value", "{{$invoiceDate}}");
            form.appendChild(invoiceDate);

			var amount = document.createElement("input");
			amount.setAttribute("name", "amount");
			amount.setAttribute("value", "{{$amount}}");
            form.appendChild(amount);

			var terminalCode = document.createElement("input");
			terminalCode.setAttribute("name", "terminalCode");
			terminalCode.setAttribute("value", "{{$terminalCode}}");
            form.appendChild(terminalCode);

			var merchantCode = document.createElement("input");
			merchantCode.setAttribute("name", "merchantCode");
			merchantCode.setAttribute("value", "{{$merchantCode}}");
            form.appendChild(merchantCode);

			var timeStamp = document.createElement("input");
			timeStamp.setAttribute("name", "timeStamp");
			timeStamp.setAttribute("value", "{{$timeStamp}}");
            form.appendChild(timeStamp);

			var action = document.createElement("input");
			action.setAttribute("name", "action");
			action.setAttribute("value", "{{$action}}");
            form.appendChild(action);

			var sign = document.createElement("input");
			sign.setAttribute("name", "sign");
			sign.setAttribute("value", "{{$sign}}");
            form.appendChild(sign);

			var redirectAddress = document.createElement("input");
			redirectAddress.setAttribute("name", "redirectAddress");
			redirectAddress.setAttribute("value", "{{$redirectUrl}}");
            form.appendChild(redirectAddress);

			document.body.appendChild(form);
            form.submit();
        	//document.write(form.outerHTML());
        	document.body.removeChild(form);
        </script>
    </body>
</html>
