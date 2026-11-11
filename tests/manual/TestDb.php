<?php
require_once(__DIR__ . "/../../resources/php/class/Database.php");

try {
    $database = Database::getInstance();

    echo "Connection successful\n";

    // Testing the database query
    $sql = "SELECT * FROM UserRoles";
    
    echo "Query successful\n";

    // Testing the database result
    $result = $database->query($sql);
    foreach ($result as $row) {
        echo "\n";
        echo "Username: " . $row["UserID"] . " - Role: " . $row["UserRole"] . "\n";
        echo "Company Name: " . $row["CompanyName"] . "\n";
        echo "Contact Name: " . $row["contactName"] . "\n";
        echo "Surname: " . $row["Surname"] . "\n";
        echo "password: " . $row["password"] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
