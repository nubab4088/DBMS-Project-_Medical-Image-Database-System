<?php
require 'backend/db.php';
session_start();

// Get image info from DB using image_id
$image_id = isset($_GET['image_id']) ? $_GET['image_id'] : (isset($_POST['image_id']) ? $_POST['image_id'] : '');
$image = null;
if ($image_id) {
    $stmt = $conn->prepare("SELECT image_path, description FROM Images WHERE image_id = ?");
    $stmt->bind_param("s", $image_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $image = $result->fetch_assoc();
    $stmt->close();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $image) {
    $input_path = $image['image_path'];
    $operation = '';
    $args = [];
    // Only allow one operation per submission for simplicity
    if (isset($_POST['clahe'])) {
        $operation = 'clahe';
    } elseif (isset($_POST['bw'])) {
        $operation = 'bw';
    } elseif (isset($_POST['grayscale'])) {
        $operation = 'grayscale';
    } elseif (!empty($_POST['enhance_r'])) {
        $operation = 'enhance_r';
    } elseif (!empty($_POST['enhance_g'])) {
        $operation = 'enhance_g';
    } elseif (!empty($_POST['enhance_b'])) {
        $operation = 'enhance_b';
    } elseif (!empty($_POST['resize_w']) && !empty($_POST['resize_h'])) {
        $operation = 'resize';
        $args['width'] = (int)$_POST['resize_w'];
        $args['height'] = (int)$_POST['resize_h'];
    } elseif (!empty($_POST['crop_x']) && !empty($_POST['crop_y']) && !empty($_POST['crop_w']) && !empty($_POST['crop_h'])) {
        $operation = 'crop';
        $args['x'] = (int)$_POST['crop_x'];
        $args['y'] = (int)$_POST['crop_y'];
        $args['w'] = (int)$_POST['crop_w'];
        $args['h'] = (int)$_POST['crop_h'];
    } elseif (!empty($_POST['zoom'])) {
        $operation = 'zoom';
        $args['factor'] = (float)$_POST['zoom'];
    } elseif (!empty($_POST['rotate'])) {
        $operation = 'rotate';
        $args['angle'] = (float)$_POST['rotate'];
    }
    if ($operation) {
        $ext = pathinfo($input_path, PATHINFO_EXTENSION);
        $processed_name = 'proc_' . uniqid() . '.' . $ext;
        $output_path = 'images/processed/' . $processed_name;
        $cmd = escapeshellcmd("python3 process_image.py --input " . escapeshellarg($input_path) . " --output " . escapeshellarg($output_path) . " --operation $operation");
        // Add extra args
        foreach ($args as $k => $v) {
            $cmd .= " --$k " . escapeshellarg($v);
        }
        // For enhance_r/g/b, pass channel
        if (in_array($operation, ['enhance_r','enhance_g','enhance_b'])) {
            // No extra arg needed, operation name is enough
        }
        exec($cmd, $output, $ret);
        if ($ret === 0 && file_exists($output_path)) {
            // Insert into ProcessedImages
            $doctor_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
            $processing_type = $operation;
            $now = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO ProcessedImages (image_id, processing_type, processed_path, processed_by, processed_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $image_id, $processing_type, $output_path, $doctor_id, $now);
            if ($stmt->execute()) {
                $success = "Image processed and saved! <a href='images.html'>Back to Images</a>";
            } else {
                $error = "Failed to save processed image to database.";
            }
            $stmt->close();
        } else {
            $error = "Image processing failed.";
        }
    } else {
        $error = "Please select an operation and provide required parameters.";
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Process Image</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .process-form {
            max-width: 500px;
            margin: 30px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            padding: 30px;
        }
        .process-form h2 {
            margin-bottom: 20px;
        }
        .process-form label {
            font-weight: 600;
            display: block;
            margin-top: 15px;
        }
        .process-form input[type="number"],
        .process-form input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .process-form .form-row {
            display: flex;
            gap: 10px;
        }
        .process-form .form-row > div {
            flex: 1;
        }
        .process-form button {
            margin-top: 20px;
        }
        .image-preview {
            text-align: center;
            margin-bottom: 20px;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="process-form">
            <h2>Process Image</h2>
            <?php if ($success): ?>
                <div class="success"> <?php echo $success; ?> </div>
            <?php elseif ($error): ?>
                <div class="error"> <?php echo $error; ?> </div>
            <?php endif; ?>
            <?php if ($image): ?>
                <div class="image-preview">
                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="Image to process">
                    <p><?php echo htmlspecialchars($image['description']); ?></p>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="image_id" value="<?php echo htmlspecialchars($image_id); ?>">
                    <label><input type="checkbox" name="clahe"> CLAHE (Contrast Enhancement)</label>
                    <label><input type="checkbox" name="bw"> Black/White</label>
                    <label><input type="checkbox" name="grayscale"> Grayscale</label>
                    <label>Enhance R/G/B:</label>
                    <div class="form-row">
                        <div><input type="number" name="enhance_r" placeholder="Red %" min="0" max="200"></div>
                        <div><input type="number" name="enhance_g" placeholder="Green %" min="0" max="200"></div>
                        <div><input type="number" name="enhance_b" placeholder="Blue %" min="0" max="200"></div>
                    </div>
                    <label>Resize:</label>
                    <div class="form-row">
                        <div><input type="number" name="resize_w" placeholder="Width (px)"></div>
                        <div><input type="number" name="resize_h" placeholder="Height (px)"></div>
                    </div>
                    <label>Crop:</label>
                    <div class="form-row">
                        <div><input type="number" name="crop_x" placeholder="X"></div>
                        <div><input type="number" name="crop_y" placeholder="Y"></div>
                        <div><input type="number" name="crop_w" placeholder="Width"></div>
                        <div><input type="number" name="crop_h" placeholder="Height"></div>
                    </div>
                    <label>Zoom (scale): <input type="number" name="zoom" placeholder="e.g. 1.2" step="0.01" min="0.1" max="5"></label>
                    <label>Rotate (angle): <input type="number" name="rotate" placeholder="Degrees" min="-360" max="360"></label>
                    <button type="submit" class="process-btn">Process & Save</button>
                </form>
            <?php else: ?>
                <p>Image not found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 