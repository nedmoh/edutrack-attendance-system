<?php
require_once '../config/db_connect.php';

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_name = $_POST['course_name'] ?? '';
    $group_id = $_POST['group_id'] ?? '';
    $session_date = $_POST['session_date'] ?? '';
    $opened_by = $_POST['opened_by'] ?? '';
    
    try {
        $pdo = connectDB();
        
        // Vérifier si une session existe déjà pour cette date et ce groupe
        $check_stmt = $pdo->prepare("
            SELECT id FROM attendance_sessions 
            WHERE session_date = ? AND group_id = ? AND status = 'open'
        ");
        $check_stmt->execute([$session_date, $group_id]);
        
        if ($check_stmt->fetch()) {
            throw new Exception("Une session ouverte existe déjà pour cette date et ce groupe");
        }
        
        // Créer la nouvelle session
        $stmt = $pdo->prepare("
            INSERT INTO attendance_sessions (course_name, group_id, session_date, opened_by, status) 
            VALUES (?, ?, ?, ?, 'open')
        ");
        
        $stmt->execute([$course_name, $group_id, $session_date, $opened_by]);
        $session_id = $pdo->lastInsertId();
        
        $message = "✅ Session créée avec succès! ID Session: $session_id";
        $message_type = "success";
        
    } catch(PDOException $e) {
        $message = "❌ Erreur base de données: " . $e->getMessage();
        $message_type = "error";
    } catch(Exception $e) {
        $message = "❌ Erreur: " . $e->getMessage();
        $message_type = "error";
    }
}

// Récupérer les groupes
try {
    $pdo = connectDB();
    $groups_stmt = $pdo->query("SELECT * FROM groups");
    $groups = $groups_stmt->fetchAll();
} catch(PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une session - EduTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f4ff, #e2e8ff);
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        input, select { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e2e8f0; 
            border-radius: 8px; 
            font-size: 16px; 
        }
        .btn { 
            background: #4361ee; 
            color: white; 
            padding: 12px 24px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
        }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-calendar-plus"></i> Créer une session de présence</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Nom du cours:</label>
                <input type="text" name="course_name" value="<?php echo htmlspecialchars($_POST['course_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Groupe:</label>
                <select name="group_id" required>
                    <option value="">Sélectionnez un groupe</option>
                    <?php foreach($groups as $group): ?>
                        <option value="<?php echo $group['id']; ?>">
                            <?php echo htmlspecialchars($group['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Date de la session:</label>
                <input type="date" name="session_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Ouvert par (professeur):</label>
                <input type="text" name="opened_by" value="<?php echo htmlspecialchars($_POST['opened_by'] ?? ''); ?>" required>
            </div>
            
            <button type="submit" class="btn"><i class="fas fa-save"></i> Créer la session</button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="take_attendance.php">📋 Prendre la présence</a> | 
            <a href="../index.html">🏠 Accueil</a>
        </p>
    </div>
</body>
</html>