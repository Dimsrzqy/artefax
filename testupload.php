<?php
$dir = __DIR__ . '/adminArtefax/img/upload/';
echo "Folder: $dir<br>";
echo "Exists: " . (is_dir($dir) ? 'Yes' : 'No') . "<br>";
echo "Writable: " . (is_writable($dir) ? 'Yes' : 'No') . "<br>";
?>