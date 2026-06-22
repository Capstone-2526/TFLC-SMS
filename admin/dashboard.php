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


$page_title = "Admin Dashboard";
include '../includes/header.php';
require_once '../config/database.php';

//  THE GATE-KEEPER - this checks if the $SESSION superglobal has the privileges, if not they are redirected to the login page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$admin_name = $_SESSION['name'] ?? 'Admin';

// Real-time Stats (Today & Overall) - ALL QUERIES SECURE
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_admins = $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$total_teachers = $db->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$total_parents = $db->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn();

$total_students = $db->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetchColumn();
$total_attendance_today = $db->query("SELECT COUNT(*) FROM attendance WHERE DATE(date)=CURDATE()")->fetchColumn();
$today_present = $db->query("SELECT COUNT(*) FROM attendance WHERE DATE(date)=CURDATE() AND status='Present'")->fetchColumn();
$low_stock = $db->query("SELECT COUNT(*) FROM inventory WHERE quantity <= min_stock")->fetchColumn();
$unread_messages = $db->query("SELECT COUNT(*) FROM messages WHERE is_read=0")->fetchColumn();

// Weekly Attendance (Last 7 days) - SECURE
$weekly_attendance = $db->query("SELECT DATE(date) as day, 
    COUNT(*) as total,
    SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) as present
    FROM attendance 
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(date)
    ORDER BY date DESC");
$weekly_data = $weekly_attendance->fetchAll();

// User Roles Distribution (Real Data)
$roles_data = [$total_admins, $total_teachers, $total_parents];
?>

<div class="flex min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-1 p-6 md:p-12">
        <div class="max-w-7xl mx-auto space-y-12">
            <!-- Welcome Header -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-8 md:p-10 border border-white/50">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-4xl md:text-5xl font-black bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-3">
                            Welcome Back, <?php echo htmlspecialchars($admin_name); ?>!
                        </h1>
                        <p class="text-xl md:text-2xl text-gray-600">School management overview - <?php echo date('F j, Y'); ?></p>
                    </div>
                    <div class="w-24 h-24 md:w-32 md:h-32 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl flex items-center justify-center shadow-2xl shrink-0">
                        <i class="fas fa-crown text-3xl md:text-5xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Stats Cards (Enhanced with real data & links) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 md:gap-8">
                <a href="users.php" class="group">
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-8 md:p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500">
                        <div class="flex items-center">
                            <div class="w-16 h-16 md:w-20 md:h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-4 md:mr-6 group-hover:rotate-12 transition-transform duration-500">
                                <i class="fas fa-users text-2xl md:text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-3xl md:text-4xl font-black"><?php echo number_format($total_users); ?></p>
                                <p class="text-indigo-100 font-semibold text-base md:text-lg">Total Users</p>
                                <div class="text-indigo-200 text-xs md:text-sm mt-1">Admin: <?php echo $total_admins; ?> | Teachers: <?php echo $total_teachers; ?></div>
                            </div>
                        </div>
                    </div>
                </a>
                
                <a href="students.php" class="group">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-8 md:p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500">
                        <div class="flex items-center">
                            <div class="w-16 h-16 md:w-20 md:h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-4 md:mr-6 group-hover:rotate-12 transition-transform duration-500">
                                <i class="fas fa-user-graduate text-2xl md:text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-3xl md:text-4xl font-black"><?php echo number_format($total_students); ?></p>
                                <p class="text-emerald-100 font-semibold text-base md:text-lg">Active Students</p>
                            </div>
                        </div>
                    </div>
                </a>
                
                <div class="group">
                    <div class="bg-gradient-to-br from-blue-500 to-cyan-600 text-white p-8 md:p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500 cursor-default">
                        <div class="flex items-center">
                            <div class="w-16 h-16 md:w-20 md:h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-4 md:mr-6 group-hover:rotate-12 transition-transform duration-500">
                                <i class="fas fa-calendar-check text-2xl md:text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-3xl md:text-4xl font-black"><?php echo $today_present; ?></p>
                                <p class="text-blue-100 font-semibold text-base md:text-lg">Present Today</p>
                                <div class="text-blue-200 text-xs md:text-sm"><?php echo $total_attendance_today; ?> total records</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="inventory.php" class="group">
                    <div class="bg-gradient-to-br from-orange-500 to-red-600 text-white p-8 md:p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500">
                        <div class="flex items-center">
                            <div class="w-16 h-16 md:w-20 md:h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-4 md:mr-6 group-hover:rotate-12 transition-transform duration-500">
                                <i class="fas fa-exclamation-triangle text-2xl md:text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-3xl md:text-4xl font-black"><?php echo $low_stock; ?></p>
                                <p class="text-orange-100 font-semibold text-base md:text-lg">Low Stock Items</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Unread Messages -->
                <a href="messages.php" class="group">
                    <div class="bg-gradient-to-br from-pink-500 to-rose-600 text-white p-8 md:p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500">
                        <div class="flex items-center">
                            <div class="w-16 h-16 md:w-20 md:h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-4 md:mr-6 group-hover:rotate-12 transition-transform duration-500">
                                <i class="fas fa-envelope-open-text text-2xl md:text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-3xl md:text-4xl font-black"><?php echo $unread_messages; ?></p>
                                <p class="text-pink-100 font-semibold text-base md:text-lg">Unread Messages</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Dynamic Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
                <!-- User Roles Distribution (REAL DATA) -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-8 md:p-10 border border-white/50">
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6 md:mb-8 flex items-center">
                        <i class="fas fa-users mr-3 text-indigo-600"></i>
                        User Roles Distribution
                    </h3>
                    <div class="h-64 md:h-80">
                        <canvas id="roleChart"></canvas>
                    </div>
                </div>

                <!-- Weekly Attendance (REAL DATA - Last 7 days) -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-8 md:p-10 border border-white/50">
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6 md:mb-8 flex items-center">
                        <i class="fas fa-calendar-week mr-3 text-emerald-600"></i>
                        Attendance This Week
                    </h3>
                    <div class="h-64 md:h-80">
                        <canvas id="weeklyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="students.php" class="group">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-8 md:p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-500 text-center">
                        <i class="fas fa-user-graduate text-4xl md:text-5xl mb-3 md:mb-4 opacity-90"></i>
                        <h3 class="text-xl md:text-2xl font-black mb-1 md:mb-2">Manage Students</h3>
                        <p class="text-emerald-100 font-semibold">View all students & attendance</p>
                    </div>
                </a>
                
                <a href="inventory.php" class="group">
                    <div class="bg-gradient-to-br from-orange-500 to-red-600 text-white p-8 md:p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-500 text-center">
                        <i class="fas fa-boxes text-4xl md:text-5xl mb-3 md:mb-4 opacity-90"></i>
                        <h3 class="text-xl md:text-2xl font-black mb-1 md:mb-2">Inventory</h3>
                        <p class="text-orange-100 font-semibold">Check stock & reorder items</p>
                    </div>
                </a>
                
                <a href="reports.php" class="group">
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-8 md:p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-500 text-center">
                        <i class="fas fa-chart-bar text-4xl md:text-5xl mb-3 md:mb-4 opacity-90"></i>
                        <h3 class="text-xl md:text-2xl font-black mb-1 md:mb-2">View Reports</h3>
                        <p class="text-indigo-100 font-semibold">Detailed analytics & insights</p>
                    </div>
                </a>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // REAL User Roles Data
    const rolesData = <?php echo json_encode($roles_data); ?>;
    
    // REAL Weekly Attendance Data (Last 7 days)
    const weeklyLabels = <?php 
        echo json_encode(array_map(function($row) { 
            return date('M d', strtotime($row['day'])); 
        }, $weekly_data)); 
    ?>;
    const weeklyPresent = <?php 
        echo json_encode(array_map(function($row) { 
            return $row['total'] > 0 ? round(($row['present'] / $row['total']) * 100, 1) : 0; 
        }, $weekly_data)); 
    ?>;

    // User Roles Doughnut Chart (REAL DATA)
    const roleCtx = document.getElementById('roleChart').getContext('2d');
    new Chart(roleCtx, {
        type: 'doughnut',
        data: {
            labels: ['Admins', 'Teachers', 'Parents'],
            datasets: [{
                data: rolesData,
                backgroundColor: [
                    '#8B5CF6', // Indigo
                    '#10B981', // Emerald  
                    '#F59E0B'  // Amber
                ],
                borderWidth: 0,
                borderRadius: 25,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { size: 14, family: 'Inter' },
                        padding: 30,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            }
        }
    });

    // Weekly Attendance Bar Chart (REAL DATA)
    const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
    new Chart(weeklyCtx, {
        type: 'bar',
        data: {
            labels: weeklyLabels,
            datasets: [{
                label: 'Attendance Rate (%)',
                data: weeklyPresent,
                backgroundColor: '#10B981',
                borderRadius: 12,
                borderSkipped: false,
                barThickness: 35
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { font: { size: 12 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 13 } }
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>