<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Upload Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            margin-top: 0;
        }
        .info-item {
            padding: 8px;
            margin: 4px 0;
            background: #f8f9fa;
            border-left: 3px solid #5B7FFF;
        }
        .error {
            background: #fee;
            border-left-color: #f44;
        }
        .success {
            background: #efe;
            border-left-color: #4f4;
        }
        form {
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        input[type="file"] {
            margin: 10px 0;
        }
        button {
            background: #5B7FFF;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #4a6eee;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Debug Upload Configuration</h1>

    <div class="debug-box">
        <h2>PHP Upload Configuration</h2>
        <div class="info-item">
            <strong>upload_max_filesize:</strong> <?= ini_get('upload_max_filesize') ?>
        </div>
        <div class="info-item">
            <strong>post_max_size:</strong> <?= ini_get('post_max_size') ?>
        </div>
        <div class="info-item">
            <strong>max_file_uploads:</strong> <?= ini_get('max_file_uploads') ?>
        </div>
        <div class="info-item">
            <strong>file_uploads:</strong> <?= ini_get('file_uploads') ? 'Enabled ✓' : 'Disabled ✗' ?>
        </div>
    </div>

    <div class="debug-box">
        <h2>Directory Information</h2>
        <?php
        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'photos' . DIRECTORY_SEPARATOR;
        ?>
        <div class="info-item">
            <strong>Upload Directory:</strong><br>
            <code><?= $uploadDir ?></code>
        </div>
        <div class="info-item <?= is_dir($uploadDir) ? 'success' : 'error' ?>">
            <strong>Directory Exists:</strong> <?= is_dir($uploadDir) ? 'Yes ✓' : 'No ✗' ?>
        </div>
        <?php if (is_dir($uploadDir)): ?>
        <div class="info-item <?= is_writable($uploadDir) ? 'success' : 'error' ?>">
            <strong>Is Writable:</strong> <?= is_writable($uploadDir) ? 'Yes ✓' : 'No ✗' ?>
        </div>
        <div class="info-item">
            <strong>Permissions:</strong> <?= substr(sprintf('%o', fileperms($uploadDir)), -4) ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="debug-box">
        <h2>Test Upload</h2>
        <form method="POST" enctype="multipart/form-data">
            <label for="test_file">Select an image file:</label><br>
            <input type="file" name="test_file" id="test_file" accept="image/*" required>
            <br><br>
            <button type="submit" name="test_upload">Test Upload</button>
        </form>
    </div>

    <?php
    if (isset($_POST['test_upload']) && isset($_FILES['test_file'])) {
        echo '<div class="debug-box">';
        echo '<h2>Upload Test Results</h2>';
        
        echo '<h3>$_FILES Data:</h3>';
        echo '<pre>' . print_r($_FILES, true) . '</pre>';
        
        $file = $_FILES['test_file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            echo '<div class="info-item success">✓ File uploaded successfully to temp directory</div>';
            
            // Create directory if needed
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                chmod($uploadDir, 0777);
            }
            
            $filename = 'test_' . time() . '_' . basename($file['name']);
            $filepath = $uploadDir . $filename;
            
            echo '<div class="info-item">';
            echo '<strong>Target Path:</strong><br>';
            echo '<code>' . $filepath . '</code>';
            echo '</div>';
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                echo '<div class="info-item success">';
                echo '✓✓✓ <strong>File moved successfully!</strong><br>';
                echo 'File saved to: ' . $filepath . '<br>';
                echo 'File size: ' . filesize($filepath) . ' bytes<br>';
                echo 'File exists: ' . (file_exists($filepath) ? 'Yes' : 'No');
                echo '</div>';
                
                // Show the image
                $webPath = 'uploads/photos/' . $filename;
                echo '<div class="info-item">';
                echo '<strong>Preview:</strong><br>';
                echo '<img src="' . $webPath . '" style="max-width: 300px; margin-top: 10px;">';
                echo '</div>';
            } else {
                echo '<div class="info-item error">';
                echo '✗✗✗ <strong>Failed to move file!</strong><br>';
                $error = error_get_last();
                if ($error) {
                    echo 'Last PHP Error: ' . $error['message'];
                }
                echo '</div>';
            }
        } else {
            echo '<div class="info-item error">';
            echo '✗ Upload Error Code: ' . $file['error'] . '<br>';
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in form',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension'
            ];
            echo 'Error: ' . ($errorMessages[$file['error']] ?? 'Unknown error');
            echo '</div>';
        }
        
        echo '</div>';
    }
    ?>

    <div class="debug-box">
        <h2>Instructions</h2>
        <ol>
            <li>Check that all configuration values above are correct</li>
            <li>Verify the upload directory exists and is writable</li>
            <li>Try uploading a small test image using the form above</li>
            <li>Look at the detailed results to identify the issue</li>
        </ol>
    </div>
</body>
</html>