<html>
    <body>
            <script language="javascript" type="text/javascript">

            var form = document.createElement("form");
            form.setAttribute("method", "POST");
            form.setAttribute("action", "https://asan.shaparak.ir");
            form.setAttribute("target", "_self");
            var hiddenField = document.createElement("input");
            hiddenField.setAttribute("name", "RefId");
            hiddenField.setAttribute("value", {!! $RefId !!});
            form.appendChild(hiddenField);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

        </script>
    </body>
</html>


