<html>
    <body>
            <script language="javascript" type="text/javascript">

            var form = document.createElement("form");
            form.setAttribute("method", "POST");
            form.setAttribute("action", "https://pna.shaparak.ir/_ipgw_/payment/");
            form.setAttribute("target", "_self");
            var hiddenField = document.createElement("input");
            hiddenField.setAttribute("name", "token");
            hiddenField.setAttribute("value", {!! $token !!});
            form.appendChild(hiddenField);

            var hiddenField2 = document.createElement("input");
            hiddenField2.setAttribute("name", "language");
            hiddenField2.setAttribute("value", "fa");
            form.appendChild(hiddenField2);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

        </script>
    </body>
</html>


