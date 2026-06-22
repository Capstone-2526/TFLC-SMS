<?php 
session_start();

$page_title = "Inventory Management";
include '../includes/header.php';
require_once '../config/database.php';

// FIXED Session validation
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$message = '';

// Handle Form Actions
if ($_POST) {

    // ADD ITEM
    if (isset($_POST['add_item'])) {
        $item_name = trim($_POST['item_name']);
        $category = trim($_POST['category']);
        $quantity = (int)$_POST['quantity'];
        $min_stock = (int)$_POST['min_stock'];
        $location = trim($_POST['location']);

        // This is a Prepared Statement, instead putting data directly into the query, we use a placeholder(like ;id)
        //this tells the database to treat the input as plain text only, making it impossible for the hackers to run 
        //malicious commands

        $stmt = $db->prepare("INSERT INTO inventory (item_name, category, quantity, min_stock, location) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$item_name, $category, $quantity, $min_stock, $location])) {
            $message = "Item added successfully!";
        } else {
            $message = "Error adding item!";
        }
    }

    // UPDATE ITEM
    if (isset($_POST['update_item'])) {
        $id = (int)$_POST['item_id'];
        $quantity = (int)$_POST['quantity'];
        $min_stock = (int)$_POST['min_stock'];

        $stmt = $db->prepare("UPDATE inventory SET quantity = ?, min_stock = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$quantity, $min_stock, $id]);

        $message = "Stock updated successfully!";
    }

    // DELETE ITEM
    if (isset($_POST['delete_item'])) {
        $id = (int)$_POST['item_id'];

        $stmt = $db->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->execute([$id]);

        $message = "Item deleted successfully!";
    }
}

// STATS
$total_items = $db->query("SELECT COUNT(*) FROM inventory")->fetchColumn();
$low_stock = $db->query("SELECT COUNT(*) FROM inventory WHERE quantity <= min_stock")->fetchColumn();
$critical_stock = $db->query("SELECT COUNT(*) FROM inventory WHERE quantity <= 5")->fetchColumn();
$total_value = $db->query("SELECT SUM(quantity) FROM inventory")->fetchColumn();

// FETCH ITEMS (FIXED ORDER)

$stmt = $db->query("SELECT * FROM inventory ORDER BY id DESC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex min-h-screen">
    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 p-10 bg-gray-50">

        <!-- Welcome Header -->
<div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl p-10 border border-white/50 mb-10">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-5xl font-black bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-3">
                Inventory Management
            </h1>
            <p class="text-2xl text-gray-600">Track & manage school supplies</p>
        </div>

        <div class="w-32 h-32 bg-gradient-to-br from-orange-500 to-red-600 rounded-3xl flex items-center justify-center shadow-2xl">
            <i class="fas fa-boxes text-5xl text-white drop-shadow-lg"></i>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="mt-6 bg-green-500 text-white p-4 rounded-xl shadow">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
</div>

        <!-- STATS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <div class="bg-white p-6 rounded-xl shadow">
                <h2 class="text-lg font-semibold">Total Items</h2>
                <p class="text-2xl"><?php echo $total_items; ?></p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow">
                <h2 class="text-lg font-semibold">Low Stock</h2>
                <p class="text-2xl"><?php echo $low_stock; ?></p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow">
                <h2 class="text-lg font-semibold">Critical Stock</h2>
                <p class="text-2xl"><?php echo $critical_stock; ?></p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow">
                <h2 class="text-lg font-semibold">Total Quantity</h2>
                <p class="text-2xl"><?php echo $total_value; ?></p>
            </div>
        </div>

        <!-- ITEMS DISPLAY -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <?php if (count($items) > 0): ?>

                <?php foreach ($items as $item): ?>
                    <div class="bg-white p-6 rounded-2xl shadow border">

                        <h2 class="text-xl font-bold mb-2">
                            <?php echo htmlspecialchars($item['item_name']); ?>
                        </h2>

                        <p><strong>Category:</strong> <?php echo $item['category']; ?></p>
                        <p><strong>Quantity:</strong> <?php echo $item['quantity']; ?></p>
                        <p><strong>Min Stock:</strong> <?php echo $item['min_stock']; ?></p>
                        <p><strong>Borrowed:</strong> <?php echo $item['borrowed']; ?></p>
                        <p><strong>Location:</strong> <?php echo $item['location'] ?: 'N/A'; ?></p>

                        <!-- ACTIONS -->
                        <form method="POST" class="mt-4 flex gap-2">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">

                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" class="border p-1 w-20">
                            <input type="number" name="min_stock" value="<?php echo $item['min_stock']; ?>" class="border p-1 w-20">

                            <button name="update_item" class="bg-blue-500 text-white px-3 py-1 rounded">Update</button>
                            <button name="delete_item" class="bg-red-500 text-white px-3 py-1 rounded" onclick="return confirm('Delete this item?')">Delete</button>
                        </form>

                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <p>No items found.</p>
            <?php endif; ?>

        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>