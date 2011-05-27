<?php
if(isset($_GET['page']) && $_GET['page'] == "support") {
?>
Support page content goes here
<?php
}
else if(isset($_GET['page']) && $_GET['page'] == "about") {
?>
About page content goes here
<?php
}
else {die("<h1>404 Not Found</h1>");}
?>