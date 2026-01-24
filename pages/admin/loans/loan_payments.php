<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

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

if (!$loan || $loan['status'] !== 'approved') {
    echo "<p>Invalid or non-approved loan.</p>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php';
    exit();
}

// Get total payments made so far
$total_paid_stmt = $pdo->prepare("SELECT SUM(amount) AS total FROM loan_payments WHERE loan_id = ?");
$total_paid_stmt->execute([$loan_id]);
$total_paid = $total_paid_stmt->fetch()['total'] ?? 0;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $date   = $_POST['date'] ?? date('Y-m-d');

    if ($amount <= 0) {
        $error = "Please enter a valid payment amount.";
    } else {
        try {
            $pdo->beginTransaction();

            // Record the payment
            $stmt = $pdo->prepare("
                INSERT INTO loan_payments (loan_id, amount, payment_date)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$loan_id, $amount, $date]);

            $new_total_paid = $total_paid + $amount;

            // If interest > 0, calculate simple interest on remaining balance
            if ($loan['interest_rate'] > 0) {
                $remaining = $loan['amount'] - $total_paid;
                if ($remaining > 0) {
                    // Monthly interest (simple, not compound)
                    $monthly_interest = $remaining * ($loan['interest_rate'] / 100);

                    // Update total interest accrued
                    $new_total_interest = $loan['total_interest'] + $monthly_interest;
                    $update_stmt = $pdo->prepare("
                        UPDATE loans
                        SET total_interest = ?
                        WHERE id = ?
                    ");
                    $update_stmt->execute([$new_total_interest, $loan_id]);
                }
            }

            // Check if loan is fully repaid (principal + interest if any)
            $total_due = $loan['amount'] + ($loan['total_interest'] ?? 0);
            if ($new_total_paid >= $total_due) {
                $pdo->prepare("UPDATE loans SET status = 'paid' WHERE id = ?")
                    ->execute([$loan_id]);
                $success = "Payment recorded. Loan fully repaid!";
            } else {
                $success = "Payment of KES " . number_format($amount, 2) . " recorded.";
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error recording payment: " . $e->getMessage();
        }
    }
}
?>

<h2>Record Loan Payment - <?php echo htmlspecialchars($loan['member_name']); ?></h2>

<p><strong>Loan Amount:</strong> KES <?php echo number_format($loan['amount'], 2); ?></p>
<?php if ($loan['interest_rate'] > 0): ?>
    <p><strong>Interest Rate:</strong> <?php echo $loan['interest_rate']; ?>% per month</p>
    <p><strong>Total Interest Accrued:</strong> KES <?php echo number_format($loan['total_interest'] ?? 0, 2); ?></p>
<?php else: ?>
    <p><strong>Interest:</strong> 0% (Interest-free loan)</p>
<?php endif; ?>
<p><strong>Paid so far:</strong> KES <?php echo number_format($total_paid, 2); ?></p>
<p><strong>Remaining:</strong> KES <?php echo number_format($loan['amount'] - $total_paid, 2); ?></p>

<?php if ($success): ?><p style="color: green;"><?php echo $success; ?></p><?php endif; ?>
<?php if ($error): ?><p style="color: red;"><?php echo $error; ?></p><?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label for="amount">Payment Amount (KES)</label>
        <input type="number" name="amount" id="amount" step="0.01" min="1" required>
    </div>

    <div class="form-group">
        <label for="date">Payment Date</label>
        <input type="date" name="date" id="date" value="<?php echo date('Y-m-d'); ?>">
    </div>

    <button type="submit">Record Payment</button>
</form>

<p><a href="list_loans.php">← Back to loans list</a></p>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>