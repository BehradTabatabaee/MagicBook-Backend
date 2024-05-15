<?php
    header('Content-type: application/json');
    $servername = "monte-rosa.liara.cloud:31004";
    $username = "root";
    $password = "FxjBPV7NneR0Zm2ZjwDnSn7S";
    $dbname = "adoring_morse";

    // connect to database
    $conn = new mysqli($servername, $username, $password, $dbname);

    // exit if connection fails
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
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
        // exit if query fails
        if ($conn->query($sqlQuery) != TRUE) {
            die("Error : " . $conn->error);
        }
    }

    createQuery("CREATE TABLE Users (
        userID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userName VARCHAR(50) NOT NULL,
        firstName VARCHAR(50) NOT NULL,
        lastName VARCHAR(50) NOT NULL,
        userPassword VARCHAR(20) NOT NULL,
        email VARCHAR(50),
        picture VARCHAR(2083)
        )");

    createQuery("CREATE TABLE Admins (
        adminName VARCHAR(50) PRIMARY KEY,
        adminPassword VARCHAR(50) NOT NULL
        )");
    
    createQuery("CREATE TABLE Mangas (
        mangaID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        mangaName VARCHAR(50) NOT NULL,
        mangaPicture VARCHAR(2083),
        mangaVolumes INT UNSIGNED NOT NULL
        )");
    
    createQuery("CREATE TABLE Volumes (
        volumeID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        mangaID INT,
        mangaName VARCHAR(50) NOT NULL,
        volumeNumber INT NOT NULL,
        volumeURL VARCHAR(2083) NOT NULL,
        FOREIGN KEY (mangaID) REFERENCES Mangas(mangaID)
        )");

    createQuery("INSERT INTO Admins (adminName, adminPassword) VALUES ('Behrad', 'Tabatabaee')");

    createQuery("INSERT INTO Users (userName, firstName, lastName, userPassword) VALUES ('Behrad', 'Behrad', 'Tabatabaee', 'Behrad')");
    
    createQuery("INSERT INTO Mangas (mangaName, mangaVolumes) VALUES ('Vagabond', 1)");

    createQuery("INSERT INTO Volumes (mangaName, mangaID, volumeNumber, volumeURL) 
    SELECT 'Vagabond', mangaID, 1, 'https://mangareader.storage.iran.liara.space/vol1.pdf' 
    FROM Mangas WHERE mangaName = 'Vagabond'");

    echo json_encode("Tables created and data added successfully");
?>
