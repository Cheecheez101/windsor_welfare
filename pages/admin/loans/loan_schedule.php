<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();

$loan_id = $_GET['id'] ?? 0;
if (!$loan_id) {
    die("Invalid loan ID.");
}

// Fetch loan details
$stmt = $pdo->prepare("
    SELECT l.*, u.full_name AS name, u.employee_id AS membership_number
    FROM loans l
    JOIN users u ON l.member_id = u.id
    WHERE l.id = ?
");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    die("Loan not found.");
}

// Fetch payment history
$stmt = $pdo->prepare("
    SELECT * FROM loan_payments
    WHERE loan_id = ?
    ORDER BY payment_date ASC
");
$stmt->execute([$loan_id]);
$payments = $stmt->fetchAll();

// Calculate repayment schedule
$principal = $loan['amount'];
$interest_rate = $loan['interest_rate'] / 100; // Convert to decimal
$term_months = 12; // Assume 12-month term
$monthly_payment = ($principal * $interest_rate * pow(1 + $interest_rate, $term_months)) / (pow(1 + $interest_rate, $term_months) - 1);

$schedule = [];
$remaining_balance = $principal;
$current_date = new DateTime($loan['approve_date'] ?: $loan['apply_date']);

for ($month = 1; $month <= $term_months; $month++) {
    $interest = $remaining_balance * $interest_rate;
    $principal_payment = $monthly_payment - $interest;
    $remaining_balance -= $principal_payment;

    $schedule[] = [
        'month' => $month,
        'due_date' => $current_date->format('Y-m-d'),
        'monthly_payment' => $monthly_payment,
        'principal' => $principal_payment,
        'interest' => $interest,
        'remaining_balance' => max(0, $remaining_balance)
    ];

    $current_date->modify('+1 month');
}

// Calculate total paid and remaining
$total_paid = array_sum(array_column($payments, 'amount'));
$total_due = $monthly_payment * $term_months;
$remaining_due = $total_due - $total_paid;

$page_title = "Loan Repayment Schedule";
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';
?>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card h3 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.card p {
    margin: 10px 0;
    line-height: 1.6;
}

.table-container {
    overflow-x: auto;
    margin-bottom: 20px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background: #f8f9fa;
    font-weight: bold;
    color: #333;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.btn:hover {
    background: #0056b3;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #6c757d;
    font-style: italic;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.summary-item {
    background: #e9ecef;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.summary-item h4 {
    margin: 0 0 10px 0;
    color: #495057;
}

.summary-item p {
    margin: 0;
    font-size: 18px;
    font-weight: bold;
    color: #007bff;
}
</style>

<div class="container">
    <h2>Loan Repayment Schedule</h2>
    <div class="card">
        <h3>Loan Details</h3>
        <p><strong>Member:</strong> <?php echo htmlspecialchars($loan['name']); ?> (<?php echo htmlspecialchars($loan['membership_number']); ?>)</p>
        <p><strong>Loan Amount:</strong> KES <?php echo number_format($loan['amount'], 2); ?></p>
        <p><strong>Interest Rate:</strong> <?php echo number_format($loan['interest_rate'], 2); ?>%</p>
        <p><strong>Status:</strong> <?php echo ucfirst($loan['status']); ?></p>
        <p><strong>Apply Date:</strong> <?php echo $loan['apply_date']; ?></p>
        <?php if ($loan['approve_date']): ?>
            <p><strong>Approve Date:</strong> <?php echo $loan['approve_date']; ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Payment Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <h4>Total Due</h4>
                <p>KES <?php echo number_format($total_due, 2); ?></p>
            </div>
            <div class="summary-item">
                <h4>Total Paid</h4>
                <p>KES <?php echo number_format($total_paid, 2); ?></p>
            </div>
            <div class="summary-item">
                <h4>Remaining Due</h4>
                <p>KES <?php echo number_format($remaining_due, 2); ?></p>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Repayment Schedule</h3>
        <div class="table-container">
            <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Due Date</th>
                    <th>Monthly Payment</th>
                    <th>Principal</th>
                    <th>Interest</th>
                    <th>Remaining Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedule as $row): ?>
                    <tr>
                        <td><?php echo $row['month']; ?></td>
                        <td><?php echo $row['due_date']; ?></td>
                        <td>KES <?php echo number_format($row['monthly_payment'], 2); ?></td>
                        <td>KES <?php echo number_format($row['principal'], 2); ?></td>
                        <td>KES <?php echo number_format($row['interest'], 2); ?></td>
                        <td>KES <?php echo number_format($row['remaining_balance'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="card">
        <h3>Payment History</h3>
        <?php if (empty($payments)): ?>
            <div class="no-data">
                <p>No payments recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Payment Date</th>
                            <th>Amount</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo $payment['payment_date']; ?></td>
                                <td>KES <?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($payment['notes'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <a href="list_loans.php" class="btn">Back to Loans List</a>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>