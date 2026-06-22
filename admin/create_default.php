<?php
$roles = ['admins', 'teachers', 'parents'];
$default_image = '../assets/images/default.jpg'; // Your global default

foreach ($roles as $role_folder) {
    $target_dir = "../assets/images/{$role_folder}/";
    
    // Create folder
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
        echo "✅ Created folder: {$target_dir}\n";
    }
    
    // Copy default image
    $target_file = $target_dir . 'default.jpg';
    if (copy($default_image, $target_file)) {
        echo "✅ Copied default.jpg to: {$target_file}\n";
    } else {
        echo "❌ Failed to copy to: {$target_file}\n";
    }
}

echo "\n🎉 ALL DEFAULT IMAGES CREATED!";
?>