<?php
echo "<h2>Files in your current directory:</h2>";
$files = scandir(_DIR_);
echo "<ul>";
foreach($files as $file) {
    if(!is_dir($file)) {
        echo "<li>" . $file . "</li>";
    }
}
echo "</ul>";
?>