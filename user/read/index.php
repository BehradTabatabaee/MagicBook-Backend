<?php

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode('Method not allowed');
    exit();
}

if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = $headers['authorization'];
        }
    }

    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        http_response_code(401);
        echo json_encode('No Authorization header');
        exit();
    }
}

header('Content-type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
    $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode('Invalid JWT');
    exit();
}

// Check if the username and password are in the Users table
$query = "SELECT * FROM Users WHERE userName = ?";
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
if (!password_verify($decoded->password, $row['userPassword'])) {
    http_response_code(401);
    echo json_encode('Unauthorized');
    exit();
}

$query = "SELECT userID, userPassword, firstName, lastName, userName, email, picture FROM Users WHERE userName = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $decoded->username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode('User not found');
    exit();
}

$user = $result->fetch_assoc();

echo json_encode($user);
?>