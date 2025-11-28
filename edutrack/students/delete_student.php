<?php
require_once '../config/db_connect.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $pdo = connectDB();
        
        // Récupérer le nom avant suppression pour le message
        $stmt = $pdo->prepare("SELECT first_name, last_name FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $student = $stmt->fetch();
        
        // Supprimer l'étudiant
        $delete_stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $delete_stmt->execute([$id]);
        
        $message = "✅ Étudiant " . ($student['first_name'] ?? '') . " " . ($student['last_name'] ?? '') . " supprimé avec succès!";
        
    } catch(PDOException $e) {
        $message = "❌ Erreur: " . $e->getMessage();
    }
} else {
    $message = "❌ ID étudiant non spécifié";
}

// Redirection vers la liste
header("Location: list_students.php?message=" . urlencode($message));
exit();
?>