<?php
// Connexion à la base de données
function connectDB() {
    // Inclure la configuration
    require_once __DIR__ . '/config.php';
    
    try {
        // D'abord se connecter sans base de données
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer la base si elle n'existe pas
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        $pdo->exec("USE " . DB_NAME);
        
        return $pdo;
        
    } catch(PDOException $e) {
        // Message d'erreur plus détaillé pour le débogage
        $error_message = "[" . date('Y-m-d H:i:s') . "] Erreur DB: " . $e->getMessage() . "\n";
        file_put_contents(__DIR__ . '/../error_log.txt', $error_message, FILE_APPEND);
        
        die("Erreur de connexion. Vérifiez que MySQL est démarré dans XAMPP.");
    }
}
?>