<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

// Get comprehensive statistics
$member_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role='member' AND status='active'")->fetchColumn();
$admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$total_savings = $pdo->query("SELECT SUM(amount) FROM contributions")->fetchColumn() ?? 0;
$pending_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='pending'")->fetchColumn();
$approved_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status='approved'")->fetchColumn();
$total_loans_amount = $pdo->query("SELECT SUM(amount) FROM loans WHERE status IN ('approved', 'paid')")->fetchColumn() ?? 0;

// Recent activities
$recent_contributions = $pdo->query("
    SELECT c.amount, c.contribution_date, u.full_name
    FROM contributions c
    JOIN users u ON c.member_id = u.id
    ORDER BY c.contribution_date DESC
    LIMIT 5
")->fetchAll();

$recent_loans = $pdo->query("
    SELECT l.amount, l.apply_date, l.status, u.full_name
    FROM loans l
    JOIN users u ON l.member_id = u.id
    ORDER BY l.apply_date DESC
    LIMIT 5
")->fetchAll();

// Monthly contributions for the current year
$current_year = date('Y');
$monthly_contributions = [];
for ($month = 1; $month <= 12; $month++) {
    $month_name = date('M', mktime(0, 0, 0, $month, 1));
    $amount = $pdo->query("
        SELECT SUM(amount) FROM contributions
        WHERE YEAR(contribution_date) = $current_year AND MONTH(contribution_date) = $month
    ")->fetchColumn() ?? 0;
    $monthly_contributions[] = ['month' => $month_name, 'amount' => $amount];
}
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

.welcome-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.welcome-section h3 {
    margin: 0 0 10px 0;
    font-size: 2em;
}

.welcome-section p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1em;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.stat-card.primary::before {
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.stat-card.success::before {
    background: linear-gradient(90deg, #28a745, #20c997);
}

.stat-card.warning::before {
    background: linear-gradient(90deg, #ffc107, #fd7e14);
}

.stat-card.danger::before {
    background: linear-gradient(90deg, #dc3545, #fd7e14);
}

.stat-icon {
    font-size: 3em;
    margin-bottom: 15px;
    opacity: 0.8;
}

.stat-number {
    font-size: 2.5em;
    font-weight: 700;
    margin-bottom: 5px;
    color: #2c3e50;
}

.stat-label {
    font-size: 1.1em;
    color: #6c757d;
    font-weight: 500;
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

.activity-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
}

.activity-section h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.5em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.activity-list {
    list-style: none;
    padding: 0;
}

.activity-item {
    padding: 15px 0;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-text {
    font-weight: 500;
    color: #2c3e50;
}

.activity-amount {
    font-weight: 700;
    color: #28a745;
}

.activity-date {
    color: #6c757d;
    font-size: 0.9em;
}

.quick-actions {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
}

.quick-actions h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.5em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.action-buttons {
    display: grid;
    grid-template-columns: 1fr;
    gap: 15px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 10px;
    text-decoration: none;
    color: #2c3e50;
    font-weight: 600;
    transition: all 0.3s ease;
}

.action-btn:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.action-btn i {
    font-size: 1.5em;
    width: 30px;
}

.chart-container {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
    margin-bottom: 30px;
}

.chart-container h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.5em;
}

.chart-placeholder {
    height: 300px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 1.1em;
    border: 2px dashed #dee2e6;
}

@media (max-width: 768px) {
    .container {
        padding: 15px;
    }

    .welcome-section {
        padding: 30px 20px;
    }

    .welcome-section h3 {
        font-size: 1.5em;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-card {
        padding: 20px;
    }

    .activity-section,
    .quick-actions,
    .chart-container {
        padding: 20px;
    }
}
</style>

<div class="container">
    <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>

    <!-- Welcome Section -->
    <div class="welcome-section">
        <h3>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h3>
        <p>Manage your welfare system efficiently with comprehensive oversight and control.</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?php echo number_format($member_count); ?></div>
            <div class="stat-label">Active Members</div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-piggy-bank"></i>
            </div>
            <div class="stat-number">KES <?php echo number_format($total_savings, 0); ?></div>
            <div class="stat-label">Total Savings</div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-number"><?php echo number_format($pending_loans); ?></div>
            <div class="stat-label">Pending Loans</div>
        </div>

        <div class="stat-card danger">
            <div class="stat-icon">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="stat-number"><?php echo number_format($approved_loans); ?></div>
            <div class="stat-label">Active Loans</div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="chart-container">
        <h3><i class="fas fa-chart-line"></i> Monthly Contributions (<?php echo $current_year; ?>)</h3>
        <div class="chart-placeholder">
            <div style="text-align: center;">
                <i class="fas fa-chart-bar" style="font-size: 3em; margin-bottom: 15px; opacity: 0.5;"></i>
                <div>Monthly Contributions Chart</div>
                <small style="display: block; margin-top: 10px;">
                    <?php
                    $total_year = array_sum(array_column($monthly_contributions, 'amount'));
                    echo 'Total: KES ' . number_format($total_year, 0);
                    ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">
        <!-- Recent Activities -->
        <div class="activity-section">
            <h3><i class="fas fa-history"></i> Recent Activities</h3>

            <h4 style="color: #2c3e50; margin-bottom: 15px;"><i class="fas fa-coins"></i> Latest Contributions</h4>
            <ul class="activity-list">
                <?php if (empty($recent_contributions)): ?>
                    <li class="activity-item">
                        <span class="activity-text">No recent contributions</span>
                    </li>
                <?php else: ?>
                    <?php foreach ($recent_contributions as $contrib): ?>
                        <li class="activity-item">
                            <div>
                                <span class="activity-text"><?php echo htmlspecialchars($contrib['full_name']); ?></span>
                                <div class="activity-date"><?php echo date('M d, Y', strtotime($contrib['contribution_date'])); ?></div>
                            </div>
                            <span class="activity-amount">KES <?php echo number_format($contrib['amount'], 2); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <h4 style="color: #2c3e50; margin: 30px 0 15px 0;"><i class="fas fa-file-contract"></i> Recent Loan Applications</h4>
            <ul class="activity-list">
                <?php if (empty($recent_loans)): ?>
                    <li class="activity-item">
                        <span class="activity-text">No recent loan applications</span>
                    </li>
                <?php else: ?>
                    <?php foreach ($recent_loans as $loan): ?>
                        <li class="activity-item">
                            <div>
                                <span class="activity-text"><?php echo htmlspecialchars($loan['full_name']); ?> - <?php echo ucfirst($loan['status']); ?></span>
                                <div class="activity-date"><?php echo date('M d, Y', strtotime($loan['apply_date'])); ?></div>
                            </div>
                            <span class="activity-amount">KES <?php echo number_format($loan['amount'], 2); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="action-buttons">
                <a href="members/list_members.php" class="action-btn">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>

                <a href="contributions/list_contributions.php" class="action-btn">
                    <i class="fas fa-coins"></i>
                    <span>View Contributions</span>
                </a>

                <a href="loans/list_loans.php" class="action-btn">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Manage Loans</span>
                </a>

                <a href="reports/annual_summary.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i>
                    <span>View Reports</span>
                </a>

                <a href="members/add_member.php" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Add New User</span>
                </a>

                <a href="../member/dashboard.php" class="action-btn">
                    <i class="fas fa-eye"></i>
                    <span>Preview Member View</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>