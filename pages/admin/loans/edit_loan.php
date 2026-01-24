<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/functions.php';

$loan_id = $_GET['id'] ?? '';
if (!$loan_id || !is_numeric($loan_id)) {
    header("Location: list_loans.php");
    exit();
}

$stmt = $pdo->prepare("SELECT l.*, u.full_name FROM loans l JOIN users u ON l.member_id = u.id WHERE l.id = ?");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    echo "<p>Loan not found.</p>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php';
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $status = $_POST['status'] ?? '';
    $interest_rate = floatval($_POST['interest_rate'] ?? 0);

    if ($amount <= 0) {
        $error = "Please enter a valid amount.";
    } else {
        $old_values = json_encode($loan);
        try {
            $stmt = $pdo->prepare("UPDATE loans SET amount = ?, status = ?, interest_rate = ? WHERE id = ?");
            $stmt->execute([$amount, $status, $interest_rate, $loan_id]);
            $new_values = json_encode(['amount' => $amount, 'status' => $status, 'interest_rate' => $interest_rate]);
            log_audit($pdo, 'Edited loan', 'loans', $loan_id, $old_values, $new_values);
            $success = "Loan updated successfully!";
            // Refresh data
            $stmt = $pdo->prepare("SELECT l.*, u.full_name FROM loans l JOIN users u ON l.member_id = u.id WHERE l.id = ?");
            $stmt->execute([$loan_id]);
            $loan = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Error updating loan: " . $e->getMessage();
        }
    }
}
?>

<h2>Edit Loan</h2>

<?php if ($success): ?>
    <p style="color: green;"><?php echo $success; ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label>Member</label>
        <input type="text" value="<?php echo htmlspecialchars($loan['full_name']); ?>" disabled>
    </div>

    <div class="form-group">
        <label for="amount">Loan Amount (KES)</label>
        <input type="number" name="amount" id="amount" step="0.01" min="100" value="<?php echo $loan['amount']; ?>" required>
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select name="status" id="status">
            <option value="pending" <?php echo $loan['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="approved" <?php echo $loan['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
            <option value="rejected" <?php echo $loan['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            <option value="paid" <?php echo $loan['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
        </select>
    </div>

    <div class="form-group">
        <label for="interest_rate">Interest Rate (%)</label>
        <input type="number" name="interest_rate" id="interest_rate" step="0.01" value="<?php echo $loan['interest_rate']; ?>">
    </div>

    <button type="submit">Update Loan</button>
</form>

<p><a href="list_loans.php">← Back to loans list</a></p>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>