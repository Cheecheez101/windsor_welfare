<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

$member_id = $_GET['id'] ?? '';
if (!$member_id || !is_numeric($member_id)) {
    header("Location: list_members.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'member'");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    echo "<p>Member not found.</p>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php';
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'member'");
        $stmt->execute([$member_id]);
        $success = "Member deleted successfully!";
        header("Location: list_members.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting member: " . $e->getMessage() . " (Member may have associated records.)";
    }
}
?>

<h2>Delete Member</h2>

<p>Are you sure you want to delete <strong><?php echo htmlspecialchars($member['full_name']); ?></strong> (Employee ID: <?php echo htmlspecialchars($member['employee_id']); ?>)?</p>
<p>This action cannot be undone.</p>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST">
    <button type="submit" style="background: #e74c3c; color: white;">Yes, Delete</button>
    <a href="list_members.php" style="margin-left: 10px;">Cancel</a>
</form>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>