<?php
require_once '../config/db_connect.php';

try {
    $pdo = connectDB();
    $stmt = $pdo->query("
        SELECT s.*, g.name as group_name 
        FROM students s 
        LEFT JOIN groups g ON s.group_id = g.id 
        ORDER BY s.last_name, s.first_name
    ");
    $students = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des étudiants - EduTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f4ff, #e2e8ff);
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        h1 { color: #4361ee; margin-bottom: 30px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #4361ee;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f8fafc;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-edit {
            background: #f59e0b;
            color: white;
        }
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        .btn-add {
            background: #10b981;
            color: white;
            padding: 12px 24px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-users"></i> Liste des étudiants</h1>
        
        <a href="add_student.php" class="btn btn-add">
            <i class="fas fa-user-plus"></i> Ajouter un étudiant
        </a>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Cours</th>
                    <th>Groupe</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo htmlspecialchars($student['course']); ?></td>
                    <td><?php echo htmlspecialchars($student['group_name'] ?? 'Non assigné'); ?></td>
                    <td>
                        <a href="update_student.php?id=<?php echo $student['id']; ?>" class="btn btn-edit">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                        <a href="delete_student.php?id=<?php echo $student['id']; ?>" class="btn btn-delete" 
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet étudiant ?')">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 20px;">
            <a href="../index.html">← Retour à l'accueil</a>
        </p>
    </div>
</body>
</html>