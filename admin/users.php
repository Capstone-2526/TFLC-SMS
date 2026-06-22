<?php
session_start();

$page_title = "Users Management";
include '../includes/header.php';
require_once '../config/database.php';

// FIXED Session validation
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();


// HANDLE UPDATE USER ✅ (PUT THIS FIRST OR SEPARATE)
if ($_POST && isset($_POST['update_user'])) {
    $id = (int)$_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];

    $stmt = $db->prepare("UPDATE users SET name=?, email=?, phone=?, role=? WHERE id=?");
    if ($stmt->execute([$name, $email, $phone, $role, $id])) {
        $success = "User updated successfully!";
    } else {
        $error = "Failed to update user!";
    }
}

// Handle Add User
if ($_POST && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Check username uniqueness
    $check = $db->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->rowCount() > 0) {
        $error = "Username already exists!";
    } else {
        $profile_image = 'default.jpg';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $target_dir = "../assets/images/" . strtolower($role) . "s/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            
            $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($file_extension, $allowed) && $_FILES['profile_image']['size'] < 2000000) {
                $new_filename = $username . '_' . time() . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $profile_image = $new_filename;
                }
            }
        }
        
        $stmt = $db->prepare("INSERT INTO users (username, password, role, name, email, phone, profile_image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$username, $password, $role, $name, $email, $phone, $profile_image])) {
            $success = "User added successfully!";
        }
    }
}

// **FIXED Delete User Section** - Lines 350-351
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete']; // Cast to integer for safety
    
    // **FIX 1: Check if user exists first**
    $stmt = $db->prepare("SELECT role, profile_image FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // **FIX 2: Check if $user is array before accessing**
    if ($user !== false && is_array($user) && $user['profile_image'] !== 'default.jpg') {
        $image_path = "../assets/images/" . strtolower($user['role']) . "s/" . $user['profile_image'];
        // **FIX 3: Check if it's a file, not directory**
        if (file_exists($image_path) && is_file($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete user
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "User deleted successfully!";
    } else {
        $error = "Failed to delete user!";
    }
}

$edit_user = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch All Users
$stmt = $db->query("SELECT * FROM users ORDER BY role, name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex min-h-screen">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-1 p-12 overflow-auto">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-12">
                <div>
                    <h1 class="text-5xl font-black bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-3">
                        Users Management
                    </h1>
                    <p class="text-xl text-gray-600">Manage teachers, parents, and admin accounts</p>
                </div>
                <button onclick="openAddModal()" 
                        class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-10 py-5 rounded-3xl font-bold text-xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                    <i class="fas fa-plus mr-3"></i>Add User
                </button>
            </div>

            <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-8 py-6 rounded-3xl mb-12 shadow-lg animate-pulse">
                <i class="fas fa-check-circle text-2xl mr-4"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-8 py-6 rounded-3xl mb-12 shadow-lg animate-pulse">
                <i class="fas fa-exclamation-circle text-2xl mr-4"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- Users Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($users as $user): ?>
                <div class="group bg-white/80 backdrop-blur-xl rounded-3xl shadow-xl p-8 hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 border border-white/50 relative overflow-hidden">
                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-all duration-300 flex space-x-2 z-20">
                        <a href="?edit=<?php echo $user['id']; ?>" 
                           class="w-14 h-14 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-purple-600 text-white rounded-3xl shadow-xl hover:shadow-2xl hover:scale-110 transition-all duration-300 flex items-center justify-center"
                           title="Edit User">
                            <i class="fas fa-edit text-xl"></i>
                        </a>
                        <a href="?delete=<?php echo $user['id']; ?>&confirm=1" 
                           onclick="return confirm('Delete <?php echo htmlspecialchars($user['name']); ?>?');"
                           class="w-14 h-14 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-red-700 text-white rounded-3xl shadow-xl hover:shadow-2xl hover:scale-110 transition-all duration-300 flex items-center justify-center">
                            <i class="fas fa-trash text-xl"></i>
                        </a>
                    </div>
                    
                    <!-- Profile Image - BULLETPROOF VERSION -->
<div class="text-center mb-8">
    <div class="relative inline-block">
        <?php 
        // Generate correct image path
        $role_folder = strtolower($user['role']) . 's';
        $image_path = "../assets/images/{$role_folder}/" . htmlspecialchars($user['profile_image']);
        
        // Role-specific default images
        $role_defaults = [
            'admin' => "../assets/images/admins/default.jpg",
            'teacher' => "../assets/images/teachers/default.jpg", 
            'parent' => "../assets/images/parents/default.jpg"
        ];
        
        $default_image = $role_defaults[$user['role']] ?? "../assets/images/default.jpg";
        ?>
        
        <img src="<?php echo $image_path; ?>" 
             alt="<?php echo htmlspecialchars($user['name']); ?>" 
             class="w-32 h-32 rounded-3xl shadow-2xl object-cover border-6 border-white group-hover:scale-110 transition-transform duration-500 mx-auto"
             onerror="this.src='<?php echo $default_image; ?>'; this.onerror=null;">
        
        <!-- Role badge -->
        <span class="absolute -bottom-2 -right-2 w-10 h-10 bg-gradient-to-r from-<?php echo $user['role']=='admin'?'indigo':($user['role']=='teacher'?'green':'purple'); ?>-500 to-<?php echo $user['role']=='admin'?'purple':($user['role']=='teacher'?'emerald':'pink'); ?>-500 rounded-3xl flex items-center justify-center text-white text-xl font-bold shadow-2xl">
            <?php echo strtoupper(substr($user['role'], 0, 1)); ?>
        </span>
    </div>
    <h3 class="text-2xl font-black text-gray-900 mt-6 mb-2"><?php echo htmlspecialchars($user['name']); ?></h3>
    <p class="text-gray-600 font-semibold"><?php echo htmlspecialchars($user['username']); ?></p>
</div>
                    
                    <!-- Details -->
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-envelope text-indigo-500 mr-3"></i>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="flex items-center text-gray-700">
                            <i class="fas fa-phone text-green-500 mr-3"></i>
                            <span><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <?php if (isset($user['created_at'])): ?>
                        <div class="flex items-center">
                            <i class="fas fa-calendar text-blue-500 mr-3"></i>
                            <span class="text-sm text-gray-500">Created: <?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Role Badge -->
                    <div class="flex justify-center">
                        <span class="px-6 py-3 bg-gradient-to-r 
                            <?php 
                            echo $user['role']=='admin' ? 'from-indigo-500 to-purple-600' : 
                                 ($user['role']=='teacher' ? 'from-green-500 to-emerald-600' : 'from-purple-500 to-pink-600'); 
                            ?> 
                            text-white font-bold rounded-2xl shadow-lg text-lg">
                            <?php echo ucfirst($user['role']); ?> Account
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<!-- Add User Modal -->
<div id="addModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-8 z-50" onclick="this.classList.add('hidden')">
    <div class="bg-white rounded-3xl shadow-2xl p-12 max-w-2xl w-full max-h-[90vh] overflow-y-auto mx-4" onclick="event.stopPropagation()">
        <h2 class="text-4xl font-black text-gray-900 mb-10">Add New User</h2>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div>
                    <label class="block text-gray-700 font-bold mb-4 text-xl">Full Name *</label>
                    <input type="text" name="name" required class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl transition-all">
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-4 text-xl">Username *</label>
                    <input type="text" name="username" required class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl transition-all">
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-4 text-xl">Email *</label>
                    <input type="email" name="email" required class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl transition-all">
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-4 text-xl">Phone *</label>
                    <input type="tel" name="phone" required class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl transition-all">
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-4 text-xl">Password *</label>
                    <input type="password" name="password" required minlength="6" class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl transition-all">
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-4 text-xl">Role *</label>
                    <select name="role" required class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl">
                        <option value="">Select Role</option>
                        <option value="admin">Administrator</option>
                        <option value="teacher">Teacher</option>
                        <option value="parent">Parent</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-bold mb-4 text-xl flex items-center">Profile Image (Optional)
                        <i class="fas fa-image ml-2 text-indigo-500"></i>
                    </label>
                    <input type="file" name="profile_image" accept="image/*" class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-lg file:font-semibold file:bg-indigo-50 file:text-indigo-700">
                </div>
            </div>
            <div class="flex gap-4">
                <button type="submit" name="add_user" class="flex-1 bg-gradient-to-r from-emerald-500 to-teal-600 text-white py-6 rounded-3xl font-black text-2xl shadow-2xl hover:shadow-3xl hover:-translate-y-1 transition-all duration-300">
                    <i class="fas fa-user-plus mr-3"></i>Create User
                </button>
                <button type="button" onclick="this.parentElement.parentElement.parentElement.classList.add('hidden')" 
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-6 rounded-3xl font-bold text-2xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                    <i class="fas fa-times mr-3"></i>Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($edit_user): ?>
<div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-8 z-50">
    <div class="bg-white rounded-3xl shadow-2xl p-12 max-w-2xl w-full">

        <h2 class="text-4xl font-black mb-10">Edit User</h2>

        <form method="POST">
            <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <input type="text" name="name" value="<?php echo $edit_user['name']; ?>" required
                    class="p-4 border rounded-xl">

                <input type="email" name="email" value="<?php echo $edit_user['email']; ?>" required
                    class="p-4 border rounded-xl">

                <input type="text" name="phone" value="<?php echo $edit_user['phone']; ?>"
                    class="p-4 border rounded-xl">

                <select name="role" class="p-4 border rounded-xl">
                    <option value="admin" <?php if($edit_user['role']=='admin') echo 'selected'; ?>>Admin</option>
                    <option value="teacher" <?php if($edit_user['role']=='teacher') echo 'selected'; ?>>Teacher</option>
                    <option value="parent" <?php if($edit_user['role']=='parent') echo 'selected'; ?>>Parent</option>
                </select>

            </div>

            <div class="flex gap-4 mt-8">
                <button type="submit" name="update_user"
                    class="flex-1 bg-blue-500 text-white py-4 rounded-xl font-bold">
                    Update
                </button>

                <a href="users.php"
                    class="flex-1 bg-gray-500 text-white py-4 rounded-xl text-center font-bold">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>
<?php endif; ?>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>