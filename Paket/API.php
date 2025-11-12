<?php
include 'config/koneksi.php'; // Pastikan file ini ada
$search = trim($_GET['q'] ?? '');

if (strlen($search) >= 2) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, price FROM products 
                              WHERE name LIKE ? OR description LIKE ? 
                              LIMIT 10");
        $searchTerm = "%{$search}%";
        $stmt->execute([$searchTerm, $searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($results);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Search failed']);
    }
} else {
    echo json_encode([]);
}