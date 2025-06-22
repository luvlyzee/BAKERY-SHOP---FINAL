<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
$host = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "newsletter_db";
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

$sql = "SELECT product_id, category, product_name, price, img FROM products";
$res = $conn->query($sql);

if (!$res) {
    http_response_code(500);
    echo json_encode(["error" => "Query failed: " . $conn->error]);
    exit;
}

$products = [
    "breads" => [],
    "pastries" => [],
    "cakes" => [],
    "cookies" => []
];

while ($row = $res->fetch_assoc()) {
    $cat = strtolower(trim($row['category']));
    if (!isset($products[$cat])) continue;
    $products[$cat][] = [
        "id"   => $row['product_id'],
        "name" => $row['product_name'],
        "price" => floatval($row['price']),
        "img" => $row['img']
    ];
}

echo json_encode($products);
$conn->close();
?>