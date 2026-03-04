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

$dependency_stmt = $pdo->prepare(
    "SELECT
        (SELECT COUNT(*) FROM contributions WHERE member_id = ?) AS contributions_count,
        (SELECT COUNT(*) FROM loans WHERE member_id = ?) AS loans_count"
);
$dependency_stmt->execute([$member_id, $member_id]);
$dependency_counts = $dependency_stmt->fetch() ?: ['contributions_count' => 0, 'loans_count' => 0];
$has_associated_records = ((int)$dependency_counts['contributions_count'] > 0 || (int)$dependency_counts['loans_count'] > 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($has_associated_records) {
            $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'member'");
            $stmt->execute([$member_id]);
            header("Location: list_members.php?msg=" . urlencode("Member has associated records and was marked inactive.") . "&type=success");
            exit();
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'member'");
        $stmt->execute([$member_id]);
        $success = "Member deleted successfully!";
        header("Location: list_members.php?msg=" . urlencode("Member deleted successfully.") . "&type=success");
        exit();
    } catch (PDOException $e) {
        if ((string)$e->getCode() === '23000') {
            try {
                $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'member'");
                $stmt->execute([$member_id]);
                header("Location: list_members.php?msg=" . urlencode("Member could not be deleted due to linked records and was marked inactive.") . "&type=success");
                exit();
            } catch (PDOException $inner_exception) {
                $error = "Unable to delete member. Please try again.";
            }
        } else {
            $error = "Unable to delete member. Please try again.";
        }
    }
}
?>

<h2>Delete Member</h2>

<p>Are you sure you want to delete <strong><?php echo htmlspecialchars($member['full_name']); ?></strong> (Employee ID: <?php echo htmlspecialchars($member['employee_id']); ?>)?</p>
<?php if ($has_associated_records): ?>
    <p style="color: #856404;">This member has associated records (contributions and/or loans). They will be marked inactive instead of being permanently deleted.</p>
<?php else: ?>
    <p>This action cannot be undone.</p>
<?php endif; ?>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST">
    <button type="submit" style="background: #e74c3c; color: white;">Yes, Delete</button>
    <a href="list_members.php" style="margin-left: 10px;">Cancel</a>
</form>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>