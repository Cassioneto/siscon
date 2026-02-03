<?php
require_once '../config.php';

// OWASP A01: Broken Access Control
// Only catalogers can access this data
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'cataloger') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // OWASP A05: Injection - Use Prepared Statements
    // Search by name or phone
    $stmt = $pdo->prepare("SELECT id, name, phone FROM users WHERE role = 'patient' AND (name LIKE ? OR phone LIKE ?) LIMIT 10");
    $term = "%$query%";
    $stmt->execute([$term, $term]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Output is JSON encoded, safe from XSS here (context is JSON)
    echo json_encode($results);

} catch (PDOException $e) {
    // OWASP A10: Logging
    error_log("Search Error: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno']);
}
?>