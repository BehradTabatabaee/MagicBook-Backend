<?php

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

// Get the manga ID from the query string
$mangaID = $_GET['id'];

// Prepare and execute the SQL statement to get the manga information
$query = "SELECT * FROM Mangas WHERE mangaID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $mangaID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode('Manga not found');
    exit();
}

$manga = $result->fetch_assoc();

// Prepare and execute the SQL statement to get the volumes of the manga
$query = "SELECT * FROM Volumes WHERE mangaID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $mangaID);
$stmt->execute();
$volumes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Add the volumes to the manga information
$manga['volumes'] = $volumes;

// Send a response
echo json_encode($manga);
