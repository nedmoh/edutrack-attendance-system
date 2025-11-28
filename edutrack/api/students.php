<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = connectDB();

    switch($method) {
        case 'GET':
            
            $stmt = $pdo->query("
                SELECT 
                    s.*, 
                    g.name as group_name,
                    COUNT(CASE WHEN ar.present = 1 THEN 1 END) as participations,
                    COUNT(CASE WHEN ar.present = 0 THEN 1 END) as absences
                FROM students s 
                LEFT JOIN groups g ON s.group_id = g.id 
                LEFT JOIN attendance_records ar ON s.student_id = ar.student_id
                GROUP BY s.id
                ORDER BY s.last_name, s.first_name
            ");
            $students = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $students
            ]);
            break;

        case 'POST':
           
            $input = json_decode(file_get_contents('php://input'), true);
            
            $student_id = $input['student_id'] ?? '';
            $first_name = $input['first_name'] ?? '';
            $last_name = $input['last_name'] ?? '';
            $email = $input['email'] ?? '';
            $course = $input['course'] ?? '';
            $group_id = $input['group_id'] ?? 1;
            
            
            if (empty($student_id) || empty($first_name) || empty($last_name) || empty($course)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Tous les champs obligatoires doivent être remplis'
                ]);
                break;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO students (student_id, first_name, last_name, email, course, group_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$student_id, $first_name, $last_name, $email, $course, $group_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Étudiant ajouté avec succès',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'PUT':
           
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $_GET['id'] ?? 0;
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID manquant']);
                break;
            }
            
            $student_id = $input['student_id'] ?? '';
            $first_name = $input['first_name'] ?? '';
            $last_name = $input['last_name'] ?? '';
            $email = $input['email'] ?? '';
            $course = $input['course'] ?? '';
            $group_id = $input['group_id'] ?? 1;
            
            $stmt = $pdo->prepare("
                UPDATE students 
                SET student_id = ?, first_name = ?, last_name = ?, email = ?, course = ?, group_id = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$student_id, $first_name, $last_name, $email, $course, $group_id, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Étudiant mis à jour avec succès'
            ]);
            break;

        case 'DELETE':
            
            $id = $_GET['id'] ?? 0;
            
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID manquant']);
                break;
            }
            
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Étudiant supprimé avec succès'
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