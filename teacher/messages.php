<?php
session_start();

$page_title = "Messages";
include '../includes/header.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$teacher_id = $_SESSION['user_id'];

// Handle message sending
$message = '';
if ($_POST) {
    $to_user_id = $_POST['to_user_id'];
    $student_id = $_POST['student_id'] ?? '';
    $message_text = trim($_POST['message']);
    
    if (!empty($message_text)) {
        $insertStmt = $db->prepare("
            INSERT INTO messages (parent_id, from_user_id, to_user_id, student_id, message, sender, is_read) 
            VALUES (?, ?, ?, ?, ?, 'teacher', 0)
        ");
        $insertStmt->execute([$_POST['parent_id'], $teacher_id, $to_user_id, $student_id, $message_text]);
        $message = '<div class="bg-emerald-500 text-white p-6 rounded-3xl shadow-2xl text-center text-2xl font-bold mb-8">Message Sent Successfully! ✅</div>';
    }
}

// Fetch conversations (parents who messaged teacher or vice versa)
$conversationsStmt = $db->prepare("
    SELECT DISTINCT
        u.id as user_id,
        u.name as parent_name,
        u.phone as parent_phone,
        s.student_id,
        s.name as student_name,
        COUNT(CASE WHEN m.is_read = 0 AND m.to_user_id = ? THEN 1 END) as unread_count,
        MAX(m.created_at) as last_message_time
    FROM messages m
    JOIN users u ON (m.parent_id = u.id OR (m.from_user_id = u.id AND m.sender = 'parent'))
    LEFT JOIN students s ON m.student_id = s.student_id
    WHERE m.to_user_id = ? OR m.from_user_id = ?
    GROUP BY u.id, s.student_id
    ORDER BY last_message_time DESC
");
$conversationsStmt->execute([$teacher_id, $teacher_id, $teacher_id]);
$conversations = $conversationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch students for new message
$studentsStmt = $db->prepare("
    SELECT DISTINCT s.id, s.student_id, s.name, u.id as parent_id, u.name as parent_name
    FROM students s
    JOIN users u ON s.parent_id = u.id
    WHERE s.status = 'Active'
    ORDER BY s.name
");
$studentsStmt->execute();
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
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
                            Messages
                        </h1>
                        <p class="text-2xl text-gray-600">Communicate with parents</p>
                        <?php if (!empty($conversations)): ?>
                        <div class="flex gap-6 mt-4">
                            <span class="px-6 py-3 bg-emerald-100 text-emerald-800 rounded-2xl text-xl font-bold shadow-lg">
                                <?php 
                                $totalUnread = array_sum(array_column($conversations, 'unread_count'));
                                echo $totalUnread; ?> Unread
                            </span>
                            <span class="px-6 py-3 bg-blue-100 text-blue-800 rounded-2xl text-xl font-bold shadow-lg">
                                <?php echo count($conversations); ?> Conversations
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php echo $message ?? ''; ?>
                    </div>
                    <div class="w-32 h-32 bg-gradient-to-br from-purple-500 to-pink-600 rounded-3xl flex items-center justify-center shadow-2xl">
                        <i class="fas fa-comments text-5xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Conversations List -->
                <div class="lg:col-span-2 space-y-6">
                    <?php if (empty($conversations)): ?>
                    <!-- No Conversations -->
                    <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-20 border border-white/50 text-center">
                        <i class="fas fa-comments text-9xl text-gray-300 mb-12"></i>
                        <h2 class="text-4xl font-black text-gray-500 mb-6">No Messages Yet</h2>
                        <p class="text-xl text-gray-600 mb-12">Start the conversation with a parent</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                    <a href="?conversation=<?php echo $conv['user_id']; ?>&student=<?php echo $conv['student_id'] ?? ''; ?>" 
                       class="group flex gap-6 p-8 bg-white/70 backdrop-blur-sm hover:bg-white/90 rounded-3xl shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 border border-white/50 hover:border-purple-200">
                        
                        <!-- Avatar & Unread Badge -->
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-600 rounded-3xl flex items-center justify-center text-white text-2xl font-bold shadow-2xl">
                                <?php echo strtoupper(substr($conv['parent_name'], 0, 2)); ?>
                            </div>
                            <?php if ($conv['unread_count'] > 0): ?>
                            <div class="absolute -top-2 -right-2 bg-red-500 text-white w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shadow-lg animate-bounce">
                                <?php echo $conv['unread_count']; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Conversation Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-4 mb-2">
                                <h4 class="text-2xl font-black text-gray-900 group-hover:text-purple-700 transition-colors flex-1 truncate">
                                    <?php echo htmlspecialchars($conv['parent_name']); ?>
                                </h4>
                                <span class="text-xs text-gray-500">
                                    <?php echo date('M j, g:i A', strtotime($conv['last_message_time'])); ?>
                                </span>
                            </div>
                            <?php if ($conv['student_name']): ?>
                            <div class="text-lg text-gray-600 mb-2 flex items-center gap-2">
                                <i class="fas fa-user-graduate text-indigo-500"></i>
                                <span><?php echo htmlspecialchars($conv['student_name']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($conv['parent_phone']): ?>
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <i class="fas fa-phone"></i>
                                <span class="truncate"><?php echo htmlspecialchars($conv['parent_phone']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Latest Message Preview -->
                        <?php 
                        $latestMsgStmt = $db->prepare("SELECT message FROM messages WHERE (from_user_id = ? OR to_user_id = ?) AND parent_id = ? ORDER BY created_at DESC LIMIT 1");
                        $latestMsgStmt->execute([$teacher_id, $teacher_id, $conv['user_id']]);
                        $latestMsg = $latestMsgStmt->fetchColumn();
                        ?>
                        <div class="text-right ml-auto">
                            <?php if ($latestMsg): ?>
                            <div class="text-sm text-gray-500 italic max-w-48 truncate bg-gray-100 px-3 py-2 rounded-2xl">
                                <?php echo htmlspecialchars(substr($latestMsg, 0, 50)); ?>...
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- New Message / Quick Send -->
                <div class="space-y-8">
                    <!-- Send New Message -->
                    <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-10 border border-white/50 sticky top-12">
                        <h3 class="text-3xl font-bold mb-8 text-gray-900 flex items-center gap-4">
                            <i class="fas fa-paper-plane text-purple-600 text-3xl"></i>
                            New Message
                        </h3>
                        
                        <form method="POST" class="space-y-6">
                            <!-- Student Selection -->
                            <div>
                                <label class="block text-xl font-bold text-gray-700 mb-4">Select Student</label>
                                <select name="student_id" class="w-full p-5 rounded-3xl border-2 border-gray-200 focus:border-purple-400 focus:ring-4 focus:ring-purple-100 text-xl font-semibold shadow-lg transition-all duration-300" required>
                                    <option value="">Choose a student...</option>
                                    <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['student_id']; ?>" data-parent-id="<?php echo $student['parent_id']; ?>">
                                        <?php echo htmlspecialchars($student['name']); ?> (<?php echo $student['student_id']; ?>) 
                                        - <?php echo htmlspecialchars($student['parent_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Hidden fields for parent_id and to_user_id -->
                            <input type="hidden" name="parent_id" id="parent_id" value="">
                            <input type="hidden" name="to_user_id" id="to_user_id" value="">

                            <!-- Message -->
                            <div>
                                <label class="block text-xl font-bold text-gray-700 mb-4">Message</label>
                                <textarea name="message" rows="6" 
                                          class="w-full p-6 rounded-3xl border-2 border-gray-200 focus:border-purple-400 focus:ring-4 focus:ring-purple-100 text-xl resize-vertical shadow-lg transition-all duration-300 font-medium" 
                                          placeholder="Type your message here..." required></textarea>
                            </div>

                            <!-- Send Button -->
                            <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white py-6 px-8 rounded-3xl text-2xl font-black shadow-2xl hover:shadow-3xl hover:-translate-y-1 transition-all duration-500 flex items-center justify-center gap-4">
                                <i class="fas fa-paper-plane text-3xl"></i>
                                Send Message
                            </button>
                        </form>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-8 border border-white/50 text-center">
                        <h4 class="text-2xl font-bold text-gray-900 mb-6">Quick Stats</h4>
                        <div class="grid grid-cols-2 gap-6 text-center">
                            <div>
                                <div class="text-4xl font-black text-emerald-600 mb-2"><?php echo count($conversations); ?></div>
                                <div class="text-lg text-gray-600 font-semibold">Active Chats</div>
                            </div>
                            <div>
                                <div class="text-4xl font-black text-pink-600 mb-2"><?php echo $totalUnread ?? 0; ?></div>
                                <div class="text-lg text-gray-600 font-semibold">Unread Messages</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentSelect = document.querySelector('select[name="student_id"]');
    const parentIdInput = document.getElementById('parent_id');
    const toUserIdInput = document.getElementById('to_user_id');
    
    studentSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const parentId = selectedOption.dataset.parentId;
        parentIdInput.value = parentId;
        toUserIdInput.value = parentId;
        
        // Visual feedback
        if (parentId) {
            this.parentElement.style.borderColor = '#a855f7';
            this.parentElement.style.boxShadow = '0 0 0 4px rgba(168, 85, 247, 0.2)';
        }
    });

    // Auto-resize textarea
    const textarea = document.querySelector('textarea[name="message"]');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });

    // Smooth hover effects
    document.querySelectorAll('.group').forEach(group => {
        group.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        group.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>