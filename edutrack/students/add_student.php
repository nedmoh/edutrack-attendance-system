<?php
require_once '../config/db_connect.php';

$message = null;
$message_type = null;
$attendance_link = null;
$groups = [];

try {
    $pdo = connectDB();
    $groups = $pdo->query("SELECT * FROM groups ORDER BY id")->fetchAll();
} catch (Exception $e) {
    $groups = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement du formulaire d'ajout d'étudiant
    $student_id = $_POST['student_id'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $course = $_POST['course'] ?? '';
    $group_id = $_POST['group_id'] ?? 1;

    try {
        $pdo = connectDB();

        $stmt = $pdo->prepare(
            "INSERT INTO students (student_id, first_name, last_name, email, course, group_id) VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([$student_id, $first_name, $last_name, $email, $course, $group_id]);

        $message = "✅ Étudiant $first_name $last_name ajouté avec succès!";
        $message_type = "success";

        // Try to find an open session for this group so the user can verify attendance
        try {
            $sess_stmt = $pdo->prepare("SELECT id FROM attendance_sessions WHERE group_id = ? AND status = 'open' ORDER BY session_date DESC LIMIT 1");
            $sess_stmt->execute([$group_id]);
            $sess_row = $sess_stmt->fetch(PDO::FETCH_ASSOC);
                if ($sess_row && isset($sess_row['id'])) {
                    // attendance page is one level up from students/
                    $attendance_link = "../attendance/take_attendance.php?session_id=" . $sess_row['id'];
                }
                // If we found an attendance page, redirect immediately so the user can verify the student
                if (!empty($attendance_link)) {
                    header('Location: ' . $attendance_link);
                    exit;
                }
        } catch (Exception $e) {
            // ignore — link is optional
        }

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
    <title>Ajouter un étudiant - EduTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reprenez le CSS de votre index.html */
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
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        input[type="text"], input[type="email"] {
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
            font-size: 16px;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-user-plus"></i> Ajouter un étudiant</h1>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
                <?php if (!empty($attendance_link) && $message_type === 'success'): ?>
                    <div style="margin-top:10px">
                        <a href="<?php echo $attendance_link; ?>" class="btn" style="background:#10b981;">Voir la liste de présence (session)</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="student_id">ID Étudiant:</label>
                <input type="text" id="student_id" name="student_id" required>
            </div>
            
            <div class="form-group">
                <label for="first_name">Prénom:</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Nom:</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email">
            </div>
            
            <div class="form-group">
                <label for="course">Cours:</label>
                <input type="text" id="course" name="course" required>
            </div>
            
            <div class="form-group">
                <label for="group_id">Groupe:</label>
                <select id="group_id" name="group_id" required>
                    <?php if (!empty($groups)): ?>
                        <?php foreach($groups as $g): ?>
                            <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="1">Groupe 1</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <button type="submit" class="btn">Ajouter l'étudiant</button>
        </form>
        
        <p style="margin-top: 20px;">
            <a href="../index.html">← Retour à l'accueil</a>
        </p>
    </div>
</body>
</html>
