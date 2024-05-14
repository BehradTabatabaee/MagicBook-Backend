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
    function createQuery(sqlQuery) {
        // exit if query fails
        if ($conn->query($sql) != TRUE) {
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
        )")

    createQuery("CREATE TABLE Admins (
        adminName VARCHAR(50) PRIMARY KEY,
        adminPassword VARCHAR(50) NOT NULL,
        )")
    
    createQuery("CREATE TABLE Mangas (
        mangaID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        mangaName VARCHAR(50) NOT NULL,
        manga VARCHAR(50) NOT NULL,
        )")

    echo json_encode("Tables created and data inserted successfully");
?>