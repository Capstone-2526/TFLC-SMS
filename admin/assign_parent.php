<?php
// Create admin/assign_parent.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit;
}

include '../includes/header.php';
require_once '../config/database.php';
$db = (new Database())->getConnection();

if ($_POST) {
    $student_id = $_POST['student_id'];
    $parent_id = (int)$_POST['parent_id'];
    $stmt = $db->prepare("REPLACE INTO student_parent (student_id, parent_id, relation) VALUES (?, ?, 'Parent')");
    $stmt->execute([$student_id, $parent_id]);
    $success = "✅ Assigned!";
}

$students = $db->query("SELECT student_id, name, class FROM students")->fetchAll();
$parents = $db->query("SELECT id, name, phone FROM users WHERE role='parent'")->fetchAll();
?>
<div class="max-w-4xl mx-auto p-12">
    <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl p-12">
        <h1 class="text-4xl font-black mb-8 bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
            Assign Parent to Student
        </h1>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 text-green-800 p-6 rounded-2xl mb-8 font-bold text-xl"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
            <div>
                <label class="block text-2xl font-bold mb-4">Student</label>
                <select name="student_id" class="w-full p-6 border-2 rounded-3xl text-xl focus:ring-4 focus:ring-indigo-500" required>
                    <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s['student_id']; ?>"><?php echo htmlspecialchars($s['name']); ?> (<?php echo $s['class']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-2xl font-bold mb-4">Parent</label>
                <select name="parent_id" class="w-full p-6 border-2 rounded-3xl text-xl focus:ring-4 focus:ring-indigo-500" required>
                    <?php foreach ($parents as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['phone']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="md:col-span-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-2xl font-black px-12 py-6 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-1 transition-all w-full">
                <i class="fas fa-link mr-3"></i>Assign Parent
            </button>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>