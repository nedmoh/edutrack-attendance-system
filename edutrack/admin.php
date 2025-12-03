<?php
require_once 'config/db_connect.php';

// Gestion des actions
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$message_type = '';

try {
    $pdo = connectDB();
    
    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch($action) {
            case 'add_student':
                $student_id = $_POST['student_id'];
                $first_name = $_POST['first_name'];
                $last_name = $_POST['last_name'];
                $email = $_POST['email'];
                $course = $_POST['course'];
                $group_id = $_POST['group_id'];
                
                $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, last_name, email, course, group_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$student_id, $first_name, $last_name, $email, $course, $group_id]);
                
                $message = "Étudiant ajouté avec succès !";
                $message_type = "success";
                break;
                
            case 'create_session':
                $course_name = $_POST['course_name'];
                $group_id = $_POST['group_id'];
                $session_date = $_POST['session_date'];
                $opened_by = $_POST['opened_by'];
                
                $stmt = $pdo->prepare("INSERT INTO attendance_sessions (course_name, group_id, session_date, opened_by, status) VALUES (?, ?, ?, ?, 'open')");
                $stmt->execute([$course_name, $group_id, $session_date, $opened_by]);
                
                $message = "Session créée avec succès !";
                $message_type = "success";
                break;
                
            case 'take_attendance':
                $session_id = $_POST['session_id'];
                $attendances = $_POST['attendance'] ?? [];
                
                foreach ($attendances as $student_id => $is_present) {
                    $check_stmt = $pdo->prepare("SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?");
                    $check_stmt->execute([$session_id, $student_id]);
                    
                    if ($check_stmt->fetch()) {
                        $update_stmt = $pdo->prepare("UPDATE attendance_records SET present = ? WHERE session_id = ? AND student_id = ?");
                        $update_stmt->execute([$is_present ? 1 : 0, $session_id, $student_id]);
                    } else {
                        $insert_stmt = $pdo->prepare("INSERT INTO attendance_records (session_id, student_id, session_number, present) VALUES (?, ?, 1, ?)");
                        $insert_stmt->execute([$session_id, $student_id, $is_present ? 1 : 0]);
                    }
                }
                
                $message = "Présence enregistrée avec succès !";
                $message_type = "success";
                break;
                
            case 'close_session':
                $session_id = $_POST['session_id'];
                $stmt = $pdo->prepare("UPDATE attendance_sessions SET status = 'closed' WHERE id = ?");
                $stmt->execute([$session_id]);
                
                $message = "Session fermée avec succès !";
                $message_type = "success";
                break;
        }
    }
    
    // Récupération des données selon l'action
    switch($action) {
        case 'students':
            $students = $pdo->query("SELECT s.*, g.name as group_name FROM students s LEFT JOIN groups g ON s.group_id = g.id ORDER BY s.last_name")->fetchAll();
            break;
            
        case 'sessions':
            $sessions = $pdo->query("SELECT s.*, g.name as group_name FROM attendance_sessions s LEFT JOIN groups g ON s.group_id = g.id ORDER BY s.session_date DESC")->fetchAll();
            break;
            
        case 'take_attendance':
            $session_id = $_GET['session_id'] ?? 0;
            $session = $pdo->query("SELECT * FROM attendance_sessions WHERE id = $session_id")->fetch();
            $students = $pdo->query("SELECT * FROM students WHERE group_id = {$session['group_id']} ORDER BY last_name")->fetchAll();
            break;
            
        case 'reports':
            $stats = $pdo->query("
                SELECT 
                    COUNT(*) as total_students,
                    SUM(CASE WHEN absences < 3 THEN 1 ELSE 0 END) as good_attendance,
                    SUM(CASE WHEN absences >= 3 AND absences <= 4 THEN 1 ELSE 0 END) as warning_attendance,
                    SUM(CASE WHEN absences >= 5 THEN 1 ELSE 0 END) as critical_attendance
                FROM (
                    SELECT 
                        s.id,
                        COUNT(CASE WHEN ar.present = 0 THEN 1 END) as absences
                    FROM students s
                    LEFT JOIN attendance_records ar ON s.student_id = ar.student_id
                    GROUP BY s.id
                ) as attendance_summary
            ")->fetch();
            break;
    }
    
    // Données communes
    $groups = $pdo->query("SELECT * FROM groups")->fetchAll();
    $open_sessions = $pdo->query("SELECT * FROM attendance_sessions WHERE status = 'open'")->fetchAll();
    
} catch(PDOException $e) {
    $message = "Erreur: " . $e->getMessage();
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin EduTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4ade80;
            --danger: #ef4444;
            --warning: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f1f5f9;
            color: var(--dark);
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: var(--primary);
            color: white;
            padding: 20px 0;
        }
        
        .logo {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-links {
            list-style: none;
        }
        
        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.1);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .btn {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-success { background: var(--success); }
        .btn-danger { background: var(--danger); }
        .btn-warning { background: var(--warning); }
        
        /* Tables */
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
            background: var(--primary);
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success { background: #d1fae5; color: #065f46; border-left: 4px solid var(--success); }
        .error { background: #fee2e2; color: #991b1b; border-left: 4px solid var(--danger); }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h1><i class="fas fa-user-shield"></i> Admin EduTrack</h1>
            </div>
            <ul class="nav-links">
                <li><a href="?action=dashboard" class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-gauge-high"></i> Dashboard</a></li>
                <li><a href="?action=students" class="<?php echo $action === 'students' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestion Étudiants</a></li>
                <li><a href="?action=sessions" class="<?php echo $action === 'sessions' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Sessions</a></li>
                <li><a href="?action=reports" class="<?php echo $action === 'reports' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Rapports</a></li>
                <li><a href="index.html" style="margin-top: 20px; background: rgba(255,255,255,0.2);"><i class="fas fa-external-link-alt"></i> Interface Publique</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h2>
                    <?php 
                    $titles = [
                        'dashboard' => 'Tableau de Bord',
                        'students' => 'Gestion des Étudiants',
                        'sessions' => 'Gestion des Sessions',
                        'take_attendance' => 'Prendre la Présence',
                        'reports' => 'Rapports et Statistiques'
                    ];
                    echo $titles[$action] ?? 'Administration';
                    ?>
                </h2>
            </div>
            
            <div class="content">
                <?php if ($message): ?>
                    <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php switch($action): 
                    case 'dashboard': ?>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <i class="fas fa-users"></i>
                                <div class="stat-value"><?php echo $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(); ?></div>
                                <div>Étudiants</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-calendar-check"></i>
                                <div class="stat-value"><?php echo $pdo->query("SELECT COUNT(*) FROM attendance_sessions WHERE status = 'open'")->fetchColumn(); ?></div>
                                <div>Sessions Actives</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-clipboard-list"></i>
                                <div class="stat-value"><?php echo $pdo->query("SELECT COUNT(*) FROM attendance_records")->fetchColumn(); ?></div>
                                <div>Présences</div>
                            </div>
                        </div>
                        
                        <div class="quick-actions">
                            <h3>Actions Rapides</h3>
                            <div style="display: flex; gap: 15px; margin-top: 20px;">
                                <a href="?action=students" class="btn"><i class="fas fa-user-plus"></i> Ajouter un Étudiant</a>
                                <a href="?action=sessions" class="btn btn-success"><i class="fas fa-calendar-plus"></i> Créer une Session</a>
                                <a href="?action=reports" class="btn btn-warning"><i class="fas fa-chart-pie"></i> Voir les Rapports</a>
                            </div>
                        </div>
                        
                    <?php break; ?>
                    
                    <?php case 'students': ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3>Liste des Étudiants</h3>
                            <button onclick="document.getElementById('addStudentForm').style.display='block'" class="btn">
                                <i class="fas fa-user-plus"></i> Ajouter un Étudiant
                            </button>
                        </div>
                        
                        <!-- Formulaire d'ajout -->
                        <div id="addStudentForm" style="display: none; background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h4>Nouvel Étudiant</h4>
                            <form method="POST" action="?action=add_student">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="form-group">
                                        <label>ID Étudiant</label>
                                        <input type="text" name="student_id" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Groupe</label>
                                        <select name="group_id" required>
                                            <?php foreach($groups as $group): ?>
                                                <option value="<?php echo $group['id']; ?>"><?php echo $group['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Prénom</label>
                                        <input type="text" name="first_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Nom</label>
                                        <input type="text" name="last_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email">
                                    </div>
                                    <div class="form-group">
                                        <label>Cours</label>
                                        <input type="text" name="course" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn" style="margin-top: 15px;">Ajouter</button>
                                <button type="button" class="btn btn-danger" onclick="document.getElementById('addStudentForm').style.display='none'" style="margin-top: 15px;">Annuler</button>
                            </form>
                        </div>
                        
                        <!-- Liste des étudiants -->
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
                                    <td><?php echo $student['student_id']; ?></td>
                                    <td><?php echo $student['last_name']; ?></td>
                                    <td><?php echo $student['first_name']; ?></td>
                                    <td><?php echo $student['email']; ?></td>
                                    <td><?php echo $student['course']; ?></td>
                                    <td><?php echo $student['group_name']; ?></td>
                                    <td class="action-buttons">
                                        <a href="students/update_student.php?id=<?php echo $student['id']; ?>" class="btn btn-warning btn-sm">Modifier</a>
                                        <a href="students/delete_student.php?id=<?php echo $student['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cet étudiant ?')">Supprimer</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                    <?php break; ?>
                    
                    <?php case 'sessions': ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3>Sessions de Présence</h3>
                            <button onclick="document.getElementById('addSessionForm').style.display='block'" class="btn btn-success">
                                <i class="fas fa-calendar-plus"></i> Nouvelle Session
                            </button>
                        </div>
                        
                        <!-- Formulaire création session -->
                        <div id="addSessionForm" style="display: none; background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h4>Nouvelle Session</h4>
                            <form method="POST" action="?action=create_session">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="form-group">
                                        <label>Nom du Cours</label>
                                        <input type="text" name="course_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Groupe</label>
                                        <select name="group_id" required>
                                            <?php foreach($groups as $group): ?>
                                                <option value="<?php echo $group['id']; ?>"><?php echo $group['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Date</label>
                                        <input type="date" name="session_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Professeur</label>
                                        <input type="text" name="opened_by" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn">Créer</button>
                                <button type="button" class="btn btn-danger" onclick="document.getElementById('addSessionForm').style.display='none'">Annuler</button>
                            </form>
                        </div>
                        
                        <!-- Liste des sessions -->
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cours</th>
                                    <th>Groupe</th>
                                    <th>Date</th>
                                    <th>Professeur</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($sessions as $session): ?>
                                <tr>
                                    <td><?php echo $session['id']; ?></td>
                                    <td><?php echo $session['course_name']; ?></td>
                                    <td><?php echo $session['group_name']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($session['session_date'])); ?></td>
                                    <td><?php echo $session['opened_by']; ?></td>
                                    <td>
                                        <span style="color: <?php echo $session['status'] === 'open' ? '#10b981' : '#6b7280'; ?>">
                                            <?php echo $session['status'] === 'open' ? '🟢 Ouverte' : '🔴 Fermée'; ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <?php if ($session['status'] === 'open'): ?>
                                            <a href="?action=take_attendance&session_id=<?php echo $session['id']; ?>" class="btn btn-sm">Prendre présence</a>
                                            <form method="POST" action="?action=close_session" style="display: inline;">
                                                <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Fermer cette session ?')">Fermer</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #6b7280;">Session fermée</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                    <?php break; ?>
                    
                    <?php case 'take_attendance': ?>
                        <h3>Prendre la Présence - <?php echo $session['course_name']; ?></h3>
                        <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($session['session_date'])); ?> | 
                           <strong>Groupe:</strong> <?php echo $groups[$session['group_id']-1]['name'] ?? ''; ?></p>
                        
                        <form method="POST" action="?action=take_attendance">
                            <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                            
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Présent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['student_id']; ?></td>
                                        <td><?php echo $student['last_name']; ?></td>
                                        <td><?php echo $student['first_name']; ?></td>
                                        <td>
                                            <input type="checkbox" name="attendance[<?php echo $student['student_id']; ?>]" value="1" checked>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <button type="submit" class="btn" style="margin-top: 20px;">
                                <i class="fas fa-save"></i> Enregistrer la Présence
                            </button>
                            <a href="?action=sessions" class="btn btn-danger">Annuler</a>
                        </form>
                        
                    <?php break; ?>
                    
                    <?php case 'reports': ?>
                        <h3>Rapports de Présence</h3>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <i class="fas fa-users"></i>
                                <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                                <div>Total Étudiants</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-face-smile"></i>
                                <div class="stat-value"><?php echo $stats['good_attendance']; ?></div>
                                <div>Bonnes Présences</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-face-meh"></i>
                                <div class="stat-value"><?php echo $stats['warning_attendance']; ?></div>
                                <div>Attention Requise</div>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-face-frown"></i>
                                <div class="stat-value"><?php echo $stats['critical_attendance']; ?></div>
                                <div>Critique</div>
                            </div>
                        </div>
                        
                        <h4 style="margin-top: 30px;">Détails par Étudiant</h4>
                        <?php
                        $student_stats = $pdo->query("
                            SELECT 
                                s.student_id,
                                s.first_name,
                                s.last_name,
                                s.course,
                                COUNT(CASE WHEN ar.present = 1 THEN 1 END) as present_count,
                                COUNT(CASE WHEN ar.present = 0 THEN 1 END) as absent_count,
                                COUNT(*) as total_sessions
                            FROM students s
                            LEFT JOIN attendance_records ar ON s.student_id = ar.student_id
                            GROUP BY s.student_id
                            ORDER BY s.last_name
                        ")->fetchAll();
                        ?>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Cours</th>
                                    <th>Présences</th>
                                    <th>Absences</th>
                                    <th>Taux</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($student_stats as $stat): 
                                    $attendance_rate = $stat['total_sessions'] > 0 ? ($stat['present_count'] / $stat['total_sessions']) * 100 : 0;
                                    $status = $attendance_rate >= 80 ? '✅ Bon' : ($attendance_rate >= 60 ? '⚠️ Moyen' : '❌ Critique');
                                ?>
                                <tr>
                                    <td><?php echo $stat['last_name'] . ' ' . $stat['first_name']; ?></td>
                                    <td><?php echo $stat['course']; ?></td>
                                    <td><?php echo $stat['present_count']; ?></td>
                                    <td><?php echo $stat['absent_count']; ?></td>
                                    <td><?php echo number_format($attendance_rate, 1); ?>%</td>
                                    <td><?php echo $status; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                    <?php break; ?>
                    
                <?php endswitch; ?>
            </div>
        </div>
    </div>

    <script>
        // Scripts simples pour l'admin
        function confirmAction(message) {
            return confirm(message);
        }
    </script>
</body>
</html>
