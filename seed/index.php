<?php
    header('Content-type: application/json');
    require_once __DIR__ . '/../../vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    // connect to database
    $conn = new mysqli($_ENV['DB_SERVER'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME'], $_ENV['DB_PORT']);


    // exit if connection fails
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode("Connection failed: " . $conn->connect_error);
        exit();
    }

    // clear the database
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $table = $row[0];
        // delete each table
        $conn->query("DROP TABLE IF EXISTS $table");
    }

    // function to query the database, just to make the code a bit cleaner
    function createQuery($sqlQuery) {
        global $conn;
        // prepare and bind, to avoid sql injection
        $stmt = $conn->prepare($sqlQuery);
        if ($stmt === false) {
            http_response_code(500);
            echo json_encode("Error: " . $conn->error);
            exit();
        }
        $stmt->execute();
    }

    // seed data
    createQuery("CREATE TABLE Users (
        userID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userName VARCHAR(50) NOT NULL,
        firstName VARCHAR(50) NOT NULL,
        lastName VARCHAR(50) NOT NULL,
        userPassword VARCHAR(255) NOT NULL,
        email VARCHAR(50),
        picture VARCHAR(2083)
        )");

    createQuery("CREATE TABLE Admins (
        adminName VARCHAR(50) PRIMARY KEY,
        adminPassword VARCHAR(255) NOT NULL
        )");
    
    createQuery("CREATE TABLE Mangas (
        mangaID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        mangaName VARCHAR(50) NOT NULL,
        mangaPicture VARCHAR(2083),
        mangaVolumes INT UNSIGNED NOT NULL
        )");
    
    createQuery("CREATE TABLE Volumes (
        volumeID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        mangaID INT UNSIGNED,
        mangaName VARCHAR(50) NOT NULL,
        volumeNumber INT NOT NULL,
        volumeURL VARCHAR(2083) NOT NULL,
        FOREIGN KEY (mangaID) REFERENCES Mangas(mangaID)
        )");

    createQuery("INSERT INTO Admins (adminName, adminPassword) VALUES ('Behrad', '".password_hash('Tabatabaee', PASSWORD_DEFAULT)."')");

    createQuery("INSERT INTO Users (userName, firstName, lastName, userPassword) VALUES ('Behrad', 'Behrad', 'Tabatabaee', '".password_hash('Behrad', PASSWORD_DEFAULT)."')");
    
    createQuery("INSERT INTO Mangas (mangaName, mangaVolumes) VALUES ('Vagabond', 1)");

    createQuery("INSERT INTO Volumes (mangaName, mangaID, volumeNumber, volumeURL) 
    SELECT 'Vagabond', mangaID, 1, 'https://mangareader.storage.iran.liara.space/vol1.pdf' 
    FROM Mangas WHERE mangaName = 'Vagabond'");

    echo json_encode("Tables created and data added successfully");
?>