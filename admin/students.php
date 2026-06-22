<?php 
session_start();

$page_title = "Students Management - Admin Only";
include '../includes/header.php';
require_once '../config/database.php';

// Check admin session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle delete student with related records
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    try {
        // Start transaction
        $db->beginTransaction();

        // Delete related records in student_parent table
        $stmt = $db->prepare("DELETE FROM student_parent WHERE student_id = ?");
        $stmt->execute([$delete_id]);

        // Delete the student record
        $stmt = $db->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->execute([$delete_id]);

        // Commit transaction
        $db->commit();

        header("Location: students.php");
        exit();
    } catch (Exception $e) {
        // Rollback on error
        $db->rollBack();
        $error = "Failed to delete student: " . $e->getMessage();
    }
}


$success = '';
$error = '';


// Handle add student form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $student_id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $grade_level = trim($_POST['grade_level']);
    $parent_phone = trim($_POST['parent_phone']);
    $status = $_POST['status'];
    // New fields
    $dob = $_POST['dob'];
    $admission_date = $_POST['admission_date'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];

    $profile_image = 'student_default.jpg'; // default
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileSize = $_FILES['profile_image']['size'];
        $fileType = $_FILES['profile_image']['type'];
        $fileNameCmps = explode('.', $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $allowedfileExtensions = ['jpg', 'jpeg', 'png'];
        if (in_array($fileExtension, $allowedfileExtensions) && $fileSize <= 2 * 1024 * 1024) {
            $newFileName = $student_id . '.' . $fileExtension;
            $dest_path = '../assets/images/students/' . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $profile_image = $newFileName;
            } else {
                $error = "Error moving uploaded file.";
            }
        } else {
            $error = "Invalid file type or size.";
        }
    }

    if (empty($error)) {
        $stmt = $db->prepare("INSERT INTO students (student_id, name, grade_level, parent_phone, profile_image, status, dob, admission_date, gender, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$student_id, $name, $grade_level, $parent_phone, $profile_image, $status, $dob, $admission_date, $gender, $address])) {
            $success = "Student added successfully!";
        } else {
            $error = "Failed to add student.";
        }
    }
}

// Handle save edits to student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit'])) {
    $student_id = $_POST['student_id'];
    $name = trim($_POST['name']);
    $grade_level = $_POST['grade_level'];
    $parent_phone = $_POST['parent_phone'];
    $status = $_POST['status'];
    $dob = $_POST['dob'];
    $admission_date = $_POST['admission_date'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];

    $stmt = $db->prepare("UPDATE students SET name=?, grade_level=?, parent_phone=?, status=?, dob=?, admission_date=?, gender=?, address=? WHERE student_id=?");
    if ($stmt->execute([$name, $grade_level, $parent_phone, $status, $dob, $admission_date, $gender, $address, $student_id])) {
        $success = "Student details updated successfully!";
    } else {
        $error = "Failed to update student.";
    }
}

// Fetch all students
$stmt = $db->query("SELECT * FROM students ORDER BY name ASC");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if editing
$edit_student = null;
if (isset($_GET['edit'])) {
    $student_id = $_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $edit_student = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="flex min-h-screen">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="flex-1 p-12">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-10 border border-white/50 mb-12">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-5xl font-black bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-2">
                            👨‍🎓 Students Management
                        </h1>
                        <p class="text-2xl text-gray-600"></p>
                    </div>
                    <button onclick="openAddModal()" class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-12 py-6 rounded-3xl font-bold text-2xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-300">
                        <i class="fas fa-plus mr-3"></i>Add Student
                    </button>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="bg-gradient-to-r from-green-400 to-emerald-500 text-white p-8 rounded-3xl shadow-2xl mb-12 animate-pulse">
                <i class="fas fa-check-circle text-3xl mr-4"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-gradient-to-r from-red-400 to-red-600 text-white p-8 rounded-3xl shadow-2xl mb-12">
                <i class="fas fa-exclamation-triangle mr-3"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Students Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <?php foreach($students as $student): ?>
                <div class="group bg-white/90 backdrop-blur-xl rounded-3xl shadow-xl p-8 hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 border-2 border-white/50 hover:border-indigo-300 relative overflow-hidden">
                    
                    <!-- Admin Action Buttons -->
<div class="absolute top-4 right-4 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 opacity-0 group-hover:opacity-100 transition-all duration-300 z-20">
    
    <!-- Update Form -->
    <form method="POST" enctype="multipart/form-data" class="inline-block" style="z-index: 30;">
        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
        <input type="file" name="profile_image" accept="image/*" class="hidden" id="img<?php echo $student['student_id']; ?>">
        
        <div class="flex space-x-1">
            <!-- Image Upload -->
            <label for="img<?php echo $student['student_id']; ?>" class="cursor-pointer block">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-purple-600 text-white rounded-2xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center group/image">
                    <i class="fas fa-camera text-lg"></i>
                </div>
            </label>
            
            <!-- Save Button -->
            <button type="submit" name="update_student" class="w-12 h-12 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-emerald-700 text-white rounded-2xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center">
                <i class="fas fa-check text-lg"></i>
            </button>
        </div>
    </form>

    <!-- edit Button -->
    <a href="?edit=<?php echo $student['student_id']; ?>" 
       class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-purple-600 text-white rounded-3xl shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-200 flex items-center justify-center"
       title="Edit Student">
       <i class="fas fa-edit text-xl"></i>
    </a>

    <!-- Delete Button -->
    <a href="?delete=<?php echo $student['student_id']; ?>" 
       onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($student['name']); ?>?')" 
       class="w-12 h-12 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-red-700 text-white rounded-2xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center delete-btn"
       title="Delete Student">
        <i class="fas fa-trash text-lg"></i>
    </a>
</div>

                    <!-- Student Photo -->
                    <div class="text-center mb-8">
                        <div class="relative">
                            <img src="../assets/images/students/<?php echo $student['profile_image']; ?>" 
                                 alt="<?php echo htmlspecialchars($student['name']); ?>" 
                                 class="w-36 h-36 rounded-3xl mx-auto shadow-2xl object-cover border-6 border-white group-hover:scale-105 transition-transform duration-300">
                            <?php if($student['profile_image'] == 'student_default.jpg'): ?>
                            <div class="absolute -bottom-3 -right-3 w-12 h-12 bg-gradient-to-r from-gray-400 to-gray-500 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-xl border-4 border-white">
                                📸
                            </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 mt-6 mb-2 line-clamp-1">
                            <?php echo htmlspecialchars($student['name']); ?>
                        </h3>
                    </div>

                    <!-- Student Info -->
                    <div class="space-y-3 mb-8">
                        <div class="flex items-center justify-between p-3 bg-indigo-50 rounded-2xl">
                            <span><i class="fas fa-id-badge text-indigo-500 mr-2"></i>ID</span>
                            <code class="font-mono font-bold text-indigo-800"><?php echo $student['student_id']; ?></code>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-2xl">
                            <span><i class="fas fa-phone text-green-500 mr-2"></i>Parent</span>
                            <strong><?php echo $student['parent_phone']; ?></strong>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-purple-50 rounded-2xl">
                            <span><i class="fas fa-school text-purple-500 mr-2"></i>Grade</span>
                            <span class="font-bold text-purple-800 px-3 py-2 bg-purple-100 rounded-xl"><?php echo $student['grade_level']; ?></span>
                        </div>
                        <?php if($student['status'] == 'Inactive'): ?>
                        <div class="flex items-center justify-center p-3 bg-red-50 rounded-2xl">
                            <span class="px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-bold">INACTIVE</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<!-- Add Student Modal -->
<div id="addModal" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden flex items-center justify-center p-8 z-50" onclick="this.classList.add('hidden')">
    <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-12 max-w-4xl w-full mx-8 max-h-[95vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-10">
            <h2 class="text-4xl font-black text-gray-900">👨‍🎓 Add New Student</h2>
        </div>
        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left side -->
            <div class="space-y-6">
                <!-- Student ID -->
                <div>
                    <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-id-card mr-3 text-indigo-500"></i>Student ID
                    </label>
                    <input type="text" name="student_id" placeholder="e.g. STU001" required maxlength="10" class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl font-semibold shadow-inner">
                </div>
                <!-- Name -->
                <div>
                    <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user mr-3 text-green-500"></i>Full Name
                    </label>
                    <input type="text" name="name" placeholder="e.g. Juan Dela Cruz" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-green-500 focus:ring-4 focus:ring-green-100 text-xl shadow-inner">
                </div>
                <!-- Grade Level -->
                <div>
                    <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-school mr-3 text-purple-500"></i>Grade Level
                    </label>
                    <select name="grade_level" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-purple-500 focus:ring-4 focus:ring-purple-100 text-xl">
                        <option value="">Select Grade</option>
                        <option value="Nursery">Nursery</option>
                        <option value="Pre-Kinder">Pre-Kinder</option>
                        <option value="Kinder">Kinder</option>
                    </select>
                </div>
                <!-- Parent Phone -->
                <div>
                    <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-phone mr-3 text-blue-500"></i>Parent Phone
                    </label>
                    <input type="tel" name="parent_phone" placeholder="09xxxxxxxxx" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 text-xl shadow-inner">
                </div>
                <!-- Profile Image -->
                <div class="col-span-2">
                    <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-image mr-3 text-orange-500"></i>Profile Photo (Optional)
                    </label>
                    <div class="p-8 border-4 border-dashed border-gray-300 rounded-3xl text-center hover:border-indigo-400 transition-all hover:bg-indigo-50">
                        <input type="file" name="profile_image" accept="image/jpeg,image/png" class="hidden" id="studentPhoto">
                        <label for="studentPhoto" class="cursor-pointer block p-8">
                            <i class="fas fa-cloud-upload-alt text-6xl text-gray-400 mb-4 block"></i>
                            <p class="text-xl font-semibold text-gray-700 mb-2">Click to upload photo</p>
                            <p class="text-gray-500">JPG, PNG • Max 2MB • <strong>Optional</strong></p>
                        </label>
                        <div class="mt-4 p-4 bg-indigo-50 rounded-2xl text-sm">
                            <strong>📸 Admin Tip:</strong> Upload clear face photo for best results
                        </div>
                    </div>
                </div>
            </div>
            <!-- Right side -->
            <div class="lg:pt-20 space-y-6">
                <!-- Admin Controls -->
                <div class="p-8 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-3xl border-4 border-dashed border-indigo-200">
                    <h3 class="text-2xl font-bold text-indigo-800 mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-3"></i>Admin Controls
                    </h3>
                    <div class="space-y-3 text-lg">
                        <label class="flex items-center">
                            <input type="radio" name="status" value="Active" checked class="w-6 h-6 mr-4 text-indigo-600">
                            <span class="font-semibold text-green-800">✅ Active Student</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="status" value="Inactive" class="w-6 h-6 mr-4 text-red-600">
                            <span class="font-semibold text-red-800">❌ Inactive Student</span>
                        </label>
                    </div>
                </div>
                
                <!-- New fields for Admin -->
                <!-- Date of Birth -->
                <div>
                    <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-birthday-cake mr-3"></i>Date of Birth
                    </label>
                    <input type="date" name="dob" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl shadow-inner" />
                </div>
                <!-- Admission Date -->
                <div>
                    <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-calendar-plus mr-3"></i>Admission Date
                    </label>
                    <input type="date" name="admission_date" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-green-500 focus:ring-4 focus:ring-green-100 text-xl shadow-inner" />
                </div>
                <!-- Gender -->
                <div>
                    <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-venus-mars mr-3"></i>Gender
                    </label>
                    <select name="gender" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-purple-500 focus:ring-4 focus:ring-purple-100 text-xl">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <!-- Address -->
                <div>
                    <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-map-marker-alt mr-3"></i>Address
                    </label>
                    <textarea name="address" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl shadow-inner" rows="3"></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex gap-4 pt-8 border-t border-indigo-200 justify-center">
                    <button type="submit" name="add_student" class="flex-1 bg-gradient-to-r from-emerald-500 to-teal-600 text-white py-6 px-8 rounded-3xl font-black text-2xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-300">
                        <i class="fas fa-user-plus mr-3"></i>✅ Add Student
                    </button>
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-6 px-8 rounded-3xl font-bold text-2xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
                        <i class="fas fa-times mr-3"></i>Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<?php if ($edit_student): ?>
<div class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center p-8 z-50" onclick="if(event.target === this) this.remove()">
  <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-12 max-w-4xl w-full mx-8 max-h-[95vh] overflow-y-auto" onclick="event.stopPropagation()">
    <h2 class="text-4xl font-black text-gray-900 mb-10 flex items-center justify-center">
      <i class="fas fa-user-edit mr-4"></i> Edit Student
    </h2>
    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Left side -->
      <div class="space-y-6">
        <!-- Name -->
        <div>
          <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-user mr-3 text-green-500"></i>Full Name
          </label>
          <input type="text" name="name" value="<?php echo htmlspecialchars($edit_student['name']); ?>" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-green-500 focus:ring-4 focus:ring-green-100 text-xl shadow-inner">
        </div>
        <!-- Grade Level -->
        <div>
          <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-school mr-3 text-purple-500"></i>Grade Level
          </label>
          <select name="grade_level" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-purple-500 focus:ring-4 focus:ring-purple-100 text-xl">
            <option value="">Select Grade</option>
            <option value="Nursery" <?php if($edit_student['grade_level']=='Nursery') echo 'selected'; ?>>Nursery</option>
            <option value="Pre-Kinder" <?php if($edit_student['grade_level']=='Pre-Kinder') echo 'selected'; ?>>Pre-Kinder</option>
            <option value="Kinder" <?php if($edit_student['grade_level']=='Kinder') echo 'selected'; ?>>Kinder</option>
          </select>
        </div>
        <!-- Parent Phone -->
        <div>
          <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-phone mr-3 text-blue-500"></i>Parent Phone
          </label>
          <input type="tel" name="parent_phone" value="<?php echo htmlspecialchars($edit_student['parent_phone']); ?>" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-blue-500 focus:ring-4 focus:ring-blue-100 text-xl shadow-inner">
        </div>
        <!-- Hidden input for student_id -->
        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($edit_student['student_id']); ?>">
      </div>
      <!-- Right side -->
      <!-- Date of Birth -->
      <div>
        <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
          <i class="fas fa-birthday-cake mr-3"></i>Date of Birth
        </label>
        <input type="date" name="dob" value="<?php echo htmlspecialchars($edit_student['dob']); ?>" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl shadow-inner" />
      </div>
      <!-- Admission Date -->
      <div>
        <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
          <i class="fas fa-calendar-plus mr-3"></i>Admission Date
        </label>
        <input type="date" name="admission_date" value="<?php echo htmlspecialchars($edit_student['admission_date']); ?>" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-green-500 focus:ring-4 focus:ring-green-100 text-xl shadow-inner" />
      </div>
      <!-- Gender -->
      <div>
        <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
          <i class="fas fa-venus-mars mr-3"></i>Gender
        </label>
        <select name="gender" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-purple-500 focus:ring-4 focus:ring-purple-100 text-xl">
          <option value="">Select Gender</option>
          <option value="Male" <?php if($edit_student['gender']=='Male') echo 'selected'; ?>>Male</option>
          <option value="Female" <?php if($edit_student['gender']=='Female') echo 'selected'; ?>>Female</option>
        </select>
      </div>
      <!-- Address -->
      <div>
        <label class="block text-xl font-bold text-gray-800 mb-4 flex items-center">
          <i class="fas fa-map-marker-alt mr-3"></i>Address
        </label>
        <textarea name="address" required class="w-full p-6 border-2 border-gray-200 rounded-3xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 text-xl shadow-inner" rows="3"><?php echo htmlspecialchars($edit_student['address']); ?></textarea>
      </div>
      <!-- Admin Controls -->
      <div class="lg:pt-20 space-y-6 flex flex-col justify-center">
        <div class="p-8 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-3xl border-4 border-dashed border-indigo-200">
          <h3 class="text-2xl font-bold text-indigo-800 mb-4 flex items-center">
            <i class="fas fa-info-circle mr-3"></i>Admin Controls
          </h3>
          <div class="space-y-3 text-lg">
            <label class="flex items-center">
              <input type="radio" name="status" value="Active" <?php if($edit_student['status']=='Active') echo 'checked'; ?> class="w-6 h-6 mr-4 text-indigo-600">
              <span class="font-semibold">✅ Active Student</span>
            </label>
            <label class="flex items-center">
              <input type="radio" name="status" value="Inactive" <?php if($edit_student['status']=='Inactive') echo 'checked'; ?> class="w-6 h-6 mr-4 text-red-600">
              <span class="font-semibold">❌ Inactive Student</span>
            </label>
          </div>
        </div>
        <!-- Buttons -->
        <div class="flex gap-4 pt-8 border-t border-indigo-200 justify-center">
          <button type="submit" name="save_edit" class="flex-1 bg-gradient-to-r from-emerald-500 to-teal-600 text-white py-6 px-8 rounded-3xl font-black text-2xl shadow-2xl hover:shadow-3xl hover:-translate-y-2 transition-all duration-300">
            <i class="fas fa-user-check mr-3"></i>Save Changes
          </button>
          <button type="button" onclick="this.closest('.fixed').remove()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-6 px-8 rounded-3xl font-bold text-2xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all">
            <i class="fas fa-times mr-3"></i>Cancel
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
// Close modal on outside click
document.getElementById('addModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}
</script>
<?php include '../includes/footer.php'; ?>