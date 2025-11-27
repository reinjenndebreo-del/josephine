<?php
require_once("config.php");

// Create menu_items table if not exists
$sql = "CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(100) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($connection->query($sql) === TRUE) {
    echo "Table menu_items created or already exists successfully\n";
} else {
    echo "Error creating table: " . $connection->error . "\n";
}

// Check if table exists and show its structure
$result = $connection->query("DESCRIBE menu_items");
if ($result) {
    echo "\nTable structure:\n";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error checking table structure: " . $connection->error;
}

// Ensure 'image' column exists for existing installations
$colCheck = $connection->query("SHOW COLUMNS FROM menu_items LIKE 'image'");
if ($colCheck && $colCheck->num_rows === 0) {
    $alter = "ALTER TABLE menu_items ADD COLUMN image VARCHAR(255) DEFAULT NULL";
    if ($connection->query($alter) === TRUE) {
        echo "\nColumn 'image' added to menu_items table.\n";
    } else {
        echo "\nFailed to add 'image' column: " . $connection->error . "\n";
    }
} else {
    echo "\nColumn 'image' already exists.\n";
}

$connection->close();