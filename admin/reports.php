<?php 
session_start();

$page_title = "Admin Reports";
include '../includes/header.php';
require_once '../config/database.php';

// FIXED Session validation
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Date Filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// USER STATS
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_admins = $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$total_teachers = $db->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$total_parents = $db->query("SELECT COUNT(*) FROM users WHERE role='parent'")->fetchColumn();
$total_students = $db->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetchColumn();

// ATTENDANCE STATS (Date Range)
$attendance_query = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) as absent,
    SUM(CASE WHEN status='Late' THEN 1 ELSE 0 END) as late,
    SUM(CASE WHEN status='Excused' THEN 1 ELSE 0 END) as excused
    FROM attendance WHERE DATE(date) BETWEEN ? AND ?");
$attendance_query->execute([$start_date, $end_date]);
$attendance_stats = $attendance_query->fetch();

$attendance_rate = 0;
if ($attendance_stats && $attendance_stats['total'] > 0) {
    $attendance_rate = round(($attendance_stats['present'] / $attendance_stats['total']) * 100, 1);
}

// OTHER STATS
$low_stock = $db->query("SELECT COUNT(*) FROM inventory WHERE quantity <= min_stock")->fetchColumn();
$unread_messages = $db->query("SELECT COUNT(*) FROM messages WHERE is_read = FALSE")->fetchColumn();

// Charts Data
$monthly_attendance = $db->prepare("SELECT 
    MONTH(date) month, YEAR(date) year, DATE_FORMAT(date, '%M %Y') month_name,
    COUNT(*) total,
    SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) present
    FROM attendance 
    WHERE date BETWEEN ? AND ?
    GROUP BY MONTH(date), YEAR(date)
    ORDER BY date DESC LIMIT 6");
$monthly_attendance->execute([$start_date, $end_date]);
$monthly_data = $monthly_attendance->fetchAll();

$attendance_breakdown = [$attendance_stats['present'] ?? 0, $attendance_stats['absent'] ?? 0, $attendance_stats['late'] ?? 0, $attendance_stats['excused'] ?? 0];
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
                            Analytics Reports
                        </h1>
                        <p class="text-2xl text-gray-600"><?php echo date('F j, Y', strtotime($start_date)); ?> - <?php echo date('F j, Y', strtotime($end_date)); ?></p>
                    </div>
                    <div class="w-32 h-32 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl flex items-center justify-center shadow-2xl">
                        <i class="fas fa-chart-line text-5xl text-white drop-shadow-lg"></i>
                    </div>
                </div>

                <!-- Date Filter -->
                <div class="bg-gradient-to-r from-blue-500/20 to-purple-500/20 backdrop-blur-xl border border-blue-200/50 rounded-2xl p-6 mt-8 flex items-center gap-4">
                    <form method="GET" class="flex flex-1 items-center gap-4">
                        <div>
                            <label class="block text-lg font-semibold text-white/90 mb-2">Start Date</label>
                            <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                                   class="w-48 p-4 bg-white/80 rounded-xl border-2 border-white/50 focus:border-blue-400 focus:ring-4 focus:ring-blue-200 text-lg font-semibold">
                        </div>
                        <div>
                            <label class="block text-lg font-semibold text-white/90 mb-2">End Date</label>
                            <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                                   class="w-48 p-4 bg-white/80 rounded-xl border-2 border-white/50 focus:border-blue-400 focus:ring-4 focus:ring-blue-200 text-lg font-semibold">
                        </div>
                        <button type="submit" 
                                class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-12 py-6 rounded-2xl text-xl font-bold hover:shadow-2xl hover:-translate-y-1 transition-all duration-500 whitespace-nowrap flex-shrink-0">
                            <i class="fas fa-search mr-2"></i>Update Report
                        </button>
                    </form>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                <div class="group">
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500 cursor-default">
                        <div class="flex items-center">
                            <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-6 group-hover:rotate-12 transition-transform duration-500">
                                <i class="fas fa-users text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-4xl font-black"><?php echo number_format($total_users); ?></p>
                                <p class="text-indigo-100 font-semibold text-lg">Total Users</p>
                                <div class="text-indigo-200 text-sm mt-1">Admin: <?php echo $total_admins; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="group">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500 cursor-default">
                        <div class="flex items-center">
                            <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-6 group-hover:rotate-12 transition-transform duration-500">
                                <i class="fas fa-user-graduate text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-4xl font-black"><?php echo number_format($total_students); ?></p>
                                <p class="text-emerald-100 font-semibold text-lg">Active Students</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="group">
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500 cursor-default">
                        <div class="flex items-center">
                            <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-6 group-hover:rotate-12 transition-transform duration-500">
                                <i class="fas fa-chart-pie text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-4xl font-black"><?php echo $attendance_rate; ?><span class="text-2xl">%</span></p>
                                <p class="text-emerald-100 font-semibold text-lg">Attendance Rate</p>
                                <div class="text-emerald-200 text-sm"><?php echo number_format($attendance_stats['total'] ?? 0); ?> records</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="group">
                    <div class="bg-gradient-to-br from-orange-500 to-red-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500 cursor-default">
                        <div class="flex items-center">
                            <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-6 group-hover:rotate-12 transition-transform duration-500">
                                <i class="fas fa-exclamation-triangle text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-4xl font-black"><?php echo $low_stock; ?></p>
                                <p class="text-orange-100 font-semibold text-lg">Low Stock Items</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="group">
                    <div class="bg-gradient-to-br from-pink-500 to-rose-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-3 transition-all duration-500 cursor-default">
                        <div class="flex items-center">
                            <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-3xl flex items-center justify-center mr-6 group-hover:rotate-12 transition-transform duration-500">
                                <i class="fas fa-envelope-open-text text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-4xl font-black"><?php echo $unread_messages; ?></p>
                                <p class="text-pink-100 font-semibold text-lg">Unread Messages</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section (Exact Dashboard Style) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Monthly Attendance Trend -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-10 border border-white/50">
                    <h3 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                        <i class="fas fa-calendar-alt mr-3 text-emerald-600"></i>
                        Monthly Attendance Trend
                    </h3>
                    <div class="h-80">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>

                <!-- Attendance Breakdown -->
                <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-10 border border-white/50">
                    <h3 class="text-3xl font-bold text-gray-900 mb-8 flex items-center">
                        <i class="fas fa-chart-pie mr-3 text-purple-600"></i>
                        Attendance Breakdown
                    </h3>
                    <div class="h-80">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Actions (Dashboard Style) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="dashboard.php" class="group">
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-500 text-center">
                        <i class="fas fa-tachometer-alt text-5xl mb-4 opacity-90"></i>
                        <h3 class="text-2xl font-black mb-2">Dashboard</h3>
                        <p class="text-indigo-100 font-semibold">Live overview</p>
                    </div>
                </a>
                
                <a href="users.php" class="group">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-500 text-center">
                        <i class="fas fa-users text-5xl mb-4 opacity-90"></i>
                        <h3 class="text-2xl font-black mb-2">Manage Users</h3>
                        <p class="text-emerald-100 font-semibold">Staff & parents</p>
                    </div>
                </a>
                
                <a href="#" onclick="window.print()" class="group">
                    <div class="bg-gradient-to-br from-orange-500 to-red-600 text-white p-10 rounded-3xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-500 text-center">
                        <i class="fas fa-print text-5xl mb-4 opacity-90"></i>
                        <h3 class="text-2xl font-black mb-2">Print Report</h3>
                        <p class="text-orange-100 font-semibold">Download PDF</p>
                    </div>
                </a>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // REAL Monthly Data
    const monthlyLabels = <?php echo json_encode(array_column($monthly_data, 'month_name')); ?>;
    const monthlyRates = <?php echo json_encode(array_map(function($row) { 
        return ($row['total'] > 0) ? round(($row['present'] / $row['total']) * 100, 1) : 0;
    }, $monthly_data)); ?>;

    // REAL Attendance Breakdown
    const attendanceData = <?php echo json_encode($attendance_breakdown); ?>;

    // Monthly Trend Line Chart (Dashboard Style)
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Attendance Rate (%)',
                data: monthlyRates,
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 4,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#10B981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 3,
                pointRadius: 8,
                pointHoverRadius: 10
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
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // Attendance Doughnut Chart (Dashboard Style)
    new Chart(document.getElementById('attendanceChart'), {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Late', 'Excused'],
            datasets: [{
                data: attendanceData,
                backgroundColor: [
                    '#10B981',  // Present - Emerald
                    '#EF4444',  // Absent - Red
                    '#F59E0B',  // Late - Amber
                    '#8B5CF6'   // Excused - Violet
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
                        font: { size: 16 },
                        padding: 40,
                        usePointStyle: true
                    }
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>