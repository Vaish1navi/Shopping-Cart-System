<?php
header('Content-Type: application/json');
include('database/dbConnect.php'); // Database connection


$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$results = [];

if (!empty($query)) {
    $search_query = "%$query%";
    $stmt = $conn->prepare("SELECT id, name FROM products 
                           WHERE name LIKE ? AND status = 'active' 
                           LIMIT 5");
    $stmt->bind_param("s", $search_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

echo json_encode($results);
?>