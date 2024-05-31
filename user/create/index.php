<?php

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode('Method not allowed');
    exit();
}

header('Content-type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

// load environment variables 
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Get the request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate the data
if (empty($data['userName']) || empty($data['firstName']) || empty($data['lastName']) || empty($data['userPassword']) || empty($data['email'])) {
    http_response_code(400);
    echo json_encode('Fill up the empty fields!');
    exit();
}

// connect to database
$conn = new mysqli($_ENV['DB_SERVER'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME'], $_ENV['DB_PORT']);

// exit if connection fails
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode("Connection failed: " . $conn->connect_error);
    exit();
}

// Check if the username and password are in the Users table
$query = "SELECT * FROM Users WHERE userName = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $data['userName']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 0) {
    http_response_code(401);
    echo json_encode('Username Already Exists!');
    exit();
}

$hashedPassword = password_hash($data['userPassword'], PASSWORD_DEFAULT);

$query = "INSERT INTO Users (userName, firstName, lastName, userPassword, email)
VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param('sssss', $data['userName'], $data['firstName'], $data['lastName'], $hashedPassword, $data['email']);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    http_response_code(500);
    echo json_encode('Error in user creation');
    exit();
}

echo json_encode(array("message" => "Registration Complete!"));

?>
