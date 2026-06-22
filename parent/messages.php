<?php
session_start();

$page_title = "Chat";
include '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$parent_id = $_SESSION['user_id'];

// Get unread count
$unreadStmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE to_user_id = ? AND is_read = 0 AND sender = 'teacher'");
$unreadStmt->execute([$parent_id]);
$unread_count = $unreadStmt->fetchColumn();

// Get all teachers this parent can message
$teachersStmt = $db->prepare("
    SELECT DISTINCT u.id, u.name, u.profile_image,
           COUNT(m.id) as unread_count,
           s.name as student_name
    FROM users u
    JOIN students s ON u.student_id = s.student_id
    INNER JOIN student_parent sp ON s.student_id = sp.student_id
    LEFT JOIN messages m ON m.from_user_id = u.id AND m.to_user_id = ? AND m.is_read = 0
    WHERE sp.parent_id = ? AND u.role = 'teacher'
    GROUP BY u.id, u.name, u.profile_image, s.name
    ORDER BY MAX(m.created_at) DESC, u.name ASC
");
$teachersStmt->execute([$parent_id, $parent_id]);
$teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

$selected_teacher_id = $_GET['teacher'] ?? ($teachers[0]['id'] ?? null);
$selected_student_id = $_GET['student'] ?? null;

// Get messages for selected teacher
$messages = [];
if ($selected_teacher_id) {
    $messagesStmt = $db->prepare("
        SELECT m.*, 
               u.name as sender_name,
               CASE WHEN m.sender = 'teacher' THEN 'teacher' ELSE 'parent' END as sender_type
        FROM messages m
        LEFT JOIN users u ON (m.from_user_id = u.id OR m.to_user_id = u.id)
        WHERE (m.from_user_id = ? OR m.to_user_id = ?) 
        AND (m.to_user_id = ? OR m.from_user_id = ?)
        " . ($selected_student_id ? "AND m.student_id = ?" : "") . "
        ORDER BY m.created_at ASC
    ");
    $params = [$selected_teacher_id, $parent_id, $parent_id, $selected_teacher_id];
    if ($selected_student_id) $params[] = $selected_student_id;
    $messagesStmt->execute($params);
    $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark as read
    $readStmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE to_user_id = ? AND from_user_id = ?");
    $readStmt->execute([$parent_id, $selected_teacher_id]);
}

// Get selected teacher info
$selected_teacher = null;
if ($selected_teacher_id) {
    $teacherStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $teacherStmt->execute([$selected_teacher_id]);
    $selected_teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);
}

// ✅ FIXED: Handle new message with proper POST check
$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && !empty(trim($_POST['message'])) && $selected_teacher_id) {
    $message = trim($_POST['message']);
    $insertStmt = $db->prepare("
        INSERT INTO messages (parent_id, from_user_id, to_user_id, student_id, message, sender, is_read) 
        VALUES (?, ?, ?, ?, ?, 'parent', 0)
    ");
    $insertStmt->execute([
        $parent_id, $parent_id, $selected_teacher_id, $selected_student_id, $message
    ]);
    $message_sent = true;
    // Reload page to show new message
    header("Location: chat.php?teacher=" . $selected_teacher_id . ($selected_student_id ? "&student=" . urlencode($selected_student_id) : ""));
    exit;
}
?>

<div class="flex min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 flex overflow-hidden">
        <!-- Teachers Sidebar -->
        <div class="w-80 bg-white/90 backdrop-blur-xl border-r border-gray-200 shadow-2xl">
            <div class="p-8 border-b border-gray-200 sticky top-0 bg-white/95 backdrop-blur-sm z-10">
                <h2 class="text-3xl font-black text-gray-900 mb-4 flex items-center gap-3">
                    <i class="fas fa-comments text-emerald-600"></i>
                    Teachers
                </h2>
                <div class="text-sm text-gray-600 flex items-center gap-2">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    <?php echo $unread_count; ?> unread messages
                </div>
            </div>
            
            <div class="overflow-y-auto h-[calc(100vh-200px)]">
                <?php if (empty($teachers)): ?>
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-user-tie text-6xl mb-4 opacity-50"></i>
                    <p class="text-lg">No teachers found</p>
                </div>
                <?php else: ?>
                <?php foreach ($teachers as $teacher): ?>
                <a href="?teacher=<?php echo $teacher['id']; ?><?php echo $selected_student_id ? '&student=' . urlencode($selected_student_id) : ''; ?>" 
                   class="flex items-center gap-4 p-6 hover:bg-emerald-50 <?php 
                   $is_active = ($teacher['id'] == $selected_teacher_id);
                   echo $is_active ? 'bg-emerald-100 border-r-4 border-emerald-500' : 'hover:border-r-2 hover:border-gray-200'; 
                   ?> transition-all group">
                    <div class="relative">
                        <img src="../assets/images/users/<?php echo htmlspecialchars($teacher['profile_image'] ?? 'default.jpg'); ?>" 
                             class="w-14 h-14 rounded-2xl object-cover shadow-lg">
                        <?php if ($teacher['unread_count'] > 0): ?>
                        <div class="absolute -top-1 -right-1 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold flex items-center justify-center shadow-lg animate-bounce">
                            <?php echo $teacher['unread_count']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-lg text-gray-900 truncate"><?php echo htmlspecialchars($teacher['name']); ?></h4>
                        <?php if ($teacher['student_name']): ?>
                        <p class="text-sm text-gray-600 truncate">For: <?php echo htmlspecialchars($teacher['student_name']); ?></p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="flex-1 flex flex-col bg-white/80 backdrop-blur-xl">
            <?php if ($selected_teacher): ?>
            <!-- Chat Header -->
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-emerald-50 to-teal-50 sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <img src="../assets/images/users/<?php echo htmlspecialchars($selected_teacher['profile_image'] ?? 'default.jpg'); ?>" 
                         class="w-16 h-16 rounded-2xl shadow-lg">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900"><?php echo htmlspecialchars($selected_teacher['name']); ?></h3>
                        <p class="text-lg text-gray-600">Class Teacher • Online</p>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div id="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4" style="scroll-behavior: smooth;">
                <?php if (empty($messages)): ?>
                <div class="text-center py-24 text-gray-500">
                    <i class="fas fa-comments text-9xl mb-8 opacity-50"></i>
                    <p class="text-2xl">Start the conversation</p>
                </div>
                <?php else: ?>
                <?php foreach ($messages as $message): ?>
                <div class="flex <?php echo $message['sender_type'] == 'parent' ? 'justify-end' : 'justify-start'; ?>">
                    <div class="max-w-3xl <?php echo $message['sender_type'] == 'parent' ? 'order-2' : ''; ?>">
                        <div class="flex items-end gap-3">
                            <?php if ($message['sender_type'] == 'teacher'): ?>
                            <img src="../assets/images/users/<?php echo htmlspecialchars($selected_teacher['profile_image'] ?? 'default.jpg'); ?>" 
                                 class="w-10 h-10 rounded-2xl shadow-md flex-shrink-0">
                            <?php endif; ?>
                            
                            <div class="p-6 rounded-3xl shadow-lg <?php 
                                echo $message['sender_type'] == 'parent' 
                                    ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white' 
                                    : 'bg-white border border-gray-200'; 
                            ?>">
                                <p class="text-lg leading-relaxed"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                <p class="text-sm opacity-75 mt-2"><?php echo date('M j, g:i A', strtotime($message['created_at'])); ?></p>
                            </div>
                            
                            <?php if ($message['sender_type'] == 'parent'): ?>
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-md flex-shrink-0">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Message Input -->
            <form method="POST" class="p-6 border-t border-gray-200 bg-white/50">
                <div class="flex items-end gap-4">
                    <textarea name="message" 
                              required 
                              rows="1" 
                              class="flex-1 p-4 border-2 border-gray-200 rounded-3xl text-lg focus:ring-4 focus:ring-emerald-200 focus:border-emerald-500 resize-none" 
                              placeholder="Type your message..." 
                              <?php echo isset($_POST['message']) ? 'value="' . htmlspecialchars($_POST['message']) . '"' : ''; ?>></textarea>
                    <button type="submit" 
                            class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-3xl shadow-2xl hover:shadow-3xl hover:scale-105 transition-all flex items-center justify-center">
                        <i class="fas fa-paper-plane text-xl"></i>
                    </button>
                </div>
            </form>
            <?php else: ?>
            <!-- No teacher selected -->
            <div class="flex-1 flex items-center justify-center p-12 text-center text-gray-500">
                <div>
                    <i class="fas fa-comments text-9xl mb-8 opacity-50"></i>
                    <h3 class="text-4xl font-bold mb-4">Select a teacher</h3>
                    <p class="text-xl">Choose a teacher from the sidebar to start chatting</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// Auto-scroll to bottom
function scrollToBottom() {
    const chatArea = document.getElementById('messagesContainer');
    if (chatArea) {
        chatArea.scrollTop = chatArea.scrollHeight;
    }
}

window.onload = function() {
    scrollToBottom();
    
    // Auto-resize textarea
    const textarea = document.querySelector('textarea[name="message"]');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.form.submit();
            }
        });
    }
};
</script>

<?php include '../includes/footer.php'; ?>