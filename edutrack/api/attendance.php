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
                SELECT 
                    s.student_id,
                    s.first_name,
                    s.last_name, 
                    s.course,
                    s.email,
                    g.name as group_name,
                    COUNT(CASE WHEN ar.present = 1 THEN 1 END) as participations,
                    COUNT(CASE WHEN ar.present = 0 THEN 1 END) as absences,
                    (SELECT COUNT(*) FROM attendance_records WHERE student_id = s.student_id) as total_sessions
                FROM students s
                LEFT JOIN groups g ON s.group_id = g.id
                LEFT JOIN attendance_records ar ON s.student_id = ar.student_id
                GROUP BY s.id
                ORDER BY s.last_name, s.first_name
            ");
            $attendance = $stmt->fetchAll();
            
            
            $formatted_data = [];
            foreach ($attendance as $student) {
                $formatted_data[] = [
                    'id' => $student['student_id'],
                    'lastName' => $student['last_name'],
                    'firstName' => $student['first_name'],
                    'course' => $student['course'],
                    'email' => $student['email'],
                    'group' => $student['group_name'],
                    'absences' => (int)$student['absences'],
                    'participations' => (int)$student['participations'],
                    'totalSessions' => (int)$student['total_sessions']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $formatted_data
            ]);
            break;

        case 'POST':
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $session_id = $input['session_id'] ?? 0;
            $student_id = $input['student_id'] ?? '';
            $session_number = $input['session_number'] ?? 1;
            $present = $input['present'] ?? false;
            $participated = $input['participated'] ?? false;
            
            
            $check_stmt = $pdo->prepare("
                SELECT id FROM attendance_records 
                WHERE session_id = ? AND student_id = ? AND session_number = ?
            ");
            $check_stmt->execute([$session_id, $student_id, $session_number]);
            
            if ($check_stmt->fetch()) {
                // Mise à jour
                $update_stmt = $pdo->prepare("
                    UPDATE attendance_records 
                    SET present = ?, participated = ?
                    WHERE session_id = ? AND student_id = ? AND session_number = ?
                ");
                $update_stmt->execute([$present, $participated, $session_id, $student_id, $session_number]);
            } else {
                // Insertion
                $insert_stmt = $pdo->prepare("
                    INSERT INTO attendance_records (session_id, student_id, session_number, present, participated) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insert_stmt->execute([$session_id, $student_id, $session_number, $present, $participated]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Présence enregistrée avec succès'
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
