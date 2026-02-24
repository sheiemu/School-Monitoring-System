<?php
session_start();
session_unset();    
session_destroy();  

// Direct home page-e niye jabe
header("Location: index.php"); 
exit();
?>