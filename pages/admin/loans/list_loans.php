<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

// Get filter parameters
$status = $_GET['status'] ?? '';
$member_search = $_GET['member_search'] ?? '';
$amount_from = $_GET['amount_from'] ?? '';
$amount_to = $_GET['amount_to'] ?? '';
$apply_date_from = $_GET['apply_date_from'] ?? '';
$apply_date_to = $_GET['apply_date_to'] ?? '';

// Build query with interest calculations and payment totals
$query = "
    SELECT l.id, l.member_id, u.full_name AS member_name, l.amount, l.interest_rate, l.total_interest,
           l.status, l.apply_date, l.approve_date,
           COALESCE(SUM(lp.amount), 0) as total_paid
    FROM loans l
    JOIN users u ON l.member_id = u.id
    LEFT JOIN loan_payments lp ON l.id = lp.loan_id
    WHERE 1=1
";
$params = [];

if ($status) {
    $query .= " AND l.status = ?";
    $params[] = $status;
}

if ($member_search) {
    $query .= " AND u.full_name LIKE ?";
    $params[] = "%$member_search%";
}

if ($amount_from) {
    $query .= " AND l.amount >= ?";
    $params[] = $amount_from;
}

if ($amount_to) {
    $query .= " AND l.amount <= ?";
    $params[] = $amount_to;
}

if ($apply_date_from) {
    $query .= " AND l.apply_date >= ?";
    $params[] = $apply_date_from;
}

if ($apply_date_to) {
    $query .= " AND l.apply_date <= ?";
    $params[] = $apply_date_to;
}

$query .= " GROUP BY l.id, l.member_id, u.full_name, l.amount, l.interest_rate, l.total_interest, l.status, l.apply_date, l.approve_date
           ORDER BY l.apply_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$loans = $stmt->fetchAll();

// Calculate totals for each loan
foreach ($loans as &$loan) {
    // Total owed = principal + interest
    $loan['total_owed'] = $loan['amount'] + $loan['total_interest'];

    // Remaining = total owed - total paid
    $loan['remaining'] = $loan['total_owed'] - $loan['total_paid'];

    // Ensure remaining doesn't go negative
    if ($loan['remaining'] < 0) {
        $loan['remaining'] = 0;
    }
}
?>

<div class="container">
    <div class="page-header">
        <h2>Loans Management</h2>
    </div>

    <div class="actions">
        <a href="apply_loan.php" class="btn btn-primary">Apply for New Loan</a>
    </div>

    <div class="filters">
        <form method="GET" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="">All</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="member_search">Member Name:</label>
                    <input type="text" name="member_search" id="member_search" value="<?php echo htmlspecialchars($member_search); ?>" placeholder="Search member name">
                </div>
                <div class="filter-group">
                    <label for="amount_from">Amount From:</label>
                    <input type="number" step="0.01" name="amount_from" id="amount_from" value="<?php echo htmlspecialchars($amount_from); ?>" placeholder="Min amount">
                </div>
                <div class="filter-group">
                    <label for="amount_to">Amount To:</label>
                    <input type="number" step="0.01" name="amount_to" id="amount_to" value="<?php echo htmlspecialchars($amount_to); ?>" placeholder="Max amount">
                </div>
            </div>
            <div class="filter-row">
                <div class="filter-group">
                    <label for="apply_date_from">Apply Date From:</label>
                    <input type="date" name="apply_date_from" id="apply_date_from" value="<?php echo htmlspecialchars($apply_date_from); ?>">
                </div>
                <div class="filter-group">
                    <label for="apply_date_to">Apply Date To:</label>
                    <input type="date" name="apply_date_to" id="apply_date_to" value="<?php echo htmlspecialchars($apply_date_to); ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="list_loans.php" class="btn btn-link">Clear Filters</a>
                </div>
            </div>
        </form>
    </div>

    <?php if (empty($loans)): ?>
        <div class="no-data">
            <p>No loan requests recorded yet.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Amount (KES)</th>
                        <th>Status</th>
                        <th>Apply Date</th>
                        <th>Approve Date</th>
                        <th>Interest Rate</th>
                        <th>Total Owed</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                    <tr>
                        <td><?php echo $loan['id']; ?></td>
                        <td><?php echo htmlspecialchars($loan['member_name']); ?></td>
                        <td class="amount-cell"><?php echo number_format($loan['amount'], 2); ?></td>
                        <td>
                            <span class="status status-<?php echo $loan['status']; ?>">
                                <?php echo ucfirst($loan['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($loan['apply_date'])); ?></td>
                        <td><?php echo $loan['approve_date'] ? date('d M Y', strtotime($loan['approve_date'])) : '-'; ?></td>
                        <td class="interest-rate-cell"><?php echo number_format($loan['interest_rate'], 2); ?>%</td>
                        <td class="total-owed-cell"><?php echo number_format($loan['total_owed'], 2); ?></td>
                        <td class="paid-cell"><?php echo number_format($loan['total_paid'], 2); ?></td>
                        <td class="<?php echo $loan['remaining'] > 0 ? 'remaining-positive' : 'remaining-zero'; ?>">
                            <?php echo number_format($loan['remaining'], 2); ?>
                        </td>
                        <td class="actions">
                            <?php if ($loan['status'] === 'pending'): ?>
                                <a href="approve_loan.php?id=<?php echo $loan['id']; ?>" class="btn-link">Approve/Reject</a> |
                            <?php endif; ?>
                            <a href="edit_loan.php?id=<?php echo $loan['id']; ?>" class="btn-link">Edit</a>
                            <?php if ($loan['status'] === 'approved'): ?>
                                | <a href="loan_payments.php?id=<?php echo $loan['id']; ?>" class="btn-link">Record Payment</a> |
                                <a href="loan_schedule.php?id=<?php echo $loan['id']; ?>" class="btn-link">View Schedule</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
    color: #333;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.page-header h2 {
    margin: 0;
    font-size: 2.5em;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.actions {
    margin-bottom: 30px;
    text-align: center;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.btn-primary {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    color: white;
}

.filters {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.filter-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 180px;
    flex: 1;
}

.filter-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    font-size: 14px;
}

.filter-group input,
.filter-group select {
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: white;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
    margin-right: 10px;
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
    color: white;
}

.btn-link {
    background: none;
    border: none;
    padding: 0;
    color: #667eea;
    text-decoration: underline;
    cursor: pointer;
    font-size: 14px;
}

.btn-link:hover {
    color: #5a67d8;
}

.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.data-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.data-table th {
    padding: 18px 15px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
    position: sticky;
    top: 0;
    z-index: 10;
}

.data-table td {
    padding: 14px 15px;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.3s ease;
}

.data-table tbody tr:hover {
    background-color: #f8f9ff;
}

.data-table tbody tr:nth-child(even) {
    background-color: #fafbff;
}

.status {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

.status-pending {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-approved {
    background: linear-gradient(135deg, #d4edda, #a8d5ba);
    color: #155724;
    border: 1px solid #a8d5ba;
}

.status-rejected {
    background: linear-gradient(135deg, #f8d7da, #f1aeb5);
    color: #721c24;
    border: 1px solid #f1aeb5;
}

.status-paid {
    background: linear-gradient(135deg, #d1ecf1, #a6d5e0);
    color: #0c5460;
    border: 1px solid #a6d5e0;
}

.amount-cell {
    font-weight: 700;
    color: #007bff;
    font-size: 15px;
}

.total-owed-cell {
    font-weight: 700;
    color: #6f42c1;
    font-size: 15px;
}

.paid-cell {
    font-weight: 700;
    color: #28a745;
    font-size: 15px;
}

.interest-rate-cell {
    font-weight: 700;
    color: #fd7e14;
    font-size: 15px;
}

.remaining-positive {
    font-weight: 700;
    color: #dc3545;
    font-size: 15px;
}

.remaining-zero {
    font-weight: 700;
    color: #28a745;
    font-size: 15px;
}

.actions {
    min-width: 200px;
}

.actions a {
    margin-right: 8px;
    white-space: nowrap;
}

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
    font-size: 18px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    margin: 20px 0;
    border: 2px dashed #dee2e6;
}

@media (max-width: 1200px) {
    .container {
        padding: 15px;
    }

    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-group {
        min-width: auto;
    }

    .data-table {
        font-size: 12px;
    }

    .data-table th,
    .data-table td {
        padding: 10px 8px;
    }

    .actions {
        min-width: 150px;
    }

    .actions a {
        display: block;
        margin-bottom: 4px;
        margin-right: 0;
    }
}

@media (max-width: 768px) {
    .page-header {
        padding: 20px;
    }

    .page-header h2 {
        font-size: 2em;
    }

    .table-container {
        overflow-x: auto;
    }

    .data-table {
        min-width: 1000px;
    }
}
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>