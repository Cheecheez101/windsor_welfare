<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/role_auth.php';
require_member();
require_once '../../includes/header.php';

$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $contribution_date = $_POST['contribution_date'] ?? date('Y-m-d');
    $payment_method = $_POST['payment_method'] ?? 'card';

    if ($amount <= 0) {
        $error = "Please enter a valid amount.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contributions (member_id, amount, contribution_date, payment_method)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $amount, $contribution_date, $payment_method]);
            $success = "Contribution of KES " . number_format($amount, 2) . " recorded successfully!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
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
    max-width: 800px;
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
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 1em;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1em;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-group input[type="number"] {
    -moz-appearance: textfield;
}

.form-group input[type="number"]::-webkit-outer-spin-button,
.form-group input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
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
    width: 100%;
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

    .form-container {
        padding: 25px;
    }

    h2 {
        font-size: 2em;
    }
}
</style>

<div class="container">
    <h2>Add New Contribution</h2>

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
            <div class="form-group">
                <label for="amount">Amount (KES)</label>
                <input type="number" name="amount" id="amount" step="0.01" min="1" placeholder="Enter contribution amount" required>
            </div>

            <div class="form-group">
                <label for="contribution_date">Contribution Date</label>
                <input type="date" name="contribution_date" id="contribution_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="card">💳 Card</option>
                    <option value="mpesa">📱 M-Pesa</option>
                    <option value="cash">💼 Payroll Deduction</option>
                </select>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-plus"></i> Submit Contribution
            </button>
        </form>
    </div>

    <div class="back-link">
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>