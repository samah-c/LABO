<?php
/**
 * TEMPORARY TEST FILE - Put this at: /test_upload_membre.php
 * Access it at: http://localhost/your_project/test_upload_membre.php
 */

// Start session
session_start();

// Simulate logged in member
$_SESSION['user_id'] = 2; // Use the user ID from your screenshot
$_SESSION['username'] = 'user';
$_SESSION['role'] = 'membre';

// Database connection (adjust these)
$host = '127.0.0.1';
$dbname = 'TDW';
$username = 'admin';
$password = 'admin';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Upload Membre</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: green; padding: 10px; background: #e7f5e7; border-radius: 4px; }
        .error { color: red; padding: 10px; background: #ffe7e7; border-radius: 4px; }
        .info { padding: 8px; background: #f0f0f0; margin: 5px 0; border-left: 3px solid #5B7FFF; }
        form { margin-top: 20px; }
        input, textarea, select { 
            width: 100%; 
            padding: 8px; 
            margin: 5px 0 15px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #5B7FFF;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #4a6eee; }
        img { max-width: 200px; margin: 10px 0; border: 2px solid #ddd; }
    </style>
</head>
<body>
    <h1>🧪 Test Upload Membre</h1>

    <?php
    // Get current member data
    $userId = $_SESSION['user_id'];
    $stmt = $db->prepare("
        SELECT m.*, u.email, u.username 
        FROM Membre m 
        INNER JOIN User u ON m.user_id = u.id 
        WHERE m.user_id = ?
    ");
    $stmt->execute([$userId]);
    $membre = $stmt->fetch(PDO::FETCH_ASSOC);
    $membreId = $membre['id'] ?? null;

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        echo '<div class="card">';
        echo '<h2>📊 Debug Information</h2>';
        
        echo '<h3>POST Data:</h3>';
        echo '<pre>' . print_r($_POST, true) . '</pre>';
        
        echo '<h3>FILES Data:</h3>';
        echo '<pre>' . print_r($_FILES, true) . '</pre>';
        
        $errors = [];
        $success = [];
        
        // Update basic info
        $data = [
            'nom' => trim($_POST['nom'] ?? ''),
            'prenom' => trim($_POST['prenom'] ?? ''),
            'grade' => trim($_POST['grade'] ?? ''),
            'specialite' => trim($_POST['specialite'] ?? ''),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'biographie' => trim($_POST['biographie'] ?? '')
        ];
        
        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            echo '<div class="info">📸 Photo file detected!</div>';
            
            $file = $_FILES['photo'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($extension, $allowed)) {
                $errors[] = 'Invalid file type: ' . $extension;
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'File too large: ' . $file['size'] . ' bytes';
            } else {
                // Upload directory
                $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR;
                
                echo '<div class="info">📁 Upload directory: ' . $uploadDir . '</div>';
                
                // Create directory if needed
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                    chmod($uploadDir, 0777);
                    echo '<div class="info">✓ Created directory</div>';
                }
                
                // Check writable
                if (!is_writable($uploadDir)) {
                    $errors[] = 'Directory not writable!';
                    echo '<div class="error">✗ Directory not writable! Permissions: ' . substr(sprintf('%o', fileperms($uploadDir)), -4) . '</div>';
                } else {
                    echo '<div class="info">✓ Directory is writable</div>';
                    
                    // Generate filename
                    $filename = 'membre_' . $membreId . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    echo '<div class="info">🎯 Target: ' . $filepath . '</div>';
                    echo '<div class="info">📄 Temp file: ' . $file['tmp_name'] . '</div>';
                    
                    // Try upload
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        echo '<div class="success">✓✓✓ File uploaded successfully!</div>';
                        
                        // Verify file exists
                        if (file_exists($filepath)) {
                            echo '<div class="success">✓ File verified at: ' . $filepath . '</div>';
                            echo '<div class="info">File size: ' . filesize($filepath) . ' bytes</div>';
                            
                            $data['photo'] = 'photos/' . $filename;
                            $success[] = 'Photo uploaded: ' . $filename;
                            
                            // Show preview
                            echo '<div class="info">';
                            echo '<strong>Preview:</strong><br>';
                            echo '<img src="uploads/photos/' . $filename . '" alt="Uploaded photo">';
                            echo '</div>';
                        } else {
                            $errors[] = 'File does not exist after upload!';
                        }
                    } else {
                        $errors[] = 'move_uploaded_file() failed!';
                        $lastError = error_get_last();
                        if ($lastError) {
                            $errors[] = 'PHP Error: ' . $lastError['message'];
                        }
                    }
                }
            }
        } else if (isset($_FILES['photo'])) {
            $errorCode = $_FILES['photo']['error'];
            if ($errorCode !== UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Upload error code: ' . $errorCode;
            }
        }
        
        // Update database
        if (empty($errors)) {
            try {
                $fields = [];
                $values = [];
                
                foreach ($data as $key => $value) {
                    $fields[] = "$key = ?";
                    $values[] = $value;
                }
                
                $values[] = $membreId;
                
                $sql = "UPDATE Membre SET " . implode(', ', $fields) . " WHERE id = ?";
                echo '<div class="info">📝 SQL: ' . $sql . '</div>';
                echo '<div class="info">📦 Values: ' . print_r($values, true) . '</div>';
                
                $stmt = $db->prepare($sql);
                if ($stmt->execute($values)) {
                    $success[] = 'Database updated successfully!';
                    echo '<div class="success">✓ Database updated!</div>';
                } else {
                    $errors[] = 'Database update failed!';
                    echo '<div class="error">✗ Database update failed!</div>';
                }
            } catch (Exception $e) {
                $errors[] = 'Exception: ' . $e->getMessage();
                echo '<div class="error">✗ Exception: ' . $e->getMessage() . '</div>';
            }
        }
        
        // Summary
        if (!empty($success)) {
            echo '<div class="success"><h3>✓ Success:</h3><ul>';
            foreach ($success as $msg) echo '<li>' . $msg . '</li>';
            echo '</ul></div>';
        }
        
        if (!empty($errors)) {
            echo '<div class="error"><h3>✗ Errors:</h3><ul>';
            foreach ($errors as $msg) echo '<li>' . $msg . '</li>';
            echo '</ul></div>';
        }
        
        echo '</div>';
        
        // Refresh member data
        $stmt->execute([$userId]);
        $membre = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    ?>

    <div class="card">
        <h2>Current Member Data</h2>
        <div class="info"><strong>ID:</strong> <?= $membre['id'] ?? 'N/A' ?></div>
        <div class="info"><strong>User ID:</strong> <?= $membre['user_id'] ?? 'N/A' ?></div>
        <div class="info"><strong>Name:</strong> <?= ($membre['prenom'] ?? '') . ' ' . ($membre['nom'] ?? '') ?></div>
        <div class="info"><strong>Email:</strong> <?= $membre['email'] ?? 'N/A' ?></div>
        <div class="info"><strong>Current Photo:</strong> <?= $membre['photo'] ?? 'None' ?></div>
        
        <?php if (!empty($membre['photo'])): ?>
            <img src="uploads/<?= $membre['photo'] ?>" alt="Current photo">
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Update Profile</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Nom:</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($membre['nom'] ?? '') ?>" required>
            
            <label>Prénom:</label>
            <input type="text" name="prenom" value="<?= htmlspecialchars($membre['prenom'] ?? '') ?>" required>
            
            <label>Grade:</label>
            <input type="text" name="grade" value="<?= htmlspecialchars($membre['grade'] ?? '') ?>">
            
            <label>Spécialité:</label>
            <input type="text" name="specialite" value="<?= htmlspecialchars($membre['specialite'] ?? '') ?>">
            
            <label>Téléphone:</label>
            <input type="text" name="telephone" value="<?= htmlspecialchars($membre['telephone'] ?? '') ?>">
            
            <label>Biographie:</label>
            <textarea name="biographie" rows="3"><?= htmlspecialchars($membre['biographie'] ?? '') ?></textarea>
            
            <label>Photo (JPG, PNG, GIF - Max 5MB):</label>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/jpg">
            
            <button type="submit" name="update_profile">💾 Update Profile</button>
        </form>
    </div>
</body>
</html>