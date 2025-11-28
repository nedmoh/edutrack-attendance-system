<?php
require_once 'config/db_connect.php';

try {
    $pdo = connectDB();
    
    echo "<h1>Configuration de la base de données EduTrack</h1>";
    
    // 1. Table des groupes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Table 'groups' créée<br>";
    
    // 2. Table des étudiants
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(20) UNIQUE NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(150),
            course VARCHAR(100) NOT NULL,
            group_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id)
        )
    ");
    echo "✅ Table 'students' créée<br>";
    
    // 3. Table des sessions de présence
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendance_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_name VARCHAR(100) NOT NULL,
            group_id INT,
            session_date DATE NOT NULL,
            opened_by VARCHAR(100),
            status ENUM('open', 'closed') DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id)
        )
    ");
    echo "✅ Table 'attendance_sessions' créée<br>";
    
    // 4. Table des enregistrements de présence
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendance_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT,
            student_id VARCHAR(20) NOT NULL,
            session_number INT NOT NULL,
            present BOOLEAN DEFAULT FALSE,
            participated BOOLEAN DEFAULT FALSE,
            record_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES attendance_sessions(id),
            FOREIGN KEY (student_id) REFERENCES students(student_id)
        )
    ");
    echo "✅ Table 'attendance_records' créée<br>";
    
    // 5. Insérer des groupes de test
    $pdo->exec("
        INSERT IGNORE INTO groups (id, name) VALUES 
        (1, 'Groupe A'),
        (2, 'Groupe B'),
        (3, 'Groupe C')
    ");
    echo "✅ Groupes de test insérés<br>";
    
    // 6. Insérer des étudiants de test
    $pdo->exec("
        INSERT IGNORE INTO students (student_id, first_name, last_name, email, course, group_id) VALUES 
        ('1001', 'Emma', 'Smith', 'emma.smith@email.com', 'Mathematics', 1),
        ('1002', 'Michael', 'Johnson', 'michael.johnson@email.com', 'Science', 1),
        ('1003', 'Sophia', 'Williams', 'sophia.williams@email.com', 'History', 2),
        ('1004', 'James', 'Brown', 'james.brown@email.com', 'Mathematics', 2),
        ('1005', 'Olivia', 'Davis', 'olivia.davis@email.com', 'Science', 3)
    ");
    echo "✅ Étudiants de test insérés<br>";
    
    echo "<h2 style='color: green;'>🎉 Base de données configurée avec succès !</h2>";
    echo "<p><a href='index.html'>Accéder à l'application</a></p>";
    
} catch(PDOException $e) {
    echo "<h2 style='color: red;'>❌ Erreur lors de la configuration</h2>";
    echo "<p>Erreur: " . $e->getMessage() . "</p>";
}
?>