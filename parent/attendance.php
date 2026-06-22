<?php
session_start();

$page_title = "Attendance";
include '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$parent_id = $_SESSION['user_id'];

// Get all children first
$childrenStmt = $db->prepare("
    SELECT s.student_id, s.name 
    FROM students s
    INNER JOIN student_parent sp ON s.student_id = sp.student_id
    WHERE sp.parent_id = ? AND s.status = 'Active'
    ORDER BY s.name ASC
");
$childrenStmt->execute([$parent_id]);
$children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);

$selected_student_id = $_GET['student_id'] ?? ($children[0]['student_id'] ?? null);
$attendance = [];

// Fetch attendance for selected student
if ($selected_student_id) {
    $attendanceStmt = $db->prepare("
        SELECT a.*, s.name as student_name
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        WHERE a.student_id = ? 
        ORDER BY a.date DESC
        LIMIT 30
    ");
    $attendanceStmt->execute([$selected_student_id]);
    $attendance = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="flex min-h-screen">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 p-12">
        <div class="max-w-7xl mx-auto space-y-12">
            <!-- Header -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-10 border border-white/50">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-5xl font-black bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-3">
                            Attendance Records
                        </h1>
                        <p class="text-2xl text-gray-600">Track your child's attendance</p>
                    </div>
                    <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-3xl flex items-center justify-center shadow-2xl">
                        <i class="fas fa-calendar-check text-5xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Student Selector -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-8 border border-white/50">
                <label class="block text-xl font-bold text-gray-900 mb-4">Select Student:</label>
                <select onchange="window.location.href='?student_id='+this.value" class="w-full max-w-md p-4 border-2 border-gray-200 rounded-2xl text-2xl font-semibold focus:ring-4 focus:ring-blue-200 focus:border-blue-500">
                    <?php foreach ($children as $child): ?>
                    <option value="<?php echo htmlspecialchars($child['student_id']); ?>" 
                            <?php echo ($selected_student_id == $child['student_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($child['name']); ?> (<?php echo htmlspecialchars($child['student_id']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Attendance Table -->
            <?php if ($selected_student_id && !empty($attendance)): ?>
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden border border-white/50">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-blue-500 to-cyan-600 text-white">
                            <tr>
                                <th class="p-6 text-left text-xl font-black">Date</th>
                                <th class="p-6 text-left text-xl font-black">Status</th>
                                <th class="p-6 text-left text-xl font-black">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($attendance as $record): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-6 font-bold text-2xl text-gray-900">
                                    <?php echo date('M j, Y', strtotime($record['date'])); ?>
                                </td>
                                <td class="p-6">
                                    <span class="px-6 py-3 rounded-2xl text-2xl font-black shadow-lg <?php 
                                        echo $record['status'] == 'Present' 
                                            ? 'bg-green-500 text-white' 
                                            : 'bg-red-500 text-white'; 
                                    ?>">
                                        <?php echo htmlspecialchars($record['status']); ?>
                                    </span>
                                </td>
                                <td class="p-6 font-semibold text-xl text-gray-700">
                                    <?php echo date('g:i A', strtotime($record['time'] ?? 'now')); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php elseif ($selected_student_id): ?>
            <div class="text-center py-32 bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/50">
                <i class="fas fa-calendar-times text-9xl text-gray-300 mb-8"></i>
                <h3 class="text-4xl font-bold text-gray-500 mb-4">No Attendance Records</h3>
                <p class="text-xl text-gray-400">Attendance records will appear here.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>