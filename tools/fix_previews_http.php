<?php
/**
 * Downgrade stored preview URLs in DB by converting https:// to http://
 *
 * Usage:
 *   php tools/fix_previews_http.php
 */

require __DIR__ . '/../src/bootstrap.php';

$pdo = App\Db::pdo();

$beforeHttp = (int)$pdo->query("SELECT COUNT(*) FROM tracks WHERE preview_url LIKE 'http://%'")->fetchColumn();
$beforeHttps = (int)$pdo->query("SELECT COUNT(*) FROM tracks WHERE preview_url LIKE 'https://%'")->fetchColumn();

$stmt = $pdo->prepare("UPDATE tracks SET preview_url = REPLACE(preview_url, 'https://', 'http://') WHERE preview_url LIKE 'https://%'");
$stmt->execute();
$changed = $stmt->rowCount();

$afterHttp = (int)$pdo->query("SELECT COUNT(*) FROM tracks WHERE preview_url LIKE 'http://%'")->fetchColumn();
$afterHttps = (int)$pdo->query("SELECT COUNT(*) FROM tracks WHERE preview_url LIKE 'https://%'")->fetchColumn();

echo "Before: http={$beforeHttp}, https={$beforeHttps}\n";
echo "Changed rows: {$changed}\n";
echo "After:  http={$afterHttp}, https={$afterHttps}\n";
