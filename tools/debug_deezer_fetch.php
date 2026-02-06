<?php
$url = 'https://api.deezer.com/chart/16/tracks?limit=1';
$j = @file_get_contents($url);
if ($j === false) {
    echo "FAIL\n";
    $e = error_get_last();
    var_export($e);
    echo "\n";
    exit(1);
}
echo substr($j, 0, 200) . "\n";
