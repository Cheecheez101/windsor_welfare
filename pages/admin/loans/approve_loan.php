<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/functions.php';
if ($_SESSION['role'] !== 'admin') {
    echo "<p style='color:red;'>Only admins can approve/reject loans.</p>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php';
    exit();
}

$loan_id = $_GET['id'] ?? '';
if (!$loan_id || !is_numeric($loan_id)) {
    header("Location: list_loans.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT l.*, u.full_name AS member_name 
    FROM loans l 
    JOIN users u ON l.member_id = u.id 
    WHERE l.id = ?
");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    echo "<p>Loan not found.</p>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $approve_date = ($action === 'approve') ? date('Y-m-d') : null;
    $interest_rate = ($action === 'approve') ? floatval($_POST['interest_rate'] ?? 0) : 0;

    // Calculate total interest when approving
    $total_interest = ($action === 'approve') ? ($loan['amount'] * ($interest_rate / 100)) : 0;

    $old_values = json_encode($loan);
    $stmt = $pdo->prepare("
        UPDATE loans
        SET status = ?, approve_date = ?, interest_rate = ?, total_interest = ?
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $approve_date, $interest_rate, $total_interest, $loan_id]);
    $new_values = json_encode([
        'status' => $new_status,
        'approve_date' => $approve_date,
        'interest_rate' => $interest_rate,
        'total_interest' => $total_interest
    ]);
    log_audit($pdo, "Loan $action", 'loans', $loan_id, $old_values, $new_values);

    header("Location: list_loans.php");
    exit();
}
?>

<h2>Approve / Reject Loan Request</h2>

<p><strong>Member:</strong> <?php echo htmlspecialchars($loan['member_name']); ?></p>
<p><strong>Amount:</strong> KES <?php echo number_format($loan['amount'], 2); ?></p>
<p><strong>Requested on:</strong> <?php echo date('d M Y', strtotime($loan['apply_date'])); ?></p>
<p><strong>Current Status:</strong> <?php echo ucfirst($loan['status']); ?></p>

<form method="POST">
    <div style="margin-bottom: 15px;">
        <label for="interest_rate">Interest Rate (%):</label>
        <input type="number" step="0.01" name="interest_rate" id="interest_rate" value="12.00" required>
        <small>(e.g., 12.00 for 12%)</small>
    </div>
    <button type="submit" name="action" value="approve" style="background: #27ae60; color: white;">Approve Loan</button>
    <button type="submit" name="action" value="reject" style="background: #e74c3c; color: white;">Reject Loan</button>
    <br><br>
    <a href="list_loans.php">Cancel</a>
</form>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>