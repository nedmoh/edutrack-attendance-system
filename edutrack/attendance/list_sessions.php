<?php
require_once '../config/db_connect.php';

try {
    $pdo = connectDB();
    $stmt = $pdo->query("
        SELECT s.*, g.name as group_name 
        FROM attendance_sessions s 
        LEFT JOIN groups g ON s.group_id = g.id 
        ORDER BY s.session_date DESC, s.created_at DESC
    ");
    $sessions = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

// Message de confirmation
$message = $_GET['message'] ?? '';
$message_type = $_GET['type'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions de présence - EduTrack</title>
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
        .status-open { color: #10b981; font-weight: 600; }
        .status-closed { color: #6b7280; font-weight: 600; }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 2px;
        }
        .btn-close { background: #ef4444; color: white; }
        .btn-take { background: #10b981; color: white; }
        .btn-create { background: #4361ee; color: white; padding: 12px 24px; margin-bottom: 20px; }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-calendar-alt"></i> Sessions de présence</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <a href="create_session.php" class="btn btn-create">
            <i class="fas fa-plus"></i> Créer une nouvelle session
        </a>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cours</th>
                    <th>Groupe</th>
                    <th>Date</th>
                    <th>Ouvert par</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sessions as $session): ?>
                <tr>
                    <td><?php echo $session['id']; ?></td>
                    <td><?php echo htmlspecialchars($session['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($session['group_name']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($session['session_date'])); ?></td>
                    <td><?php echo htmlspecialchars($session['opened_by']); ?></td>
                    <td>
                        <span class="status-<?php echo $session['status']; ?>">
                            <?php echo $session['status'] === 'open' ? '🟢 Ouverte' : '🔴 Fermée'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($session['status'] === 'open'): ?>
                            <a href="take_attendance.php?session_id=<?php echo $session['id']; ?>" class="btn btn-take">
                                <i class="fas fa-clipboard-check"></i> Prendre présence
                            </a>
                            <a href="close_session.php?id=<?php echo $session['id']; ?>" class="btn btn-close"
                               onclick="return confirm('Fermer cette session ?')">
                                <i class="fas fa-lock"></i> Fermer
                            </a>
                        <?php else: ?>
                            <span style="color: #6b7280;">Session fermée</span>
                        <?php endif; ?>
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