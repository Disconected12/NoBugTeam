<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = get_db_connection();
echo "=== 1. Check sample data from clubs ===\n";
$stmt = $db->query("SELECT id, name, summary FROM clubs LIMIT 3");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "ID " . $row["id"] . ": " . $row["name"] . "\n";
    echo "   Summary: " . $row["summary"] . "\n";
}

echo "\n=== 2. Check categories ===\n";
$stmt = $db->query("SELECT id, name FROM categories LIMIT 5");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "Cat " . $row["id"] . ": " . $row["name"] . "\n";
}

echo "\n=== 3. Check HTML response from index.php ===\n";
$html = file_get_contents("http://localhost:8080/cbl/index.php");
if (preg_match_all('/<h3[^>]*class="club-card-title"[^>]*>(.*?)<\/h3>/s', $html, $matches)) {
    foreach (array_slice($matches[1], 0, 3) as $m) {
        echo "Card title in HTML: " . trim(strip_tags($m)) . "\n";
    }
}