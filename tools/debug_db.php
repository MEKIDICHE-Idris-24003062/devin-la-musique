<?php
require __DIR__ . '/../src/bootstrap.php';

$pdo = App\Db::pdo();

$tracks = (int)$pdo->query('SELECT COUNT(*) FROM tracks')->fetchColumn();
$with = (int)$pdo->query("SELECT COUNT(*) FROM tracks WHERE preview_url IS NOT NULL AND preview_url != ''")->fetchColumn();

echo "tracks={$tracks}\n";
echo "with_preview={$with}\n";

$row = $pdo->query("SELECT preview_url, title, artist FROM tracks WHERE preview_url IS NOT NULL AND preview_url != '' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
var_export($row);
echo "\n";
