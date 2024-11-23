<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
<title>Session contents</title>

<style>
body {
    font-family: "Verdana", Geneva, sans-serif;
}
</style>


</head>
<body>
    <pre>
<?php

print_r($_SESSION);

?>
    </pre>
</body></html>
</body>
</html>