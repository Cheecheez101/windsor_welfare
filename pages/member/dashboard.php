<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: ../admin/dashboard.php");
    exit();
}

require_once '../../includes/header.php';
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

h2 {
    color: #333;
    text-align: center;
    margin-bottom: 30px;
    font-size: 2.5em;
}

.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #e9ecef;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
}

.card h3 {
    margin-top: 0;
    color: #495057;
    font-size: 1.4em;
    border-bottom: 3px solid #007bff;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.card p {
    margin: 10px 0;
    font-size: 1.1em;
    color: #6c757d;
}

.card p:first-of-type {
    font-weight: bold;
    color: #007bff;
    font-size: 1.3em;
}

.actions {
    text-align: center;
    margin-top: 40px;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    margin: 10px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    transition: background 0.3s ease, transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.welcome-message {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.welcome-message h3 {
    margin: 0;
    font-size: 1.8em;
}

.welcome-message p {
    margin: 10px 0 0 0;
    opacity: 0.9;
}

@media (max-width: 768px) {
    .dashboard-cards {
        grid-template-columns: 1fr;
    }
    
    .btn {
        display: block;
        margin: 10px auto;
        width: 200px;
    }
}
</style>

<div class="container">
    <?php
    $user_id = $_SESSION['user_id'];

    // Get user full name
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $user_name = $user['full_name'] ?? 'Member';
    ?>

    <div class="welcome-message">
        <h3>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h3>
        <p>Here's an overview of your welfare account</p>
    </div>

    <h2>My Dashboard</h2>

    <?php
    $user_id = $_SESSION['user_id'];

    // Get total savings
    $stmt = $pdo->prepare("SELECT SUM(amount) AS total FROM contributions WHERE member_id = ?");
    $stmt->execute([$user_id]);
    $total_savings = $stmt->fetch()['total'] ?? 0;

    // Get active loan
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE member_id = ? AND status IN ('approved', 'pending') ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $active_loan = $stmt->fetch();

    // Get next contribution due (assuming monthly, last day of month or something, but simplify)
    $current_month = date('Y-m');
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM contributions WHERE member_id = ? AND DATE_FORMAT(contribution_date, '%Y-%m') = ?");
    $stmt->execute([$user_id, $current_month]);
    $total_contributed_this_month = $stmt->fetchColumn() ?: 0;
    $contributed_this_month = 'KES ' . number_format($total_contributed_this_month, 2);
// Get total loan amount (if any active loan)
    $total_loan_amount = $active_loan ? $active_loan['amount'] : 0;

    // Get total payments made towards loans
    $stmt = $pdo->prepare("SELECT SUM(lp.amount) AS total_paid FROM loan_payments lp JOIN loans l ON lp.loan_id = l.id WHERE l.member_id = ?");
    $stmt->execute([$user_id]);
    $total_loan_payments = $stmt->fetch()['total_paid'] ?? 0;

    // Calculate outstanding loan balance
    $outstanding_balance = $total_loan_amount - $total_loan_payments;
    ?>

    <div class="dashboard-cards">
        <div class="card">
            <h3>My Savings</h3>
            <p>Total: KES <?php echo number_format($total_savings, 2); ?></p>
        </div>

        <div class="card">
            <h3>My Loan Status</h3>
            <?php if ($active_loan): ?>
                <p>Status: <?php echo ucfirst($active_loan['status']); ?></p>
                <p>Amount: KES <?php echo number_format($active_loan['amount'], 2); ?></p>
                <p>Outstanding: KES <?php echo number_format($outstanding_balance, 2); ?></p>
        <?php else: ?>
            <p>No active loan</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>This Month's Contribution</h3>
        <p><?php echo $contributed_this_month; ?></p>
    </div>
</div>

<div class="actions">
    <a href="add_contribution.php" class="btn">Add Contribution</a>
    <a href="apply_loan.php" class="btn">Apply for Loan</a>
    <a href="profile.php" class="btn">Edit Profile</a>
    <a href="my_contributions.php" class="btn">View Contributions</a>
    <a href="my_loans.php" class="btn">View Loans</a>
</div>
</div>

<?php require_once '../../includes/footer.php'; ?>