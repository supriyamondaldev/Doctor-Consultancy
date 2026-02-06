<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
define('DB_NAME', 'doctor_consultation');

// Create connection
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Create database and tables if they don't exist
function initializeDatabase() {
    try {
        // Connect without database first
        $pdo = new PDO(
            "mysql:host=" . DB_HOST,
            DB_USER,
            DB_PASS,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        $pdo->exec("USE " . DB_NAME);
        
        // Create appointments table
        $sql = "CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            doctor VARCHAR(50) NOT NULL,
            consultation_type VARCHAR(20) NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            symptoms TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        
        // Create doctors table
        $sql = "CREATE TABLE IF NOT EXISTS doctors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            specialization VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            rating DECIMAL(2,1) DEFAULT 0.0,
            experience_years INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        
        // Insert sample doctors if table is empty
        $count = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
        if ($count == 0) {
            $doctors = [
                ['Dr. John Smith', 'Cardiologist', 'john.smith@mediconsult.com', '+1-555-0101', 4.9, 15],
                ['Dr. Sarah Johnson', 'Pediatrician', 'sarah.johnson@mediconsult.com', '+1-555-0102', 4.8, 12],
                ['Dr. Michael Brown', 'Neurologist', 'michael.brown@mediconsult.com', '+1-555-0103', 4.9, 18],
                ['Dr. Emily Davis', 'General Practitioner', 'emily.davis@mediconsult.com', '+1-555-0104', 4.7, 10]
            ];
            
            $stmt = $pdo->prepare("INSERT INTO doctors (name, specialization, email, phone, rating, experience_years) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($doctors as $doctor) {
                $stmt->execute($doctor);
            }
        }
        
        return true;
    } catch(PDOException $e) {
        die("Database initialization failed: " . $e->getMessage());
    }
}

// Initialize database when this file is included
initializeDatabase();
?>