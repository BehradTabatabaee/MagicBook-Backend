<?php

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode('Method not allowed');
    exit();
}

header('Content-type: application/json');
require_once __DIR__ . '/../../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// load environment variables 
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../..');
$dotenv->load();

// connect to database
$conn = new mysqli($_ENV['DB_SERVER'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME'], $_ENV['DB_PORT']);

// exit if connection fails
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode("Connection failed: " . $conn->connect_error);
    exit();
}

// Get the request data
$data = json_decode(file_get_contents('php://input'), true);

// Check if the username and password are in the Users table
$query = "SELECT * FROM Users WHERE userName = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $data['username']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode('Invalid credentials');
    exit();
}

$row = $result->fetch_assoc();
if (!password_verify($data['password'], $row['userPassword'])) {
    http_response_code(401);
    echo json_encode('Invalid credentials');
    exit();
}

// Create a JWT
$jwt = JWT::encode(['username' => $data['username'], 'password' => $data['password']], $_ENV['JWT_SECRET'], 'HS256');

// Send a response
echo json_encode(['jwt' => $jwt]);
