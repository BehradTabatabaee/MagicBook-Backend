<?php

// Check if the request method is PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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

// Start a transaction for updating a manga
$conn->begin_transaction();

try {
    // Get the request data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate the data
    if (empty($data['mangaName']) || empty($data['mangaVolumes']) || empty($data['volumeNumber']) || empty($data['volumeURL'])) {
        throw new Exception('Invalid data');
    }

    // Prepare and execute the SQL statement for the Mangas table
    $query = "UPDATE Mangas SET mangaName = ?, mangaPicture = ?, mangaVolumes = ? WHERE mangaName = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssis', $data['mangaName'], $data['mangaPicture'], $data['mangaVolumes'], $data['mangaName']);
    $stmt->execute();

    // Prepare and execute the SQL statement for the Volumes table
    $query = "UPDATE Volumes SET volumeNumber = ?, volumeURL = ? WHERE mangaName = ? AND volumeNumber = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iisi', $data['volumeNumber'], $data['volumeURL'], $data['mangaName'], $data['volumeNumber']);
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
