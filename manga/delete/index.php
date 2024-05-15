<?php

// Check if the request method is DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode('Method not allowed');
    exit();
}

header('Content-type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

// load environment variables 
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// connect to database
$conn = new mysqli($_ENV['DB_SERVER'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME'], $_ENV['DB_PORT']);

// exit if connection fails
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode("Connection failed: " . $conn->connect_error);
    exit();
}

// Get the JWT from the Authorization header
$jwt = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);

// Decode the JWT
try {
    $decoded = JWT::decode($jwt, $_ENV['JWT_SECRET'], array('HS256'));
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode('Invalid JWT');
    exit();
}

// Check if the username and password are in the Admins table
$query = "SELECT * FROM Admins WHERE adminName = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $decoded->username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode('Unauthorized');
    exit();
}

$row = $result->fetch_assoc();
if (!password_verify($decoded->password, $row['adminPassword'])) {
    http_response_code(401);
    echo json_encode('Unauthorized');
    exit();
}

// Get the manga ID from the query string
$mangaID = $_GET['id'];

// Start a transaction for deleting a manga
$conn->begin_transaction();

try {
    // Prepare and execute the SQL statement for the Volumes table
    $query = "DELETE FROM Volumes WHERE mangaID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $mangaID);
    $stmt->execute();

    // Prepare and execute the SQL statement for the Mangas table
    $query = "DELETE FROM Mangas WHERE mangaID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $mangaID);
    $stmt->execute();

    // Commit the transaction
    $conn->commit();

    // Send a response
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    // An error occurred; rollback the transaction
    $conn->rollback();

    http_response_code(500);
    echo json_encode('Error: ' . $e->getMessage());
}
