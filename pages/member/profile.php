<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: ../../pages/admin/dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';

    // Handle image upload
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = uniqid() . '_' . basename($_FILES['profile_image']['name']);
        $target_file = $upload_dir . $file_name;

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                $profile_image = 'uploads/profiles/' . $file_name;
            } else {
                $error = "Error uploading image.";
            }
        } else {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare("UPDATE users SET phone = ?, email = ?" . ($profile_image ? ", profile_image = ?" : "") . " WHERE id = ?");
        $params = [$phone, $email];
        if ($profile_image) $params[] = $profile_image;
        $params[] = $user_id;
        $stmt->execute($params);
        $success = "Profile updated successfully!";
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php'; ?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.profile-header {
    text-align: center;
    margin-bottom: 40px;
}

.profile-image-container {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.profile-image {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid #007bff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.default-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: linear-gradient(135deg, #007bff, #0056b3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 4em;
    border: 5px solid #007bff;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.profile-form {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="file"] {
    width: 100%;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus {
    outline: none;
    border-color: #007bff;
}

.form-group input[disabled] {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.btn {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn:hover {
    background: linear-gradient(135deg, #20c997, #17a2b8);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: bold;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.image-upload-group {
    text-align: center;
    margin-bottom: 30px;
}

.image-upload-group input[type="file"] {
    margin-top: 10px;
}

@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    .profile-form {
        padding: 20px;
    }
}
</style>

<div class="container">
    <div class="profile-header">
        <h2>My Profile</h2>
    </div>

    <div class="profile-image-container">
        <?php if (isset($user['profile_image']) && $user['profile_image']): ?>
            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="profile-image">
        <?php else: ?>
            <div class="default-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="profile-form">
        <div class="image-upload-group">
            <label for="profile_image">Update Profile Picture</label>
            <input type="file" name="profile_image" id="profile_image" accept="image/*">
        </div>

        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled>
        </div>

        <div class="form-group">
            <label for="employee_id">Employee ID</label>
            <input type="text" id="employee_id" value="<?php echo htmlspecialchars($user['employee_id']); ?>" disabled>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
        </div>

        <button type="submit" class="btn">Update Profile</button>
    </form>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>

<p><a href="dashboard.php">← Back to dashboard</a></p>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>