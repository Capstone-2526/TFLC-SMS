<?php
session_start();

$page_title = "All Students";
include '../includes/header.php';  // ✅ Fixed: teacher/ → includes/
require_once '../config/database.php';  // ✅ Fixed: teacher/ → config/

$database = new Database();
$db = $database->getConnection();

$teacher_id = $_SESSION['user_id'];

// Fetch all students with stats
$studentsStmt = $db->prepare("
    SELECT 
        s.*,
        u.name as parent_name,
        u.phone as parent_phone,
        u.email as parent_email,
        (
            SELECT COUNT(*) 
            FROM attendance a 
            WHERE a.student_id = s.id 
            AND MONTH(a.date) = MONTH(CURDATE()) 
            AND YEAR(a.date) = YEAR(CURDATE())
            AND a.status = 'Present'
        ) as present_count,
        (
            SELECT COUNT(*) 
            FROM attendance a 
            WHERE a.student_id = s.id 
            AND MONTH(a.date) = MONTH(CURDATE()) 
            AND YEAR(a.date) = YEAR(CURDATE())
        ) as total_monthly
    FROM students s
    LEFT JOIN users u ON s.parent_id = u.id
    WHERE s.status = 'Active'
    ORDER BY s.grade_level, s.name ASC
");
$studentsStmt->execute();
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Group by grade level
$grades = [];
foreach ($students as $student) {
    $grade = $student['grade_level'];
    if (!isset($grades[$grade])) {
        $grades[$grade] = [];
    }
    $grades[$grade][] = $student;
}
?>

<div class="flex min-h-screen">
    <?php include '../includes/sidebar.php'; ?>  <!-- ✅ Fixed path -->

    <main class="flex-1 p-12">
        <div class="max-w-7xl mx-auto space-y-12">
            <!-- Header -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-10 border border-white/50">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-5xl font-black bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-3">
                            All Students
                        </h1>
                        <p class="text-2xl text-gray-600">Manage your active students</p>
                        <div class="flex gap-6 mt-4">
                            <span class="px-6 py-3 bg-emerald-100 text-emerald-800 rounded-2xl text-xl font-bold shadow-lg">
                                <?php echo count($students); ?> Active Students
                            </span>
                            <?php 
                            $totalPresent = array_sum(array_column($students, 'present_count'));
                            $totalMonthly = array_sum(array_column($students, 'total_monthly'));
                            $avgAttendance = $totalMonthly > 0 ? round(($totalPresent / $totalMonthly) * 100, 1) : 0;
                            ?>
                            <span class="px-6 py-3 bg-blue-100 text-blue-800 rounded-2xl text-xl font-bold shadow-lg">
                                <?php echo $avgAttendance; ?>% Avg Attendance
                            </span>
                        </div>
                    </div>
                    <div class="w-32 h-32 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl flex items-center justify-center shadow-2xl">
                        <i class="fas fa-user-graduate text-5xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Students by Grade -->
            <?php if (!empty($grades)): ?>
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-8 border border-white/50 overflow-hidden">
                <!-- Grade Tabs -->
                <div class="flex flex-wrap gap-4 justify-center lg:justify-start mb-12 pb-8 -mx-4 border-b border-gray-200">
                    <?php foreach ($grades as $grade => $gradeStudents): ?>
                    <button class="grade-tab px-8 py-4 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-indigo-100 hover:to-indigo-200 rounded-3xl font-bold text-xl shadow-lg hover:shadow-xl hover:-translate-y-1 hover:border-indigo-300 transition-all duration-300 border-2 border-transparent mx-2 flex items-center gap-3 group focus:outline-none focus:ring-4 focus:ring-indigo-200">
                        <i class="fas fa-layer-group text-indigo-600 group-hover:scale-110 transition-transform"></i>
                        <span><?php echo htmlspecialchars($grade); ?></span>
                        <span class="bg-indigo-200 text-indigo-800 px-3 py-1 rounded-full text-sm font-bold min-w-[2.5rem] text-center shadow-md">
                            <?php echo count($gradeStudents); ?>
                        </span>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Students Grid -->
                <?php foreach ($grades as $grade => $gradeStudents): ?>
                <section class="grade-section mb-20 pb-12 border-b-2 border-gray-100 last:border-b-0 last:mb-0">
                    <div class="flex items-center gap-6 mb-12 pb-10 border-b border-gray-200">
                        <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl flex items-center justify-center text-white text-3xl font-bold shadow-2xl ring-4 ring-indigo-100/50">
                            <?php echo strtoupper(substr($grade, 0, 2)); ?>
                        </div>
                        <div>
                            <h3 class="text-5xl font-black text-gray-900 mb-2"><?php echo htmlspecialchars($grade); ?></h3>
                            <p class="text-3xl text-gray-600"><?php echo count($gradeStudents); ?> Students</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-8">
                        <?php foreach ($gradeStudents as $student): ?>
                        <a href="student-profile.php?id=<?php echo $student['id']; ?>" class="group relative bg-white/70 backdrop-blur-sm hover:bg-white/90 rounded-3xl p-8 shadow-xl hover:shadow-2xl hover:-translate-y-4 hover:scale-[1.02] transition-all duration-500 border border-white/50 hover:border-indigo-200 overflow-hidden">
                            <!-- Attendance Badge -->
                            <?php 
                            $attendancePercent = $student['total_monthly'] > 0 ? round(($student['present_count'] / $student['total_monthly']) * 100) : 0;
                            $badgeColor = $attendancePercent >= 90 ? 'from-emerald-500 to-teal-600' : ($attendancePercent >= 75 ? 'from-yellow-500 to-orange-600' : 'from-red-500 to-rose-600');
                            ?>
                            <div class="absolute -top-6 -right-6 w-24 h-24 bg-gradient-to-br <?php echo $badgeColor; ?> rounded-3xl flex items-center justify-center text-white text-2xl font-bold shadow-2xl ring-4 ring-white/50 transform rotate-12">
                                <?php echo $attendancePercent; ?><span class="text-lg">%</span>
                            </div>

                            <!-- Student Avatar -->
                            <div class="relative z-10 mb-8 text-center">
                               <img src="../assets/images/students/<?php echo htmlspecialchars($student['profile_image'] ?? 'student_default.jpg'); ?>" 
     class="w-28 h-28 mx-auto rounded-3xl object-cover shadow-2xl border-8 border-white ring-4 ring-indigo-100/50 group-hover:ring-indigo-200/75 transition-all duration-500">
                            <!-- Student Name -->
                            <h4 class="text-2xl font-black text-gray-900 text-center mb-6 group-hover:text-indigo-700 transition-colors px-2">
                                <?php echo htmlspecialchars($student['name']); ?>
                            </h4>

                            <!-- Student ID -->
                            <div class="flex items-center justify-center gap-3 p-4 bg-indigo-50 rounded-2xl mb-6 mx-4">
                                <i class="fas fa-id-card text-indigo-600 text-xl"></i>
                                <span class="font-mono font-bold text-lg text-indigo-800 tracking-wide"><?php echo htmlspecialchars($student['student_id']); ?></span>
                            </div>

                            <!-- Quick Stats -->
                            <div class="grid grid-cols-2 gap-4 mb-8 mx-4">
                                <div class="text-center p-4 bg-gray-50 rounded-2xl group-hover:bg-indigo-50 transition-colors">
                                    <div class="text-2xl font-bold text-emerald-600"><?php echo $student['present_count']; ?></div>
                                    <div class="text-sm text-gray-600 uppercase tracking-wide">Present</div>
                                </div>
                                <div class="text-center p-4 bg-gray-50 rounded-2xl group-hover:bg-indigo-50 transition-colors">
                                    <div class="text-2xl font-bold text-gray-700"><?php echo $student['total_monthly']; ?></div>
                                    <div class="text-sm text-gray-600 uppercase tracking-wide">Total</div>
                                </div>
                            </div>

                            <!-- Gender & Parent -->
                            <div class="space-y-3 mb-8 mx-4">
                                <div class="flex items-center justify-center gap-3 p-4 bg-gradient-to-r from-pink-50 to-purple-50 rounded-2xl border-2 border-pink-100">
                                    <i class="fas fa-venus-mars text-pink-500 text-xl"></i>
                                    <span class="font-bold text-lg capitalize"><?php echo htmlspecialchars($student['gender'] ?? 'N/A'); ?></span>
                                </div>
                                
                                <?php if ($student['parent_name']): ?>
                                <div class="p-4 bg-emerald-50 rounded-2xl border-l-4 border-emerald-400 hover:shadow-md transition-shadow">
                                    <div class="font-semibold text-emerald-800 text-sm mb-1 flex items-center gap-2">
                                        <i class="fas fa-user-tie text-emerald-600"></i>Parent
                                    </div>
                                    <div class="font-bold text-lg text-gray-900"><?php echo htmlspecialchars($student['parent_name']); ?></div>
                                    <div class="text-sm text-emerald-700"><?php echo htmlspecialchars($student['parent_phone'] ?? 'No phone'); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3 pt-6 border-t border-gray-200 mx-4">
                                <a href="../attendance.php?student=<?php echo $student['id']; ?>" 
                                   class="flex-1 bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white py-4 px-6 rounded-2xl font-bold text-center text-sm shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-2">
                                    <i class="fas fa-calendar-check"></i>Attendance
                                </a>
                                <a href="../messages.php?student=<?php echo $student['student_id']; ?>" 
                                   class="flex-1 bg-gradient-to-r from-purple-500 to-purple-700 hover:from-purple-600 hover:to-purple-800 text-white py-4 px-6 rounded-2xl font-bold text-center text-sm shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex items-center justify-center gap-2">
                                    <i class="fas fa-comment-dots"></i>Message
                                </a>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <!-- Empty State -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-24 border border-white/50 text-center">
                <i class="fas fa-users text-[10rem] text-gray-300 mb-12 opacity-75"></i>
                <h2 class="text-6xl font-black text-gray-500 mb-8">No Active Students</h2>
                <p class="text-3xl text-gray-400 mb-16 leading-relaxed">Your student list is empty. Start by adding students to your class.</p>
                <a href="../add-student.php" class="inline-flex items-center gap-4 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white px-16 py-8 rounded-3xl text-2xl font-black shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-500">
                    <i class="fas fa-plus-circle text-4xl"></i>
                    Add First Student
                </a>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Card hover parallax effect
    const cards = document.querySelectorAll('.group');
    cards.forEach((card, index) => {
        card.style.transitionDelay = `${index * 0.05}s`;
        
        card.addEventListener('mouseenter', function() {
            this.querySelector('img').style.transform = 'scale(1.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.querySelector('img').style.transform = 'scale(1)';
        });
    });

    // Smooth scroll to grade sections
    document.querySelectorAll('.grade-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const grade = this.textContent.trim().replace(/\d+/, '');
            const section = document.querySelector(`[data-grade="${grade}"]`);
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>  <!-- ✅ Fixed path -->