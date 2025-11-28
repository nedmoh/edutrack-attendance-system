<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db_connect.php';

try {
    $pdo = connectDB();

    
    $total_students = $pdo->query("SELECT COUNT(*) as count FROM students")->fetch()['count'];
    
    
    $attendance_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_records,
            SUM(present) as total_present,
            SUM(participated) as total_participated
        FROM attendance_records
    ")->fetch();
    
    
    $attendance_distribution = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN absences < 3 THEN 1 ELSE 0 END) as good_attendance,
            SUM(CASE WHEN absences >= 3 AND absences <= 4 THEN 1 ELSE 0 END) as warning_attendance,
            SUM(CASE WHEN absences >= 5 THEN 1 ELSE 0 END) as critical_attendance
        FROM (
            SELECT 
                s.student_id,
                COUNT(CASE WHEN ar.present = 0 THEN 1 END) as absences
            FROM students s
            LEFT JOIN attendance_records ar ON s.student_id = ar.student_id
            GROUP BY s.student_id
        ) as attendance_summary
    ")->fetch();
    
    
    $course_distribution = $pdo->query("
        SELECT course, COUNT(*) as count 
        FROM students 
        GROUP BY course
    ")->fetchAll();
    
    $attendance_by_day = $pdo->query("
        SELECT 
            DATE(ar.record_date) as date,
            COUNT(*) as total,
            SUM(ar.present) as present
        FROM attendance_records ar
        GROUP BY DATE(ar.record_date)
        ORDER BY date DESC
        LIMIT 7
    ")->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'totalStudents' => (int)$total_students,
            'totalPresent' => (int)($attendance_stats['total_present'] ?? 0),
            'totalParticipated' => (int)($attendance_stats['total_participated'] ?? 0),
            'totalRecords' => (int)($attendance_stats['total_records'] ?? 0),
            'attendanceDistribution' => [
                'good' => (int)($attendance_distribution['good_attendance'] ?? 0),
                'warning' => (int)($attendance_distribution['warning_attendance'] ?? 0),
                'critical' => (int)($attendance_distribution['critical_attendance'] ?? 0)
            ],
            'courseDistribution' => $course_distribution,
            'attendanceByDay' => $attendance_by_day
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur base de données: ' . $e->getMessage()
    ]);
}
?>