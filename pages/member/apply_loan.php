<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: ../admin/dashboard.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

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
        return "You already have an active or pending loan.";
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
        return "You have contributions in only $months month(s). Minimum 3 months required.";
    }

    return true; // eligible
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_SESSION['user_id'];  // pre-fill from session
    $amount    = floatval($_POST['amount'] ?? 0);

    if ($amount <= 0) {
        $error = "Please enter a valid amount.";
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
                $success = "Loan request of KES " . number_format($amount, 2) . " submitted successfully!";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get current eligibility status for display
$user_id = $_SESSION['user_id'];
$eligibility_status = checkLoanEligibility($pdo, $user_id);
?>

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

h2 {
    color: #333;
    text-align: center;
    margin-bottom: 30px;
    font-size: 2.5em;
}

.loan-form {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.eligibility-info {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.eligibility-info h3 {
    margin-top: 0;
    color: #0056b3;
}

.eligibility-info ul {
    margin: 10px 0;
    padding-left: 20px;
}

.eligibility-info li {
    margin: 5px 0;
}

.eligibility-status {
    font-weight: bold;
    padding: 10px;
    border-radius: 6px;
    margin-top: 10px;
}

.status-eligible {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-not-eligible {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
}

.form-group input[type="number"] {
    width: 100%;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-group input[type="number"]:focus {
    outline: none;
    border-color: #007bff;
}

.btn {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, #007bff, #0056b3);
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
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
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

.back-link {
    text-align: center;
    margin-top: 30px;
}

@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    .loan-form {
        padding: 20px;
    }
}
</style>

<div class="container">
    <h2>Apply for Loan</h2>

    <div class="eligibility-info">
        <h3>Loan Eligibility Requirements</h3>
        <ul>
            <li>Minimum total savings: KES 10,000</li>
            <li>Contributions in at least 3 different months</li>
            <li>No existing active or pending loans</li>
        </ul>
        <div class="eligibility-status <?php echo $eligibility_status === true ? 'status-eligible' : 'status-not-eligible'; ?>">
            <?php echo $eligibility_status === true ? '✓ You are eligible to apply for a loan' : '✗ ' . $eligibility_status; ?>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="loan-form">
        <div class="form-group">
            <label for="amount">Loan Amount (KES)</label>
            <input type="number" name="amount" id="amount" step="0.01" min="100" max="50000" required
                   <?php echo $eligibility_status !== true ? 'disabled' : ''; ?>>
        </div>

        <button type="submit" class="btn" <?php echo $eligibility_status !== true ? 'disabled' : ''; ?>>
            Submit Loan Request
        </button>
    </form>

    <div class="back-link">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>