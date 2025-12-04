<?php
require_once __DIR__ . '/config/db_connect.php';

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

try {
    $pdo = connectDB();

    // Fetch open and all sessions for selection
    $sessions = $pdo->query("SELECT s.*, g.name as group_name FROM attendance_sessions s LEFT JOIN groups g ON s.group_id=g.id ORDER BY s.session_date DESC")->fetchAll(PDO::FETCH_ASSOC);

    $session = null;
    $students = [];
    $recent_students = [];

    if ($session_id) {
        $stmt = $pdo->prepare("SELECT s.*, g.name as group_name FROM attendance_sessions s LEFT JOIN groups g ON s.group_id=g.id WHERE s.id = ?");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session) {
            $students_stmt = $pdo->prepare("SELECT id, student_id, first_name, last_name, email, course, group_id, created_at FROM students WHERE group_id = ? ORDER BY last_name, first_name");
            $students_stmt->execute([$session['group_id']]);
            $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

            // recent students (last 20)
            $recent_stmt = $pdo->prepare("SELECT id, student_id, first_name, last_name, group_id, created_at FROM students ORDER BY created_at DESC LIMIT 20");
            $recent_stmt->execute();
            $recent_students = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

} catch (PDOException $e) {
    die("Erreur DB: " . $e->getMessage());
}

?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Debug — étudiants et sessions</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#f4f6fb;color:#0f172a;padding:20px}
.container{max-width:1000px;margin:0 auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 4px 12px rgba(2,6,23,0.06)}
h1{margin-top:0}
.table{width:100%;border-collapse:collapse;margin-top:12px}
.table th,.table td{padding:8px;border:1px solid #e6eef8;text-align:left}
.select-row{display:flex;gap:10px;align-items:center}
.note{background:#fffbeb;padding:10px;border-left:4px solid #f59e0b;margin-top:12px}
.bad{color:#b91c1c}
.ok{color:#047857}
</style>
</head>
<body>
<div class="container">
    <h1>Diagnostic — Sessions & étudiants</h1>

    <form method="get" class="select-row">
        <label for="session_id">Choisir une session:</label>
        <select name="session_id" id="session_id">
            <option value="0">-- sélectionnez --</option>
            <?php foreach($sessions as $s): ?>
                <option value="<?php echo $s['id']; ?>" <?php echo ($s['id']==$session_id)?'selected':''; ?>>
                    <?php echo htmlspecialchars($s['id'].' — '.$s['course_name'].' ('.($s['group_name']?:'Groupe '.$s['group_id']).') '.$s['session_date']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Voir</button>
        <a href="admin.php?action=sessions" style="margin-left:12px">Aller aux sessions (admin)</a>
    </form>

    <?php if ($session_id && !$session): ?>
        <div class="note bad">Session introuvable (ID: <?php echo $session_id; ?>).</div>
    <?php endif; ?>

    <?php if ($session): ?>
        <h2>Session: <?php echo htmlspecialchars($session['course_name']); ?> <small>(ID: <?php echo $session['id']; ?>)</small></h2>
        <p><strong>Groupe:</strong> <?php echo htmlspecialchars($session['group_name'].' ('.$session['group_id'].')'); ?> — <strong>Date:</strong> <?php echo htmlspecialchars($session['session_date']); ?> — <strong>Statut:</strong> <?php echo htmlspecialchars($session['status']); ?></p>

        <h3>Étudiants assignés à ce groupe (<?php echo count($students); ?>)</h3>
        <?php if (empty($students)): ?>
            <div class="note">Aucun étudiant trouvé pour le groupe <?php echo htmlspecialchars($session['group_id']); ?>.</div>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>id</th><th>student_id</th><th>Nom</th><th>Email</th><th>Cours</th><th>group_id</th><th>created_at</th></tr></thead>
                <tbody>
                <?php foreach($students as $st): ?>
                    <tr>
                        <td><?php echo $st['id']; ?></td>
                        <td><?php echo htmlspecialchars($st['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($st['last_name'].' '.$st['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($st['email']); ?></td>
                        <td><?php echo htmlspecialchars($st['course']); ?></td>
                        <td><?php echo htmlspecialchars($st['group_id']); ?></td>
                        <td><?php echo htmlspecialchars($st['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3 style="margin-top:18px">Derniers étudiants ajoutés (20)</h3>
        <table class="table">
            <thead><tr><th>id</th><th>student_id</th><th>Nom</th><th>group_id</th><th>created_at</th></tr></thead>
            <tbody>
            <?php foreach($recent_students as $rs): ?>
                <tr>
                    <td><?php echo $rs['id']; ?></td>
                    <td><?php echo htmlspecialchars($rs['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($rs['last_name'].' '.$rs['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($rs['group_id']); ?></td>
                    <td><?php echo htmlspecialchars($rs['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>
        <p class="note">Sélectionnez une session pour afficher les étudiants assignés à son groupe.</p>
    <?php endif; ?>

    <p style="margin-top:20px;font-size:0.9rem;color:#334155">Remarque: Cette page est un outil de diagnostic. Supprimez-la après usage si nécessaire.</p>
</div>
</body>
</html>
