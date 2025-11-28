<?php
require_once '../config/db_connect.php';

$session_id = $_GET['id'] ?? 0;
$message = "";
$message_type = "";

if ($session_id) {
    try {
        $pdo = connectDB();
        
        // Vérifier si la session existe
        $check_stmt = $pdo->prepare("SELECT id, course_name FROM attendance_sessions WHERE id = ?");
        $check_stmt->execute([$session_id]);
        $session = $check_stmt->fetch();
        
        if (!$session) {
            throw new Exception("Session non trouvée");
        }
        
        // Fermer la session
        $update_stmt = $pdo->prepare("UPDATE attendance_sessions SET status = 'closed' WHERE id = ?");
        $update_stmt->execute([$session_id]);
        
        $message = "✅ Session '{$session['course_name']}' fermée avec succès!";
        $message_type = "success";
        
    } catch(PDOException $e) {
        $message = "❌ Erreur base de données: " . $e->getMessage();
        $message_type = "error";
    } catch(Exception $e) {
        $message = "❌ Erreur: " . $e->getMessage();
        $message_type = "error";
    }
} else {
    $message = "❌ ID session non spécifié";
    $message_type = "error";
}

// Redirection
header("Location: list_sessions.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
exit();
?>