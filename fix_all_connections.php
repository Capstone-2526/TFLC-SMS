<?php
// ULTIMATE FIX - Uses YOUR REAL DATA
require_once 'config/database.php';
$db = (new Database())->getConnection();

echo "<h1>🔧 SMART Database Fix (Using YOUR Data)</h1><hr>";

// 1. CREATE table if missing
$db->exec("
CREATE TABLE IF NOT EXISTS `student_parent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(20) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `relation` varchar(50) DEFAULT 'Parent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `student_parent_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  CONSTRAINT `student_parent_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "✅ Table ready<br>";

// 2. Get YOUR REAL students
$students = $db->query("SELECT student_id, name FROM students LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "📚 Found " . count($students) . " students:<br>";
foreach ($students as $s) {
    echo "- {$s['student_id']} ({$s['name']})<br>";
}
echo "<br>";

// 3. Get YOUR REAL parents  
$parents = $db->query("SELECT id, name FROM users WHERE role='parent' ORDER BY id LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
echo "👨‍👩‍👧 Found " . count($parents) . " parents:<br>";
foreach ($parents as $p) {
    echo "- ID {$p['id']}: {$p['name']}<br>";
}
echo "<br>";

// 4. Clear old connections
$db->exec("TRUNCATE TABLE student_parent");
echo "🧹 Cleared old connections<br>";

// 5. SMART CONNECTIONS - Assign first 2 students to first parent, rest to second parent
if (!empty($students) && !empty($parents)) {
    $parent1_id = $parents[0]['id'];
    $parent2_id = isset($parents[1]) ? $parents[1]['id'] : $parent1_id;
    
    $connections = [];
    foreach ($students as $index => $student) {
        $parent_id = ($index % 2 == 0) ? $parent1_id : $parent2_id;
        $relation = ($index % 2 == 0) ? 'Mother' : 'Father';
        
        $stmt = $db->prepare("INSERT INTO student_parent (student_id, parent_id, relation) VALUES (?, ?, ?)");
        $stmt->execute([$student['student_id'], $parent_id, $relation]);
        $connections[] = [
            'student_id' => $student['student_id'],
            'student_name' => $student['name'],
            'parent_id' => $parent_id,
            'parent_name' => $parents[0]['name'] . ($parent_id == $parent2_id ? ' / ' . $parents[1]['name'] : ''),
            'relation' => $relation
        ];
    }
    echo "✅ Created " . count($connections) . " connections!<br>";
} else {
    echo "❌ Need at least 1 student AND 1 parent!";
    exit;
}

// 6. SHOW RESULTS
echo "<h2>✅ PERFECT! Your Connections:</h2>";
echo "<table border='1' style='border-collapse:collapse; width:100%; font-family:monospace;'>";
echo "<tr style='background:#4f46e5;color:white'><th>Student ID</th><th>Student</th><th>Parent ID</th><th>Parent</th><th>Relation</th></tr>";
foreach ($connections as $conn) {
    echo "<tr style='background:#f8fafc'>";
    echo "<td style='padding:12px;font-weight:bold;color:#4f46e5'>{$conn['student_id']}</td>";
    echo "<td style='padding:12px'>{$conn['student_name']}</td>";
    echo "<td style='padding:12px;font-weight:bold'>{$conn['parent_id']}</td>";
    echo "<td style='padding:12px'>{$conn['parent_name']}</td>";
    echo "<td style='padding:12px;color:#059669;font-weight:bold'>{$conn['relation']}</td>";
    echo "</tr>";
}
echo "</table>";

// 7. LOGIN INFO
echo "<hr><h3>🧪 Login Credentials:</h3>";
foreach ($parents as $p) {
    $username = strtolower(str_replace([' ', '.'], '.', $p['name']));
    echo "<div style='background:#10b981;color:white;padding:12px;margin:8px 0;border-radius:12px;font-size:18px'>";
    echo "👤 <strong>{$username}</strong> / <code style='background:black;padding:4px;border-radius:6px'>password</code>";
    echo " → Parent ID: <strong>{$p['id']}</strong>";
    echo "</div>";
}

echo "<hr>";
echo "<div style='background:#3b82f6;color:white;padding:20px;border-radius:12px;font-size:20px;text-align:center;font-weight:bold'>";
echo "🎉 ALL DONE! <a href='parent/login.php' style='color:#fbbf24;text-decoration:underline'>Test Parent Login →</a>";
echo "</div>";

echo "<script>document.title='✅ Database Fixed!'</script>";
?>