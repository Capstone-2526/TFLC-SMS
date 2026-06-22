<?php
session_start();

$page_title = "My Children";
include '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$parent_id = $_SESSION['user_id'];

// Fetch all active children
$childrenStmt = $db->prepare("
    SELECT s.*, 
           p.name as parent_name,
           p.phone as parent_phone
    FROM students s
    INNER JOIN student_parent sp ON s.student_id = sp.student_id
    LEFT JOIN users p ON sp.parent_id = p.id
    WHERE sp.parent_id = ? AND s.status = 'Active'
    ORDER BY s.name ASC
");
$childrenStmt->execute([$parent_id]);
$children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);
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
                            My Children
                        </h1>
                        <p class="text-2xl text-gray-600"><?php echo count($children); ?> Active Student<?php echo count($children) !== 1 ? 's' : ''; ?></p>
                    </div>
                    <div class="w-32 h-32 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl flex items-center justify-center shadow-2xl">
                        <i class="fas fa-child text-5xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Children Grid -->
            <?php if (empty($children)): ?>
            <div class="text-center py-32">
                <i class="fas fa-child text-9xl text-gray-300 mb-8"></i>
                <h3 class="text-4xl font-bold text-gray-500 mb-4">No Children Found</h3>
                <p class="text-xl text-gray-400">Your children will appear here when enrolled.</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($children as $child): ?>
                <div class="group bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-8 border border-white/50 hover:shadow-3xl hover:-translate-y-2 transition-all duration-500">
                    <div class="text-center mb-6">
                        <img src="../assets/images/students/<?php echo htmlspecialchars($child['profile_image'] ?? 'student_default.jpg'); ?>" 
                             class="w-32 h-32 mx-auto rounded-3xl object-cover shadow-2xl border-4 border-white ring-4 ring-emerald-100/50 group-hover:scale-110 transition-transform">
                        <h3 class="text-3xl font-black text-gray-900 mt-4"><?php echo htmlspecialchars($child['name']); ?></h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl">
                            <span class="text-lg font-semibold text-gray-700">Grade</span>
                            <span class="text-2xl font-bold text-emerald-700"><?php echo htmlspecialchars($child['grade_level']); ?></span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="p-4 bg-blue-50 rounded-2xl">
                                <span class="text-sm text-blue-600 font-semibold block">Student ID</span>
                                <span class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($child['student_id']); ?></span>
                            </div>
                            <div class="p-4 bg-purple-50 rounded-2xl">
                                <span class="text-sm text-purple-600 font-semibold block">Gender</span>
                                <span class="text-xl font-bold capitalize"><?php echo htmlspecialchars($child['gender'] ?? 'N/A'); ?></span>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-gray-200 space-y-2">
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <i class="fas fa-calendar-day text-emerald-500"></i>
                                <span>Enrolled: <?php echo date('M Y', strtotime($child['enrollment_date'] ?? 'now')); ?></span>
                            </div>
                            <?php if ($child['dob']): ?>
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <i class="fas fa-birthday-cake text-pink-500"></i>
                                <span>DOB: <?php echo date('M j, Y', strtotime($child['dob'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex gap-3 mt-6">
                            <a href="child-attendance.php?student_id=<?php echo urlencode($child['student_id']); ?>" 
                               class="flex-1 bg-gradient-to-r from-blue-500 to-cyan-600 text-white py-3 px-6 rounded-2xl text-center font-bold shadow-lg hover:shadow-xl transition-all">
                                <i class="fas fa-calendar-check mr-2"></i>Attendance
                            </a>
                            <a href="chat.php?student_id=<?php echo urlencode($child['student_id']); ?>" 
                               class="flex-1 bg-gradient-to-r from-purple-500 to-purple-700 text-white py-3 px-6 rounded-2xl text-center font-bold shadow-lg hover:shadow-xl transition-all">
                                <i class="fas fa-comments mr-2"></i>Message
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>