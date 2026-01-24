<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = $_POST['month'] ?? '';
    $year  = $_POST['year']  ?? '';
    $date  = "$year-$month-01"; // first day of month

    $amounts = $_POST['amount'] ?? [];

    $inserted = 0;
    foreach ($amounts as $member_id => $amount) {
        $amount = trim($amount);
        if (is_numeric($amount) && $amount > 0) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO contributions (member_id, amount, contribution_date)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$member_id, $amount, $date]);
                $inserted++;
            } catch (Exception $e) {
                // silently skip duplicates or errors for bulk
            }
        }
    }

    if ($inserted > 0) {
        $success = "$inserted contributions recorded for $month/$year.";
    } else if (!empty($amounts)) {
        $error = "No valid amounts were entered.";
    }
}

// Get all members
$members = $pdo->query("SELECT id, full_name AS name FROM users WHERE role='member' ORDER BY full_name")->fetchAll();

// Default to current month/year
$current_month = date('m');
$current_year  = date('Y');
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
    color: #2c3e50;
    text-align: center;
    margin-bottom: 30px;
    font-size: 2.5em;
    font-weight: 700;
}

.form-container {
    background: white;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.month-year-selector {
    display: flex;
    gap: 15px;
    align-items: end;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.month-year-selector .form-group {
    flex: 1;
    min-width: 150px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 1em;
}

.form-group select,
.form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1em;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    box-sizing: border-box;
}

.form-group select:focus,
.form-group input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.members-table {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow-x: auto;
}

.members-table table {
    width: 100%;
    border-collapse: collapse;
}

.members-table th,
.members-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.members-table th {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    font-weight: bold;
    font-size: 1em;
    position: sticky;
    top: 0;
}

.members-table tr:nth-child(even) {
    background: #f8f9fa;
}

.members-table tr:hover {
    background: #e9ecef;
    transition: background 0.3s ease;
}

.members-table input[type="number"] {
    width: 120px;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 0.9em;
    text-align: right;
}

.members-table input[type="number"]:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.btn {
    display: inline-block;
    padding: 14px 30px;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    font-size: 1em;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: none;
    cursor: pointer;
}

.btn:hover {
    background: linear-gradient(135deg, #20c997, #17a2b8);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-weight: 500;
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

.back-link a {
    color: #6c757d;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.back-link a:hover {
    color: #007bff;
}

@media (max-width: 768px) {
    .container {
        padding: 15px;
    }

    .form-container,
    .members-table {
        padding: 20px;
    }

    .month-year-selector {
        flex-direction: column;
    }

    .members-table th,
    .members-table td {
        padding: 10px 8px;
        font-size: 0.9em;
    }

    .members-table input[type="number"] {
        width: 100px;
    }

    h2 {
        font-size: 2em;
    }
}
</style>

<div class="container">
    <h2>Monthly Bulk Contribution Entry</h2>

    <div class="form-container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="month-year-selector">
                <div class="form-group">
                    <label>Month</label>
                    <select name="month" required>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php printf("%02d", $m); ?>" <?php echo $m == $current_month ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0,0,0,$m)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" name="year" value="<?php echo $current_year; ?>" min="2000" max="2035" required>
                </div>
            </div>

            <div class="members-table">
                <table>
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Contribution Amount (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['name']); ?></td>
                            <td>
                                <input type="number" name="amount[<?php echo $m['id']; ?>]" step="0.01" min="0" placeholder="0.00">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Save Monthly Contributions
                </button>
            </div>
        </form>
    </div>

    <div class="back-link">
        <a href="list_contributions.php">← Back to contributions list</a>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>