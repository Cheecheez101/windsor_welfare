<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/functions.php';

$error = '';
$success = '';

// --- Eligibility checks (run when member is selected or on form submit) ---
function checkLoanEligibility($pdo, $member_id) {
    // 1. Check for existing active/pending loan
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM loans
        WHERE member_id = ? AND status IN ('pending', 'approved')
    ");
    $stmt->execute([$member_id]);
    if ($stmt->fetchColumn() > 0) {
        return "Member already has an active or pending loan.";
    }

    // 2. Check minimum savings (KES 10,000)
    $stmt = $pdo->prepare("SELECT SUM(amount) AS total FROM contributions WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $total_savings = $stmt->fetch()['total'] ?? 0;
    if ($total_savings < 10000) {
        return "Total savings (KES " . number_format($total_savings, 2) . ") below minimum required (KES 10,000).";
    }

    // 3. Check minimum contribution months (at least 3 different months)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT DATE_FORMAT(contribution_date, '%Y-%m')) AS months
        FROM contributions WHERE member_id = ?
    ");
    $stmt->execute([$member_id]);
    $months = $stmt->fetch()['months'] ?? 0;
    if ($months < 3) {
        return "Member has contributions in only $months month(s). Minimum 3 months required.";
    }

    return true; // eligible
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'] ?? '';
    $amount    = floatval($_POST['amount'] ?? 0);

    if (empty($member_id) || $amount <= 0) {
        $error = "Please select a member and enter a valid amount.";
    } else {
        $eligibility = checkLoanEligibility($pdo, $member_id);
        if ($eligibility !== true) {
            $error = $eligibility;
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO loans (member_id, amount, apply_date, status, interest_rate)
                    VALUES (?, ?, CURDATE(), 'pending', 0.00)
                ");
                $stmt->execute([$member_id, $amount]);
                log_audit($pdo, 'Applied for loan', 'loans', $pdo->lastInsertId(), null, "Amount: $amount");
                $success = "Loan request of KES " . number_format($amount, 2) . " submitted successfully!";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get members for dropdown
$members = $pdo->query("SELECT id, full_name AS name FROM users WHERE role='member' ORDER BY full_name")->fetchAll();
?>

<h2>Apply for Loan (Admin)</h2>

<?php if ($success): ?>
    <p style="color: green;"><?php echo $success; ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label for="member_id">Member</label>
        <select name="member_id" id="member_id" required>
            <option value="">-- Select Member --</option>
            <?php foreach ($members as $m): ?>
                <option value="<?php echo $m['id']; ?>">
                    <?php echo htmlspecialchars($m['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="amount">Loan Amount (KES)</label>
        <input type="number" name="amount" id="amount" step="0.01" min="100" required>
    </div>

    <button type="submit">Submit Loan Request</button>
</form>

<p><a href="list_loans.php">← Back to loans list</a></p>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>