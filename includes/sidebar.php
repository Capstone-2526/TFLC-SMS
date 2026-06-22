<?php 
$role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-72 bg-white/80 backdrop-blur-xl shadow-2xl rounded-3xl p-8 sticky top-0 h-screen overflow-y-auto border border-white/50">
    
    <!-- Profile Section -->
    <div class="mb-12">
        <div class="text-center">
            <img src="../assets/images/<?php echo $role; ?>s/<?php echo $_SESSION['profile_image'] ?? 'default.jpg'; ?>" 
                 alt="Profile" class="w-24 h-24 rounded-3xl mx-auto shadow-xl border-4 border-indigo-200 mb-4 object-cover">
            
            <h3 class="font-bold text-xl text-gray-800">
                <?php echo $_SESSION['name']; ?>
            </h3>

            <span class="inline-block px-4 py-1 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-sm rounded-full font-semibold mt-2">
                <?php echo ucfirst($role); ?>
            </span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="space-y-3 mb-8">

        <!-- Dashboard (All Roles) -->
        <a href="../<?php echo $role; ?>/dashboard.php" 
           class="flex items-center space-x-4 p-4 rounded-2xl font-semibold transition-all duration-200 
           <?php echo strpos($current_page, 'dashboard') !== false 
           ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg' 
           : 'text-gray-700 hover:bg-indigo-50 hover:text-indigo-600'; ?>">
            
            <i class="fas fa-tachometer-alt text-xl"></i>
            <span>Dashboard</span>
        </a>

        <!-- ADMIN MENU -->
        <?php if($role == 'admin'): ?>

        <a href="../admin/students.php" 
           class="flex items-center space-x-4 p-4 rounded-2xl font-semibold transition-all duration-200 
           <?php echo $current_page == 'students.php' 
           ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg' 
           : 'text-gray-700 hover:bg-green-50 hover:text-green-600'; ?>">
            
            <i class="fas fa-users text-xl"></i>
            <span>Students</span>
        </a>

        <a href="../admin/inventory.php" 
           class="flex items-center space-x-4 p-4 rounded-2xl font-semibold transition-all duration-200 
           <?php echo $current_page == 'inventory.php' 
           ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-lg' 
           : 'text-gray-700 hover:bg-orange-50 hover:text-orange-600'; ?>">
            
            <i class="fas fa-boxes text-xl"></i>
            <span>Inventory</span>
        </a>

        <!-- ✅ FIXED REPORTS -->
        <a href="../admin/reports.php" 
           class="flex items-center space-x-4 p-4 rounded-2xl font-semibold transition-all duration-200 
           <?php echo $current_page == 'reports.php' 
           ? 'bg-gradient-to-r from-blue-500 to-cyan-600 text-white shadow-lg' 
           : 'text-gray-700 hover:bg-indigo-50 hover:text-indigo-600'; ?>">
            
            <i class="fas fa-chart-bar text-xl"></i>
            <span>Reports</span>
        </a>

        <?php endif; ?>

        <!-- TEACHER MENU -->
        <?php if($role == 'teacher'): ?>

        <a href="../teacher/attendance.php" 
           class="flex items-center space-x-4 p-4 rounded-2xl font-semibold transition-all duration-200 
           <?php echo $current_page == 'attendance.php' 
           ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg' 
           : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600'; ?>">
            
            <i class="fas fa-calendar-check text-xl"></i>
            <span>Attendance</span>
        </a>

        <a href="../teacher/students.php" 
           class="flex items-center space-x-4 p-4 rounded-2xl font-semibold transition-all duration-200 
           <?php echo $current_page == 'students.php' 
           ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-lg' 
           : 'text-gray-700 hover:bg-green-50 hover:text-green-600'; ?>">
            
            <i class="fas fa-users text-xl"></i>
            <span>Students</span>
        </a>

        <?php endif; ?>

        <!-- PARENT MENU -->
        <?php if($role == 'parent'): ?>

        <a href="../parent/messages.php" 
           class="flex items-center space-x-4 p-4 rounded-2xl font-semibold transition-all duration-200 
           <?php echo $current_page == 'messages.php' 
           ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg' 
           : 'text-gray-700 hover:bg-purple-50 hover:text-purple-600'; ?>">
            
            <i class="fas fa-envelope text-xl"></i>
            <span>Messages</span>
        </a>

        <?php endif; ?>

    </nav>

    <!-- Logout Button - Added at bottom -->
    <div class="mt-auto pt-8 border-t border-gray-200">
        <a href="../logout.php" 
           class="flex items-center space-x-4 p-4 rounded-2xl font-semibold transition-all duration-200 
           bg-gradient-to-r from-rose-500 to-red-600 text-white shadow-lg hover:from-rose-600 hover:to-red-700 
           hover:shadow-xl hover:scale-[1.02] transform active:scale-[0.98] group">
            
            <i class="fas fa-sign-out-alt text-xl group-hover:-rotate-180 transition-transform duration-300"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>