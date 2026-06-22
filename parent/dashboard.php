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
// HTMLSPECIALCHARS - to prevent cross=site scripting (xss)


$page_title = "Parent Dashboard";
include '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$parent_id = $_SESSION['user_id'];

// ✅ FIXED: Count UNREAD messages for this parent (to_user_id = parent_id, sender = 'teacher')
$messagesStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM messages 
    WHERE to_user_id = ? AND is_read = 0 AND sender = 'teacher'
");
$messagesStmt->execute([$parent_id]);
$unread_messages = $messagesStmt->fetchColumn() ?: 0;

// Count active children (students linked through student_parent table)
$totalChildrenStmt = $db->prepare("
    SELECT COUNT(*) as total 
    FROM students s
    INNER JOIN student_parent sp ON s.student_id = sp.student_id
    WHERE sp.parent_id = ? AND s.status = 'Active'
");
$totalChildrenStmt->execute([$parent_id]);
$total_children = $totalChildrenStmt->fetchColumn();

// Today's attendance summary for parent's children
$attendanceSummaryStmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT s.id) as total_children,
        COALESCE(SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END), 0) as present_count,
        COUNT(DISTINCT s.id) - COALESCE(SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END), 0) as absent_count
    FROM students s
    INNER JOIN student_parent sp ON s.student_id = sp.student_id
    LEFT JOIN attendance a ON s.student_id = a.student_id AND DATE(a.date) = CURDATE()
    WHERE sp.parent_id = ? AND s.status = 'Active'
");
$attendanceSummaryStmt->execute([$parent_id]);
$attendance_summary = $attendanceSummaryStmt->fetch(PDO::FETCH_ASSOC);

// Fetch all active children with teacher info
$childStmt = $db->prepare("
    SELECT s.*, 
           p.name as parent_name,
           p.phone as parent_phone,
           t.name as teacher_name
    FROM students s
    INNER JOIN student_parent sp ON s.student_id = sp.student_id
    LEFT JOIN users p ON sp.parent_id = p.id
    LEFT JOIN users t ON t.student_id = s.student_id AND t.role = 'teacher'
    WHERE sp.parent_id = ? AND s.status = 'Active' 
    ORDER BY s.name ASC
");
$childStmt->execute([$parent_id]);
$all_children = $childStmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <p class="text-2xl text-gray-600">Parent Dashboard - <?php echo date('F j, Y'); ?></p>
                        <div class="flex flex-wrap gap-4 mt-4">
                            <span class="px-4 py-2 bg-emerald-100 text-emerald-800 rounded-full text-lg font-bold shadow-md">
                                <?php echo $total_children; ?> Active Children
                            </span>
                            <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-lg font-bold shadow-md">
                                <?php echo $attendance_summary['present_count'] ?? 0; ?>/<?php echo $total_children; ?> Present Today
                            </span>
                            <?php if ($unread_messages > 0): ?>
                            <span class="px-4 py-2 bg-pink-100 text-pink-800 rounded-full text-lg font-bold shadow-md animate-pulse">
                                <?php echo $unread_messages; ?> New Messages
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="w-32 h-32 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl flex items-center justify-center shadow-2xl">
                        <i class="fas fa-user-shield text-5xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Total Children -->
                <div class="group bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-500 cursor-default">
                    <div class="flex items-center">
                        <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-child text-3xl"></i>
                        </div>
                        <div>
                            <p class="text-5xl font-black"><?php echo $total_children; ?></p>
                            <p class="text-indigo-100 font-semibold text-xl mt-1">Active Children</p>
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
                <a href="my-children.php" class="group">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-12 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500 text-center">
                        <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-3xl mx-auto mb-6 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-child text-4xl"></i>
                        </div>
                        <h3 class="text-3xl font-black mb-4">My Children</h3>
                        <p class="font-semibold text-xl">(<?php echo $total_children; ?>)</p>
                    </div>
                </a>

                <a href="attendance.php" class="group">
                    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 text-white p-12 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500 text-center">
                        <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-3xl mx-auto mb-6 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-calendar-check text-4xl"></i>
                        </div>
                        <h3 class="text-3xl font-black mb-4">Attendance</h3>
                        <p class="font-semibold text-xl">View records</p>
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

            <!-- Children Grid -->
            <?php if (!empty($all_children)): ?>
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-10 border border-white/50">
                <h3 class="text-3xl font-bold mb-8 text-gray-900 flex items-center gap-3">
                    <i class="fas fa-users text-3xl text-emerald-600"></i>
                    Your Children
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($all_children as $child): ?>
                    <?php
                    $child_attendance = null;
                    $childAttendanceStmt = $db->prepare("SELECT * FROM attendance WHERE student_id = ? AND DATE(date) = CURDATE()");
                    $childAttendanceStmt->execute([$child['student_id']]);
                    $child_attendance = $childAttendanceStmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <div class="group bg-gradient-to-br from-white to-gray-50 rounded-3xl shadow-2xl p-8 border border-white/50 hover:shadow-3xl hover:-translate-y-2 transition-all duration-500">
                        <!-- Child Image -->
                        <div class="relative inline-block w-full mb-6">
                            <img src="../assets/images/students/<?php echo htmlspecialchars($child['profile_image'] ?? 'student_default.jpg'); ?>" 
                                class="w-full h-48 rounded-3xl object-cover shadow-xl border-4 border-white ring-4 ring-emerald-100/50 group-hover:scale-105 transition-transform">
                        </div>

                        <!-- Child Name -->
                        <div class="mb-6 text-center">
                            <h4 class="text-3xl font-black text-gray-900">
                                <?php echo htmlspecialchars($child['name']); ?>
                            </h4>
                            <span class="inline-block mt-2 px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl text-sm font-bold">
                                <?php echo htmlspecialchars($child['grade_level']); ?>
                            </span>
                        </div>

                        <!-- Child Details -->
                        <div class="space-y-3 mb-6 pb-6 border-b border-gray-200">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 font-semibold">Student ID:</span>
                                <span class="text-gray-900 font-bold"><?php echo htmlspecialchars($child['student_id']); ?></span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 font-semibold">Gender:</span>
                                <span class="text-gray-900 font-bold capitalize"><?php echo htmlspecialchars($child['gender'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 font-semibold">DOB:</span>
                                <span class="text-gray-900 font-bold"><?php echo $child['dob'] ? date('M j, Y', strtotime($child['dob'])) : 'N/A'; ?></span>
                            </div>
                            <?php if ($child['teacher_name']): ?>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 font-semibold">Teacher:</span>
                                <span class="text-gray-900 font-bold"><?php echo htmlspecialchars($child['teacher_name']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Today's Attendance -->
                        <div class="mb-6">
                            <div class="flex items-center gap-2 mb-3">
                                <i class="fas fa-calendar-day text-lg text-gray-700"></i>
                                <span class="font-bold text-gray-800">Today's Status:</span>
                            </div>
                            <span class="block px-4 py-3 rounded-2xl text-center text-xl font-black shadow-lg text-white <?php 
                                echo ($child_attendance && $child_attendance['status'] == 'Present') 
                                    ? 'bg-green-500' 
                                    : 'bg-red-500'; 
                            ?>">
                                <?php echo htmlspecialchars($child_attendance['status'] ?? 'No Record'); ?>
                            </span>
                        </div>

                        <!-- Action Links -->
                        <div class="flex gap-3">
                            <a href="attendance.php?student_id=<?php echo urlencode($child['student_id']); ?>" 
                               class="flex-1 bg-gradient-to-r from-blue-500 to-cyan-600 text-white py-2 px-4 rounded-2xl text-center text-sm font-bold shadow-lg hover:shadow-xl transition-all group-hover:scale-105">
                                <i class="fas fa-calendar-check mr-1"></i>Attendance
                            </a>
                            <a href="messages.php" 
                               class="flex-1 bg-gradient-to-r from-purple-500 to-purple-700 text-white py-2 px-4 rounded-2xl text-center text-sm font-bold shadow-lg hover:shadow-xl transition-all group-hover:scale-105">
                                <i class="fas fa-comments mr-1"></i>Message
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>