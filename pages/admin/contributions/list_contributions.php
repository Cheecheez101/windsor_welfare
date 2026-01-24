<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

// Get filter parameters
$member_filter = $_GET['member'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$amount_min = $_GET['amount_min'] ?? '';
$amount_max = $_GET['amount_max'] ?? '';

// Build the WHERE clause dynamically
$where_conditions = [];
$params = [];

if (!empty($member_filter)) {
    $where_conditions[] = "c.member_id = ?";
    $params[] = $member_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "c.contribution_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "c.contribution_date <= ?";
    $params[] = $date_to;
}

if (!empty($payment_method)) {
    $where_conditions[] = "c.payment_method = ?";
    $params[] = $payment_method;
}

if (!empty($amount_min)) {
    $where_conditions[] = "c.amount >= ?";
    $params[] = $amount_min;
}

if (!empty($amount_max)) {
    $where_conditions[] = "c.amount <= ?";
    $params[] = $amount_max;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Fetch filtered contributions with member name
$query = "
    SELECT c.id, c.member_id, u.full_name AS member_name, c.amount, c.contribution_date, c.payment_method
    FROM contributions c
    JOIN users u ON c.member_id = u.id
    $where_clause
    ORDER BY c.contribution_date DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$contributions = $stmt->fetchAll();

// Get list of members for filter dropdown
$members = $pdo->query("SELECT id, full_name FROM users WHERE role='member' ORDER BY full_name")->fetchAll();

// Get total count for summary
$total_query = "SELECT COUNT(*) as total, SUM(amount) as total_amount FROM contributions c $where_clause";
$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute($params);
$summary = $total_stmt->fetch();
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

h2 {
    color: #2c3e50;
    text-align: center;
    margin-bottom: 30px;
    font-size: 2.5em;
    font-weight: 700;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: none;
    cursor: pointer;
}

.btn:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d, #5a6268);
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #5a6268, #495057);
}

.table-container {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.data-table th,
.data-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    font-weight: bold;
    font-size: 1em;
    position: sticky;
    top: 0;
}

.data-table tr:nth-child(even) {
    background: #f8f9fa;
}

.data-table tr:hover {
    background: #e9ecef;
    transition: background 0.3s ease;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.amount-cell {
    font-weight: bold;
    color: #28a745;
    font-size: 1.1em;
}

.payment-method {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: bold;
}

.payment-method.cash { background: #d4edda; color: #155724; }
.payment-method.card { background: #cce5ff; color: #004085; }
.payment-method.mpesa { background: #fff3cd; color: #856404; }

.no-data i {
    font-size: 3em;
    margin-bottom: 20px;
    display: block;
    color: #dee2e6;
}

.filter-section {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
    font-size: 0.9em;
}

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 0.9em;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.filter-buttons {
    display: flex;
    gap: 10px;
    align-items: end;
}

.btn-filter {
    padding: 8px 16px;
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: 500;
}

.btn-filter:hover {
    background: linear-gradient(135deg, #138496, #117a8b);
}

.btn-clear {
    padding: 8px 16px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: 500;
}

.btn-clear:hover {
    background: #5a6268;
}

.summary-card {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
}

.summary-card h3 {
    margin: 0 0 10px 0;
    font-size: 1.5em;
}

.summary-card p {
    margin: 0;
    opacity: 0.9;
}

@media (max-width: 768px) {
    .data-table th,
    .data-table td {
        padding: 10px 8px;
        font-size: 0.9em;
    }

    .action-buttons {
        flex-direction: column;
        align-items: center;
    }

    .table-container {
        padding: 15px;
    }

    .filter-form {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .filter-section {
        padding: 20px;
    }

    .summary-card {
        padding: 15px;
    }

    .summary-card h3 {
        font-size: 1.2em;
    }
}
</style>

<div class="container">
    <h2>Contributions Management</h2>

    <div class="action-buttons">
        <a href="add_contribution.php" class="btn">➕ Add New Contribution</a>
        <a href="monthly_contributions.php" class="btn btn-secondary">📅 Monthly Bulk Entry</a>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="member">Member</label>
                <select name="member" id="member">
                    <option value="">All Members</option>
                    <?php foreach ($members as $member): ?>
                        <option value="<?php echo $member['id']; ?>" <?php echo $member_filter == $member['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($member['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="date_from">Date From</label>
                <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>

            <div class="filter-group">
                <label for="date_to">Date To</label>
                <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>

            <div class="filter-group">
                <label for="payment_method">Payment Method</label>
                <select name="payment_method" id="payment_method">
                    <option value="">All Methods</option>
                    <option value="cash" <?php echo $payment_method == 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="card" <?php echo $payment_method == 'card' ? 'selected' : ''; ?>>Card</option>
                    <option value="mpesa" <?php echo $payment_method == 'mpesa' ? 'selected' : ''; ?>>M-Pesa</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="amount_min">Min Amount (KES)</label>
                <input type="number" name="amount_min" id="amount_min" step="0.01" min="0" value="<?php echo htmlspecialchars($amount_min); ?>" placeholder="0.00">
            </div>

            <div class="filter-group">
                <label for="amount_max">Max Amount (KES)</label>
                <input type="number" name="amount_max" id="amount_max" step="0.01" min="0" value="<?php echo htmlspecialchars($amount_max); ?>" placeholder="0.00">
            </div>

            <div class="filter-buttons">
                <button type="submit" class="btn-filter">🔍 Filter</button>
                <a href="list_contributions.php" class="btn-clear">🗑️ Clear</a>
            </div>
        </form>
    </div>

    <!-- Summary Card -->
    <?php if (!empty($contributions)): ?>
    <div class="summary-card">
        <h3><?php echo number_format($summary['total']); ?> Contributions</h3>
        <p>Total Amount: KES <?php echo number_format($summary['total_amount'], 2); ?></p>
    </div>
    <?php endif; ?>

    <?php if (empty($contributions)): ?>
        <div class="no-data">
            <i>📊</i>
            <p>No contributions recorded yet.</p>
            <p>Start by adding the first contribution!</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Amount (KES)</th>
                        <th>Payment Method</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contributions as $c): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['member_name']); ?></td>
                        <td class="amount-cell">KES <?php echo number_format($c['amount'], 2); ?></td>
                        <td><span class="payment-method <?php echo $c['payment_method']; ?>"><?php echo ucfirst($c['payment_method']); ?></span></td>
                        <td><?php echo date('d M Y', strtotime($c['contribution_date'])); ?></td>
                        <td>
                            <small style="color: #6c757d;">View</small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>