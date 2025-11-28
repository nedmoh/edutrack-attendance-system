<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = connectDB();

    switch($method) {
        case 'GET':
          
            $stmt = $pdo->query("
                SELECT s.*, g.name as group_name 
                FROM attendance_sessions s 
                LEFT JOIN groups g ON s.group_id = g.id 
                ORDER BY s.session_date DESC, s.created_at DESC
            ");
            $sessions = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $sessions
            ]);
            break;

        case 'POST':
           
            $input = json_decode(file_get_contents('php://input'), true);
            
            $course_name = $input['course_name'] ?? '';
            $group_id = $input['group_id'] ?? 1;
            $session_date = $input['session_date'] ?? date('Y-m-d');
            $opened_by = $input['opened_by'] ?? 'System';
            
            $stmt = $pdo->prepare("
                INSERT INTO attendance_sessions (course_name, group_id, session_date, opened_by, status) 
                VALUES (?, ?, ?, ?, 'open')
            ");
            
            $stmt->execute([$course_name, $group_id, $session_date, $opened_by]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Session créée avec succès',
                'session_id' => $pdo->lastInsertId()
            ]);
            break;

        case 'PUT':
            
            $input = json_decode(file_get_contents('php://input'), true);
            $session_id = $input['session_id'] ?? 0;
            
            $stmt = $pdo->prepare("
                UPDATE attendance_sessions 
                SET status = 'closed' 
                WHERE id = ?
            ");
            
            $stmt->execute([$session_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Session fermée avec succès'
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Méthode non autorisée'
            ]);
            break;
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur base de données: ' . $e->getMessage()
    ]);
}
?>