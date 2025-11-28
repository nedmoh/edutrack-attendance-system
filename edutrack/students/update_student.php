<?php
require_once '../config/db_connect.php';

$id = $_GET['id'] ?? 0;

try {
    $pdo = connectDB();
    
    // Récupérer l'étudiant
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        die("Étudiant non trouvé");
    }
    
    // Traitement de la mise à jour
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $student_id = $_POST['student_id'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $course = $_POST['course'] ?? '';
        $group_id = $_POST['group_id'] ?? 1;
        
        $update_stmt = $pdo->prepare("
            UPDATE students 
            SET student_id = ?, first_name = ?, last_name = ?, email = ?, course = ?, group_id = ?
            WHERE id = ?
        ");
        
        $update_stmt->execute([$student_id, $first_name, $last_name, $email, $course, $group_id, $id]);
        
        $message = "✅ Étudiant mis à jour avec succès!";
        $message_type = "success";
        
        // Recharger les données
        $stmt->execute([$id]);
        $student = $stmt->fetch();
    }
    
} catch(PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Récupérer les groupes
$groups_stmt = $pdo->query("SELECT * FROM groups");
$groups = $groups_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier étudiant - EduTrack</title>
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
            margin-right: 10px; 
        }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .success { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-edit"></i> Modifier l'étudiant</h1>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>ID Étudiant:</label>
                <input type="text" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Prénom:</label>
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Nom:</label>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
            </div>
            
            <div class="form-group">
                <label>Cours:</label>
                <input type="text" name="course" value="<?php echo htmlspecialchars($student['course']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Groupe:</label>
                <select name="group_id">
                    <?php foreach($groups as $group): ?>
                        <option value="<?php echo $group['id']; ?>" 
                            <?php echo $student['group_id'] == $group['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($group['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn"><i class="fas fa-save"></i> Enregistrer</button>
            <a href="list_students.php" class="btn" style="background: #6b7280;">Annuler</a>
        </form>
    </div>
</body>
</html>