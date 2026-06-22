<?php
session_start();

$page_title = "Take Attendance";
include '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$teacher_id = $_SESSION['user_id'];

// Fetch all active students
$studentsStmt = $db->prepare("
    SELECT s.*, u.name as parent_name 
    FROM students s
    LEFT JOIN users u ON s.parent_id = u.id
    WHERE s.status = 'Active'
    ORDER BY s.grade_level, s.name ASC
");
$studentsStmt->execute();
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
$message = '';
if ($_POST) {
    foreach ($_POST['attendance'] as $student_id => $data) {
        $status = $data['status'];
        $notes = $data['notes'] ?? '';
        
        $checkStmt = $db->prepare("SELECT id FROM attendance WHERE student_id = ? AND DATE(date) = CURDATE()");
        $checkStmt->execute([$student_id]);
        
        if ($checkStmt->fetch()) {
            $updateStmt = $db->prepare("UPDATE attendance SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE student_id = ? AND DATE(date) = CURDATE()");
            $updateStmt->execute([$status, $notes, $student_id]);
        } else {
            $insertStmt = $db->prepare("INSERT INTO attendance (student_id, date, status, notes) VALUES (?, CURDATE(), ?, ?)");
            $insertStmt->execute([$student_id, $status, $notes]);
        }
    }
    $message = '<div class="bg-emerald-500 text-white p-6 rounded-3xl shadow-2xl text-center text-2xl font-bold mb-12 animate-pulse">Attendance Marked Successfully! ✅</div>';
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
                            Take Attendance
                        </h1>
                        <p class="text-2xl text-gray-600"><?php echo date('F j, Y - l'); ?></p>
                        <p class="text-xl text-emerald-600 mt-2 font-semibold">
                            <?php 
                            $todayAttendance = $db->prepare("SELECT COUNT(*) FROM attendance WHERE DATE(date) = CURDATE()");
                            $todayAttendance->execute();
                            echo $todayAttendance->fetchColumn(); 
                            ?> records already marked today
                        </p>
                    </div>
                    <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-3xl flex items-center justify-center shadow-2xl">
                        <i class="fas fa-calendar-check text-5xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
                <?php echo $message ?? ''; ?>
            </div>

            <?php if (empty($students)): ?>
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-20 border border-white/50 text-center">
                <i class="fas fa-users text-9xl text-gray-300 mb-8"></i>
                <h2 class="text-4xl font-black text-gray-500 mb-4">No Active Students</h2>
                <p class="text-lg text-gray-600">Add students to start taking attendance</p>
            </div>
            <?php else: ?>
            <form method="POST" class="space-y-8">
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden border border-white/50">
                    <!-- FIXED TABLE -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <!-- PERFECTLY SIZED HEADER -->
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-4 border-gray-200">
                                    <th class="p-3 lg:p-6 text-left text-sm lg:text-lg font-bold text-gray-800 **w-[200px] lg:w-[280px]** rounded-tl-2xl">Student</th>
                                    <th class="p-3 lg:p-6 text-center text-sm lg:text-lg font-bold text-gray-800 **w-[120px] lg:w-[160px]**">ID</th>
                                    <th class="p-3 lg:p-6 text-center text-sm lg:text-lg font-bold text-gray-800 **w-[100px] lg:w-[140px]**">Grade</th>
                                    <th class="p-3 lg:p-6 text-center text-sm lg:text-lg font-bold text-gray-800 **w-[160px] lg:w-[200px]**">Status</th>
                                    <th class="p-3 lg:p-6 text-center text-sm lg:text-lg font-bold text-gray-800 rounded-tr-2xl">Notes</th>
                                </tr>
                            </thead>
                            
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($students as $index => $student): ?>
                                <?php 
                                $todayStmt = $db->prepare("SELECT status, notes FROM attendance WHERE student_id = ? AND DATE(date) = CURDATE()");
                                $todayStmt->execute([$student['id']]);
                                $todayRecord = $todayStmt->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <tr class="hover:bg-indigo-50/30 transition-all duration-200 group <?php echo $index % 2 ? 'bg-white/60' : 'bg-gray-50/40'; ?>">
                                    
                                    <!-- ✅ STUDENT COLUMN - NO OVERLAP -->
                                    <td class="p-3 lg:p-6 font-semibold **w-[200px] lg:w-[280px]**">
                                        <div class="flex items-center space-x-3 truncate">
                                            <img src="../assets/images/students/<?php echo htmlspecialchars($student['profile_image'] ?? 'student_default.jpg'); ?>" 
                                                 alt="<?php echo htmlspecialchars($student['name']); ?>" 
                                                 class="w-9 h-9 lg:w-11 lg:h-11 rounded-full object-cover shadow-lg border-3 border-white ring-2 ring-gray-100 flex-shrink-0 hover:ring-indigo-200 transition-all">
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm lg:text-base font-bold text-gray-900 line-clamp-2 hover:text-indigo-700 truncate pr-2">
                                                    <?php echo htmlspecialchars($student['name']); ?>
                                                </div>
                                                <?php if ($student['parent_name']): ?>
                                                <div class="text-xs text-gray-500 line-clamp-1 mt-1 truncate">
                                                    <?php echo htmlspecialchars($student['parent_name']); ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- ✅ ID COLUMN - CENTERED -->
                                    <td class="p-3 lg:p-6 text-center **w-[120px] lg:w-[160px]** font-mono">
                                        <span class="bg-gradient-to-r from-indigo-100 to-indigo-200 text-indigo-800 px-2 py-1.5 lg:px-3 lg:py-2 rounded-xl font-semibold text-xs lg:text-sm shadow-sm inline-block whitespace-nowrap min-w-[4rem]">
                                            <?php echo htmlspecialchars($student['student_id']); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- ✅ GRADE COLUMN - CENTERED -->
                                    <td class="p-3 lg:p-6 text-center **w-[100px] lg:w-[140px]**">
                                        <span class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-3 py-2 rounded-xl font-bold text-xs lg:text-sm shadow-md inline-block whitespace-nowrap min-w-[3.5rem]">
                                            <?php echo htmlspecialchars($student['grade_level']); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- ✅ STATUS COLUMN - CENTERED -->
                                    <td class="p-3 lg:p-6 text-center **w-[160px] lg:w-[200px]**">
                                        <select name="attendance[<?php echo $student['id']; ?>][status]" 
                                                class="attendance-select w-full max-w-[140px] lg:max-w-[180px] p-2.5 lg:p-3 rounded-xl lg:rounded-2xl text-sm lg:text-base font-bold shadow-lg focus:ring-4 focus:ring-blue-200 focus:outline-none mx-auto block transition-all bg-white border border-gray-200 hover:border-indigo-300">
                                            <option value="Present" <?php echo ($todayRecord['status'] ?? '') == 'Present' ? 'selected' : ''; ?>>Present ✅</option>
                                            <option value="Absent" <?php echo ($todayRecord['status'] ?? '') == 'Absent' ? 'selected' : ''; ?>>Absent ❌</option>
                                            <option value="Late" <?php echo ($todayRecord['status'] ?? '') == 'Late' ? 'selected' : ''; ?>>Late ⏰</option>
                                        </select>
                                    </td>
                                    
                                    <!-- ✅ NOTES COLUMN - FLEXIBLE -->
                                    <td class="p-3 lg:p-6 relative">
                                        <input type="text" 
                                               name="attendance[<?php echo $student['id']; ?>][notes]" 
                                               value="<?php echo htmlspecialchars($todayRecord['notes'] ?? ''); ?>" 
                                               placeholder="Notes..." 
                                               class="w-full p-2.5 lg:p-4 rounded-xl lg:rounded-2xl border-2 border-gray-200 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 transition-all text-sm lg:text-base font-medium shadow-sm hover:shadow-md"
                                               maxlength="100">
                                        <div class="absolute top-1 right-2 text-xs text-gray-400"><?php echo strlen($todayRecord['notes'] ?? '') ?>/100</div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-12 pb-8 px-8 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-center group cursor-pointer rounded-b-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-1 transition-all duration-500">
                    <div class="flex flex-col lg:flex-row items-center justify-center gap-6 lg:gap-12">
                        <i class="fas fa-check-double text-6xl lg:text-7xl opacity-90 group-hover:scale-110 transition-transform"></i>
                        <div>
                            <h3 class="text-3xl lg:text-4xl font-black mb-2">Mark Attendance Complete</h3>
                            <p class="text-emerald-100 text-lg lg:text-xl font-semibold mb-6">Save for all <?php echo count($students); ?> students</p>
                        </div>
                        <button type="submit" class="bg-white/20 backdrop-blur-sm hover:bg-white/30 px-12 py-5 lg:px-16 lg:py-6 rounded-3xl text-xl lg:text-2xl font-black shadow-2xl hover:shadow-3xl transition-all border-2 border-white/30 group-hover:scale-105">
                            <i class="fas fa-save mr-3"></i>Save All Now
                        </button>
                    </div>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- PERFECT CSS -->
<style>
.line-clamp-1, .line-clamp-2 {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-1 { -webkit-line-clamp: 1; }
.line-clamp-2 { -webkit-line-clamp: 2; }

.attendance-select {
    background: linear-gradient(145deg, #ffffff, #f8fafc);
    border-radius: 1rem;
    border: 2px solid #e2e8f0;
    font-weight: 700;
    cursor: pointer;
    min-height: 44px;
}
.attendance-select:focus {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
}
.attendance-select option {
    padding: 12px;
    background: white;
    color: #1f2937;
}

table {
    table-layout: fixed; /* ✅ Prevents column width issues */
}

/* Mobile table stacking */
@media (max-width: 768px) {
    table { font-size: 0.875rem; }
    th, td { padding: 0.75rem 0.5rem !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth dropdown hover
    document.querySelectorAll('.attendance-select').forEach(select => {
        select.addEventListener('change', function() {
            this.blur();
            // Visual feedback
            this.style.transform = 'scale(1.02)';
            setTimeout(() => this.style.transform = 'scale(1)', 150);
        });
        
        select.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-1px)';
        });
        select.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Form submission with loading
    document.querySelector('form').addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Saving...';
        btn.disabled = true;
    });

    // Character counter
    document.querySelectorAll('input[name*="notes"]').forEach(input => {
        input.addEventListener('input', function() {
            const counter = this.parentElement.querySelector('.char-counter') || 
                           document.createElement('div');
            counter.className = 'absolute top-1 right-2 text-xs text-gray-400 char-counter';
            counter.textContent = this.value.length + '/100';
            if (!this.parentElement.querySelector('.char-counter')) {
                this.parentElement.appendChild(counter);
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>