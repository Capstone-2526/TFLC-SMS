<?php
session_start();

// SESSION_START- tells php to loads the user's login data 
//FTP - File Transer Protocol
// Normalized Database - separate tables, parents, teachers link them using "Foreign Keys" to avoid repeating data and save space
// ID Columns is - "Primary Key and Auto-Increment" - handles the numbering automatically
// Database Schema - structual design of tables, columns, relationship between "student is linked to a parent"
// Associative Array PHP - php stores data fetched ffrom MySQL, where the 'keys' are the column name (ex. $row['student_name'])
//Catch Block - captures connection errors, and prevents script from crashing with fatal error
//HTML Form 'action' attribute - specifies Php File destination that will receive and process submitted form data
//Name Placed Holder in PDO - (like:id) used in prepared statements to safelty map user data into a SQL query
//DataBase Normalization - process of organizing data into separate related tables( Students and Parents ) to reduce redundancy
//PHP header('Location;..)function - Sends raw HTTP Header to the browser to redirect the user to different page.
//RBAC - Role Based Access Control - checking their role store in the session.
//The 'name' attribute in HTML inputs, key used is ($POST or $GET) to identify and retrive specific value entered by the user.
//Tailwind CSS 'hidden md:block' - utility classes that hide elements on a small mobile screens and show them only on medium-sized screens or larger.
//rowCount()- PDO Method that returns the number of rows affected or retrived by the last SQL statement
//require_once - statement that imports a file a but unsures it is only loased once to prevent function re declarations errors.
//Inventory Thresold Logic - using php to compare database 'quantity' values against a limit(like zero) to trigger out-of-stock alerts.
//SQL Injection - a security vulnerability where attackers insert malicious SQL code  into input fields: prevented by PDO prepared statements.
//CRUD mnemonic - CREATE, READ, UPDATE, DELETE - the four basic operationss of persistent data storage.
//PRIMARY KEY - A unique Identifier ensuring no two rows are the same
//FOREIGN KEY - a column to creates a link between two tablles by referencing the Primary Key of Anothe Table.
//$-SESSION vs $_POST - session persist across multiple pages; , post only contains data from the single form most recently submitted.
//password_hash() - a secure php function that convert plain text password into a one-way scrambled string for storage.

$page_title = "Teacher Dashboard";
include '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$teacher_id = $_SESSION['user_id'];

// ✅ FIXED: Count UNREAD messages WHERE teacher_id = to_user_id
$messagesStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM messages 
    WHERE to_user_id = ? AND is_read = 0 AND sender = 'parent'
");
$messagesStmt->execute([$teacher_id]);
$unread_messages = $messagesStmt->fetchColumn() ?: 0;

// Count active students
$totalStudentsStmt = $db->prepare("
    SELECT COUNT(*) as total 
    FROM students 
    WHERE status = 'Active'
");
$totalStudentsStmt->execute();
$total_students = $totalStudentsStmt->fetchColumn();

// Today's attendance summary
$attendanceSummaryStmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT s.id) as total_students,
        COALESCE(SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END), 0) as present_count,
        COUNT(DISTINCT s.id) - COALESCE(SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END), 0) as absent_count
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND DATE(a.date) = CURDATE()
    WHERE s.status = 'Active'
");
$attendanceSummaryStmt->execute();
$attendance_summary = $attendanceSummaryStmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent student with parent info
$studentStmt = $db->prepare("
    SELECT s.*, u.name as parent_name, u.phone as parent_phone 
    FROM students s
    LEFT JOIN users u ON s.parent_id = u.id
    WHERE s.status = 'Active' 
    ORDER BY s.name ASC 
    LIMIT 1
");
$studentStmt->execute();
$recent_student = $studentStmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="flex min-h-screen">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 p-12">
        <div class="max-w-7xl mx-auto space-y-12">
            <!-- Welcome Header -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-10 border border-white/50">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-5xl font-black bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-3">
                            Welcome Back, <?php echo htmlspecialchars($_SESSION['name']); ?>!
                        </h1>
                        <p class="text-2xl text-gray-600">Teacher Dashboard - <?php echo date('F j, Y'); ?></p>
                        <div class="flex flex-wrap gap-4 mt-4">
                            <span class="px-4 py-2 bg-emerald-100 text-emerald-800 rounded-full text-lg font-bold shadow-md">
                                <?php echo $total_students; ?> Active Students
                            </span>
                            <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-lg font-bold shadow-md">
                                <?php echo $attendance_summary['present_count'] ?? 0; ?>/<?php echo $total_students; ?> Present
                            </span>
                            <?php if ($unread_messages > 0): ?>
                            <span class="px-4 py-2 bg-pink-100 text-pink-800 rounded-full text-lg font-bold shadow-md animate-pulse">
                                <?php echo $unread_messages; ?> New Messages
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="w-32 h-32 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl flex items-center justify-center shadow-2xl">
                        <i class="fas fa-chalkboard-teacher text-5xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Total Students -->
                <div class="group bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-500 cursor-default">
                    <div class="flex items-center">
                        <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-users text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-5xl font-black"><?php echo $total_students; ?></p>
                            <p class="text-indigo-100 font-semibold text-xl mt-1">Active Students</p>
                        </div>
                    </div>
                </div>

                <!-- Present Today -->
                <div class="group bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-500 cursor-default">
                    <div class="flex items-center">
                        <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-check-circle text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-5xl font-black"><?php echo $attendance_summary['present_count'] ?? 0; ?></p>
                            <p class="text-emerald-100 font-semibold text-xl mt-1">Present Today</p>
                        </div>
                    </div>
                </div>

                <!-- Unread Messages -->
                <a href="messages.php" class="group relative">
                    <div class="bg-gradient-to-br from-pink-500 to-rose-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-500">
                        <?php if ($unread_messages > 0): ?>
                        <div class="absolute -top-3 -right-3 bg-red-500 text-white w-12 h-12 rounded-3xl flex items-center justify-center text-xl font-bold shadow-2xl animate-bounce">
                            <?php echo $unread_messages; ?>
                        </div>
                        <?php endif; ?>
                        <div class="flex items-center">
                            <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-6 group-hover:rotate-12 transition-all duration-500">
                                <i class="fas fa-envelope-open-text text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-5xl font-black"><?php echo $unread_messages; ?></p>
                                <p class="text-pink-100 font-semibold text-xl mt-1">New Messages</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <a href="attendance.php" class="group">
                    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 text-white p-12 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500 text-center">
                        <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-3xl mx-auto mb-6 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-calendar-check text-4xl"></i>
                        </div>
                        <h3 class="text-3xl font-black mb-4">Take Attendance</h3>
                        <p class="font-semibold text-xl">Mark today's attendance</p>
                    </div>
                </a>

                <a href="students.php" class="group">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-12 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500 text-center">
                        <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-3xl mx-auto mb-6 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-users text-4xl"></i>
                        </div>
                        <h3 class="text-3xl font-black mb-4">All Students</h3>
                        <p class="font-semibold text-xl">(<?php echo $total_students; ?>)</p>
                    </div>
                </a>

                <a href="messages.php" class="group">
                    <div class="bg-gradient-to-br from-purple-500 to-purple-700 text-white p-12 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500 text-center">
                        <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-3xl mx-auto mb-6 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-comments text-4xl"></i>
                        </div>
                        <h3 class="text-3xl font-black mb-4">Messages</h3>
                        <p class="font-semibold text-xl"><?php echo $unread_messages; ?> New</p>
                    </div>
                </a>
            </div>

            <!-- Featured Student -->
            <?php if ($recent_student): ?>
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-10 border border-white/50">
                <h3 class="text-3xl font-bold mb-8 text-gray-900 flex items-center gap-3">
                    <i class="fas fa-user-graduate text-3xl text-indigo-600"></i>
                    Featured Student
                </h3>
                
                <?php
                $student_attendance = null;
                $studentAttendanceStmt = $db->prepare("SELECT * FROM attendance WHERE student_id = ? AND DATE(date) = CURDATE()");
                $studentAttendanceStmt->execute([$recent_student['id']]);
                $student_attendance = $studentAttendanceStmt->fetch(PDO::FETCH_ASSOC);
                ?>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                    <!-- Student Profile -->
                    <div class="text-center lg:text-left">
                        <div class="relative inline-block mb-6 lg:mb-0">
                             <img src="../assets/images/students/<?php echo htmlspecialchars($student['profile_image'] ?? 'student_default.jpg'); ?>" 
                                 class="w-48 h-48 lg:w-64 lg:h-64 mx-auto lg:mx-0 rounded-3xl object-cover shadow-2xl border-8 border-white ring-8 ring-indigo-100/50">
                            <div class="absolute -bottom-4 left-1/2 lg:left-0 -translate-x-1/2 lg:translate-x-0 bg-gradient-to-r from-indigo-500 to-purple-600 p-4 rounded-3xl shadow-2xl border-4 border-white w-20 h-20 flex items-center justify-center">
                                <i class="fas fa-crown text-2xl text-white"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Student Details -->
                    <div class="space-y-6">
                        <div>
                            <h4 class="text-4xl lg:text-5xl font-black text-gray-900 mb-2">
                                <?php echo htmlspecialchars($recent_student['name']); ?>
                            </h4>
                            <div class="flex flex-wrap gap-3">
                                <span class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-2xl text-xl font-bold shadow-lg">
                                    <?php echo htmlspecialchars($recent_student['grade_level']); ?>
                                </span>
                                <span class="px-4 py-2 bg-gray-200 text-gray-800 rounded-xl font-semibold text-lg">
                                    <?php echo htmlspecialchars($recent_student['student_id']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-lg">
                            <div class="space-y-2">
                                <span class="text-gray-600 font-semibold block">Gender</span>
                                <span class="font-bold text-2xl capitalize"><?php echo htmlspecialchars($recent_student['gender'] ?? 'N/A'); ?></span>
                            </div>
                            <?php if ($recent_student['parent_name']): ?>
                            <div class="space-y-2">
                                <span class="text-gray-600 font-semibold block">Parent</span>
                                <span class="font-bold text-xl"><?php echo htmlspecialchars($recent_student['parent_name']); ?></span>
                            </div>
                            <div class="space-y-2">
                                <span class="text-gray-600 font-semibold block">Contact</span>
                                <span class="font-bold text-xl"><?php echo htmlspecialchars($recent_student['parent_phone'] ?? $recent_student['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Attendance Status -->
                        <div class="pt-6 border-t border-gray-200 flex items-center gap-6">
                            <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-gray-100 to-gray-200 rounded-2xl">
                                <i class="fas fa-calendar-day text-2xl text-gray-700"></i>
                                <span class="font-bold text-xl text-gray-800">Today's Status:</span>
                            </div>
                            <span class="px-8 py-4 rounded-2xl text-2xl font-black shadow-2xl text-white min-w-[160px] text-center <?php 
                                echo ($student_attendance && $student_attendance['status'] == 'Present') 
                                    ? 'bg-green-500' 
                                    : 'bg-red-500'; 
                            ?>">
                                <?php echo htmlspecialchars($student_attendance['status'] ?? 'No Record'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>