<?php
session_start();
require_once 'config/database.php';

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

// this statement check's the user's input if the username/password matches the database,
//  log them in "else" show an invalid credential
$error = '';
if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['profile_image'] = $user['profile_image'];
        header('Location: index.php');
        exit();
    } else {
        $error = 'Invalid credentials!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TFLC SMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 min-h-screen flex items-center justify-center p-8">
    <div class="max-w-md w-full bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl p-12 border border-white/50">
        <div class="text-center mb-12">
            <div class="w-28 h-28 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-3xl mx-auto flex items-center justify-center shadow-2xl mb-6">
                <i class="fas fa-graduation-cap text-5xl text-white"></i>
            </div>
            <h1 class="text-4xl font-black bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-2">
                TFLC SMS
            </h1>
            <p class="text-gray-600 text-xl">School Management System</p>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl mb-8 animate-pulse">
            <i class="fas fa-exclamation-triangle mr-3"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-700 font-bold mb-4 text-lg">Username</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-4 top-5 text-gray-400"></i>
                    <input type="text" name="username" required 
                           class="w-full pl-12 pr-5 py-5 border-2 border-gray-200 rounded-2xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-300 text-lg shadow-inner"
                           placeholder="Enter username" autocomplete="username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>
            
            <div>
                <label class="block text-gray-700 font-bold mb-4 text-lg">Password</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-4 top-5 text-gray-400"></i>
                    <input type="password" name="password" required 
                           class="w-full pl-12 pr-5 py-5 border-2 border-gray-200 rounded-2xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all duration-300 text-lg shadow-inner"
                           placeholder="Enter password" autocomplete="current-password">
                </div>
            </div>
            
            <button type="submit" 
                    class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white py-6 px-8 rounded-3xl font-bold text-xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 transform">
                <i class="fas fa-sign-in-alt mr-3"></i>Login to Dashboard
            </button>
        </form>
    
        </div>
    </div>
</body>
</html>