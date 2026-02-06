<?php
/**
 * Fix stored preview URLs in DB by upgrading http:// to https://
 *
 * Usage:
 *   php tools/fix_previews.php
 */

require __DIR__ . '/../src/bootstrap.php';

$pdo = App\Db::pdo();

$beforeHttp = (int)$pdo->query("SELECT COUNT(*) FROM tracks WHERE preview_url LIKE 'http://%'")->fetchColumn();
$beforeHttps = (int)$pdo->query("SELECT COUNT(*) FROM tracks WHERE preview_url LIKE 'https://%'")->fetchColumn();

$stmt = $pdo->prepare("UPDATE tracks SET preview_url = REPLACE(preview_url, 'http://', 'https://') WHERE preview_url LIKE 'http://%'");
$stmt->execute();
$changed = $stmt->rowCount();

$afterHttp = (int)$pdo->query("SELECT COUNT(*) FROM tracks WHERE preview_url LIKE 'http://%'")->fetchColumn();
$afterHttps = (int)$pdo->query("SELECT COUNT(*) FROM tracks WHERE preview_url LIKE 'https://%'")->fetchColumn();

echo "Before: http={$beforeHttp}, https={$beforeHttps}\n";
echo "Changed rows: {$changed}\n";
echo "After:  http={$afterHttp}, https={$afterHttps}\n";
