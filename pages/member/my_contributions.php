<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/role_auth.php';
require_member();
require_once '../../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get filter parameters
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$amount_min = $_GET['amount_min'] ?? '';
$amount_max = $_GET['amount_max'] ?? '';

// Build the WHERE clause dynamically
$where_conditions = ["member_id = ?"];
$params = [$user_id];

if (!empty($date_from)) {
    $where_conditions[] = "contribution_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "contribution_date <= ?";
    $params[] = $date_to;
}

if (!empty($payment_method)) {
    $where_conditions[] = "payment_method = ?";
    $params[] = $payment_method;
}

if (!empty($amount_min)) {
    $where_conditions[] = "amount >= ?";
    $params[] = $amount_min;
}

if (!empty($amount_max)) {
    $where_conditions[] = "amount <= ?";
    $params[] = $amount_max;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Fetch filtered contributions
$query = "
    SELECT id, amount, contribution_date, type, payment_method
    FROM contributions
    $where_clause
    ORDER BY contribution_date DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$contributions = $stmt->fetchAll();

// Get summary for filtered results
$summary_query = "SELECT COUNT(*) as total, SUM(amount) as total_amount FROM contributions $where_clause";
$summary_stmt = $pdo->prepare($summary_query);
$summary_stmt->execute($params);
$summary = $summary_stmt->fetch();
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

.table-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
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
}

.data-table tr:nth-child(even) {
    background: #f8f9fa;
}

.data-table tr:hover {
    background: #e9ecef;
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

.no-data {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
    font-size: 1.2em;
}

.no-data i {
    font-size: 3em;
    margin-bottom: 20px;
    display: block;
    color: #dee2e6;
}

.filter-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: 500;
}

.btn-filter:hover {
    background: linear-gradient(135deg, #20c997, #17a2b8);
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
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-clear:hover {
    background: #5a6268;
}

.summary-card {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
}

.summary-card h3 {
    margin: 0 0 5px 0;
    font-size: 1.2em;
}

.summary-card p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9em;
}

.back-link {
    text-align: center;
    margin-top: 30px;
}

.btn {
    display: inline-block;
    padding: 12px 24px;
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

@media (max-width: 768px) {
    .data-table th,
    .data-table td {
        padding: 10px 8px;
        font-size: 0.9em;
    }
    
    .table-container {
        padding: 15px;
    }

    .filter-form {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .filter-section {
        padding: 15px;
    }

    .summary-card {
        padding: 12px;
    }

    .summary-card h3 {
        font-size: 1.1em;
    }
}
</style>

<div class="container">
    <h2>My Contributions</h2>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
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
                <a href="my_contributions.php" class="btn-clear">🗑️ Clear</a>
            </div>
        </form>
    </div>

    <!-- Summary Card -->
    <?php if (!empty($contributions)): ?>
    <div class="summary-card">
        <h3><?php echo number_format($summary['total']); ?> Contributions</h3>
        <p>Total: KES <?php echo number_format($summary['total_amount'], 2); ?></p>
    </div>
    <?php endif; ?>

    <?php if (empty($contributions)): ?>
        <div class="no-data">
            <i>📊</i>
            <p>No contributions recorded yet.</p>
            <p>Start building your savings by making your first contribution!</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Amount (KES)</th>
                        <th>Payment Method</th>
                        <th>Type</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contributions as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td class="amount-cell">KES <?php echo number_format($c['amount'], 2); ?></td>
                            <td><span class="payment-method <?php echo $c['payment_method']; ?>"><?php echo ucfirst($c['payment_method']); ?></span></td>
                            <td><?php echo htmlspecialchars($c['type'] ?? 'N/A'); ?></td>
                            <td><?php echo date('d M Y', strtotime($c['contribution_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="back-link">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>