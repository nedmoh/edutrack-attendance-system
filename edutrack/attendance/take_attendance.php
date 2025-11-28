<?php
require_once '../config/db_connect.php';

$session_id = $_GET['session_id'] ?? 0;

if (!$session_id) {
    die("ID session non spécifié");
}

try {
    $pdo = connectDB();
    
    // Récupérer les infos de la session
    $session_stmt = $pdo->prepare("
        SELECT s.*, g.name as group_name 
        FROM attendance_sessions s 
        LEFT JOIN groups g ON s.group_id = g.id 
        WHERE s.id = ? AND s.status = 'open'
    ");
    $session_stmt->execute([$session_id]);
    $session = $session_stmt->fetch();
    
    if (!$session) {
        die("Session non trouvée ou déjà fermée");
    }
    
    // Récupérer les étudiants du groupe
    $students_stmt = $pdo->prepare("
        SELECT * FROM students 
        WHERE group_id = ? 
        ORDER BY last_name, first_name
    ");
    $students_stmt->execute([$session['group_id']]);
    $students = $students_stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Traitement de l'envoi du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendances = $_POST['attendance'] ?? [];
    
    try {
        $pdo = connectDB();
        
        // Pour chaque étudiant, enregistrer la présence
        foreach ($students as $student) {
            $is_present = isset($attendances[$student['id']]) ? 1 : 0;
            
            // Vérifier si un enregistrement existe déjà
            $check_stmt = $pdo->prepare("
                SELECT id FROM attendance_records 
                WHERE session_id = ? AND student_id = ? AND session_number = 1
            ");
            $check_stmt->execute([$session_id, $student['student_id']]);
            
            if ($check_stmt->fetch()) {
                // Mise à jour
                $update_stmt = $pdo->prepare("
                    UPDATE attendance_records 
                    SET present = ? 
                    WHERE session_id = ? AND student_id = ? AND session_number = 1
                ");
                $update_stmt->execute([$is_present, $session_id, $student['student_id']]);
            } else {
                // Insertion
                $insert_stmt = $pdo->prepare("
                    INSERT INTO attendance_records (session_id, student_id, session_number, present) 
                    VALUES (?, ?, 1, ?)
                ");
                $insert_stmt->execute([$session_id, $student['student_id'], $is_present]);
            }
        }
        
        $message = "✅ Présence enregistrée avec succès!";
        $message_type = "success";
        
    } catch(PDOException $e) {
        $message = "❌ Erreur: " . $e->getMessage();
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prendre la présence - EduTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f4ff, #e2e8ff);
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        .session-info {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4361ee;
        }
        .student-list {
            margin-top: 20px;
        }
        .student-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        .student-info {
            flex: 1;
        }
        .attendance-checkbox {
            width: 20px;
            height: 20px;
            margin-right: 15px;
        }
        .btn {
            background: #4361ee;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 20px;
        }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-clipboard-check"></i> Prendre la présence</h1>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="session-info">
            <h3>Session: <?php echo htmlspecialchars($session['course_name']); ?></h3>
            <p><strong>Groupe:</strong> <?php echo htmlspecialchars($session['group_name']); ?></p>
            <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($session['session_date'])); ?></p>
            <p><strong>Professeur:</strong> <?php echo htmlspecialchars($session['opened_by']); ?></p>
        </div>
        
        <form method="POST">
            <div class="student-list">
                <h3>Liste des étudiants</h3>
                
                <?php foreach($students as $student): ?>
                <div class="student-item">
                    <input type="checkbox" 
                           class="attendance-checkbox" 
                           name="attendance[<?php echo $student['id']; ?>]" 
                           value="1" 
                           id="student_<?php echo $student['id']; ?>">
                    
                    <div class="student-info">
                        <label for="student_<?php echo $student['id']; ?>" style="cursor: pointer;">
                            <strong><?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?></strong>
                            (ID: <?php echo htmlspecialchars($student['student_id']); ?>)
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Enregistrer la présence
            </button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="list_sessions.php">← Retour aux sessions</a> | 
            <a href="../index.html">🏠 Accueil</a>
        </p>
    </div>
</body>
</html>