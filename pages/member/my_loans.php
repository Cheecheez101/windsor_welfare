<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_member();

$user_id = $_SESSION['user_id'];

// Fetch my loans with payment information
try {
    $stmt = $pdo->prepare("
        SELECT 
            l.id, l.amount, l.status, l.apply_date, l.approve_date, l.interest_rate, l.total_interest,
            COALESCE(SUM(lp.amount), 0) as total_paid
        FROM loans l
        LEFT JOIN loan_payments lp ON l.id = lp.loan_id
        WHERE l.member_id = ?
        GROUP BY l.id
        ORDER BY l.apply_date DESC
    ");
    $stmt->execute([$user_id]);
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure $loans is an array
    if ($loans === false) {
        $loans = [];
        error_log("Query failed in my_loans.php");
    }
} catch (PDOException $e) {
    $loans = [];
    error_log("Database error in my_loans.php: " . $e->getMessage());
}

// Calculate remaining balance for each loan
foreach ($loans as &$loan) {
    if (is_array($loan)) {
        $total_owed = $loan['amount'] + $loan['total_interest'];
        $loan['total_owed'] = $total_owed;
        $loan['remaining_balance'] = $total_owed - $loan['total_paid'];
        $loan['is_fully_paid'] = $loan['remaining_balance'] <= 0;
    }
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="my_loans_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, ['ID', 'Amount (KES)', 'Status', 'Apply Date', 'Approve Date', 'Interest Rate (%)', 'Total Interest (KES)', 'Total Paid (KES)', 'Remaining Balance (KES)']);

    // Data rows
    foreach ($loans as $loan) {
        fputcsv($output, [
            $loan['id'],
            number_format($loan['amount'], 2),
            ucfirst($loan['status']),
            $loan['apply_date'] ? date('d M Y', strtotime($loan['apply_date'])) : '',
            $loan['approve_date'] ? date('d M Y', strtotime($loan['approve_date'])) : '',
            $loan['interest_rate'],
            number_format($loan['total_interest'], 2),
            number_format($loan['total_paid'], 2),
            number_format($loan['remaining_balance'], 2)
        ]);
    }

    fclose($output);
    exit;
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set title
    $sheet->setTitle('My Loans');

    // Headers
    $headers = ['ID', 'Amount (KES)', 'Status', 'Apply Date', 'Approve Date', 'Interest Rate (%)', 'Total Interest (KES)', 'Total Paid (KES)', 'Remaining Balance (KES)'];
    foreach ($headers as $colIndex => $header) {
        $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
    }

    // Style headers
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

    // Data rows
    $rowIndex = 2;
    foreach ($loans as $loan) {
        $sheet->setCellValueByColumnAndRow(1, $rowIndex, $loan['id']);
        $sheet->setCellValueByColumnAndRow(2, $rowIndex, $loan['amount']);
        $sheet->setCellValueByColumnAndRow(3, $rowIndex, ucfirst($loan['status']));
        $sheet->setCellValueByColumnAndRow(4, $rowIndex, $loan['apply_date'] ? date('d M Y', strtotime($loan['apply_date'])) : '');
        $sheet->setCellValueByColumnAndRow(5, $rowIndex, $loan['approve_date'] ? date('d M Y', strtotime($loan['approve_date'])) : '');
        $sheet->setCellValueByColumnAndRow(6, $rowIndex, $loan['interest_rate']);
        $sheet->setCellValueByColumnAndRow(7, $rowIndex, $loan['total_interest']);
        $sheet->setCellValueByColumnAndRow(8, $rowIndex, $loan['total_paid']);
        $sheet->setCellValueByColumnAndRow(9, $rowIndex, $loan['remaining_balance']);
        $rowIndex++;
    }

    // Auto-size columns
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Format numbers
    $sheet->getStyle('B2:B' . ($rowIndex - 1))->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('G2:I' . ($rowIndex - 1))->getNumberFormat()->setFormatCode('#,##0.00');

    // Output Excel file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="my_loans_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

// Handle payment submission
$payment_error = '';
$payment_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
    $loan_id = intval($_POST['loan_id']);
    $payment_amount = floatval($_POST['payment_amount']);

    // Verify the loan belongs to this user and is approved
    $stmt = $pdo->prepare("SELECT id, amount, total_interest, status FROM loans WHERE id = ? AND member_id = ?");
    $stmt->execute([$loan_id, $user_id]);
    $loan = $stmt->fetch();

    if (!$loan) {
        $payment_error = "Loan not found.";
    } elseif ($loan['status'] !== 'approved') {
        $payment_error = "Can only make payments on approved loans.";
    } elseif ($payment_amount <= 0) {
        $payment_error = "Please enter a valid payment amount.";
    } else {
        // Calculate remaining balance
        $total_owed = $loan['amount'] + $loan['total_interest'];
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM loan_payments WHERE loan_id = ?");
        $stmt->execute([$loan_id]);
        $total_paid = $stmt->fetch()['total_paid'];
        $remaining = $total_owed - $total_paid;

        if ($payment_amount > $remaining) {
            $payment_error = "Payment amount cannot exceed remaining balance of KES " . number_format($remaining, 2);
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO loan_payments (loan_id, amount, payment_date) VALUES (?, ?, CURDATE())");
                $stmt->execute([$loan_id, $payment_amount]);

                // Log the payment
                require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/functions.php';
                log_audit($pdo, 'INSERT', 'loan_payments', $pdo->lastInsertId(), null, json_encode([
                    'loan_id' => $loan_id,
                    'amount' => $payment_amount,
                    'payment_date' => date('Y-m-d')
                ]));

                // Check if loan is now fully paid
                $new_total_paid = $total_paid + $payment_amount;
                if ($new_total_paid >= $total_owed) {
                    $stmt = $pdo->prepare("UPDATE loans SET status = 'paid' WHERE id = ?");
                    $stmt->execute([$loan_id]);
                    
                    // Log loan status change
                    log_audit($pdo, 'UPDATE', 'loans', $loan_id, json_encode(['status' => 'approved']), json_encode(['status' => 'paid']));
                }

                $payment_success = "Payment of KES " . number_format($payment_amount, 2) . " recorded successfully!";

                // Refresh loan data
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } catch (PDOException $e) {
                $payment_error = "Error recording payment: " . $e->getMessage();
            }
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

.loans-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.loans-table table {
    width: 100%;
    border-collapse: collapse;
}

.loans-table th {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.loans-table td {
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
    font-size: 14px;
}

.loans-table tbody tr:hover {
    background-color: #f8f9fa;
}

.loans-table tbody tr:last-child td {
    border-bottom: none;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-approved {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-rejected {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.amount-cell {
    font-weight: bold;
    color: #007bff;
    font-size: 16px;
}

.paid-amount {
    font-weight: bold;
    color: #28a745;
    font-size: 16px;
}

.paid-text {
    color: #28a745;
    font-weight: bold;
}

.pending-text {
    color: #6c757d;
}

.btn-pay {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.3s ease;
}

.btn-pay:hover {
    background: linear-gradient(135deg, #20c997, #17a2b8);
    transform: translateY(-1px);
}

.btn-cancel {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn-cancel:hover {
    background: #5a6268;
}

.no-loans {
    background: white;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.no-loans p {
    font-size: 18px;
    color: #6c757d;
    margin: 0;
}

.back-link {
    text-align: center;
    margin-top: 30px;
}

.btn {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    transition: background 0.3s ease, transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-50px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5em;
}

.close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close:hover {
    color: #e9ecef;
}

.payment-form {
    padding: 30px;
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

.balance-info {
    display: block;
    margin-top: 5px;
    color: #6c757d;
    font-size: 14px;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 30px;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: bold;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
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

@media (max-width: 768px) {
    .container {
        padding: 15px;
    }

    .loans-table {
        overflow-x: auto;
    }

    .loans-table table {
        min-width: 800px;
    }

    .loans-table th,
    .loans-table td {
        padding: 8px;
        font-size: 12px;
    }

    .btn-pay {
        padding: 6px 12px;
        font-size: 12px;
    }

    .modal-content {
        margin: 5% auto;
        width: 95%;
    }

    .payment-form {
        padding: 20px;
    }

    .modal-actions {
        flex-direction: column;
    }

    .modal-actions button {
        width: 100%;
        margin-bottom: 10px;
    }

    h2 {
        font-size: 2em;
    }
}

.export-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-bottom: 20px;
}

.btn-export {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-csv {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-csv:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

.btn-excel {
    background: linear-gradient(135deg, #217346, #28a745);
    color: white;
    box-shadow: 0 4px 15px rgba(33, 115, 70, 0.3);
}

.btn-excel:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(33, 115, 70, 0.4);
}
</style>

<div class="container">
    <h2>My Loans</h2>

    <div class="export-buttons">
        <a href="?export=csv" class="btn-export btn-csv">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
        <a href="?export=excel" class="btn-export btn-excel">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>

    <?php if (empty($loans)): ?>
        <div class="no-loans">
            <p>No loan requests recorded yet.</p>
        </div>
    <?php else: ?>
        <div class="loans-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
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
                    <?php foreach ($loans as $l): ?>
                        <?php if (is_array($l)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($l['id'] ?? 'N/A'); ?></td>
                            <td class="amount-cell"><?php echo isset($l['amount']) ? number_format($l['amount'], 2) : '0.00'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($l['status'] ?? 'unknown'); ?>">
                                    <?php echo ucfirst($l['status'] ?? 'Unknown'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($l['apply_date'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($l['approve_date'] ?? '-'); ?></td>
                            <td><?php echo isset($l['interest_rate']) ? $l['interest_rate'] : '0.00'; ?>%</td>
                            <td class="amount-cell"><?php echo isset($l['total_owed']) ? number_format($l['total_owed'], 2) : '0.00'; ?></td>
                            <td><?php echo isset($l['total_paid']) ? number_format($l['total_paid'], 2) : '0.00'; ?></td>
                            <td class="<?php echo (isset($l['remaining_balance']) && $l['remaining_balance'] <= 0) ? 'paid-amount' : 'amount-cell'; ?>">
                                <?php echo isset($l['remaining_balance']) ? number_format(max(0, $l['remaining_balance']), 2) : '0.00'; ?>
                            </td>
                            <td>
                                <?php if (isset($l['status']) && $l['status'] === 'approved' && isset($l['is_fully_paid']) && !$l['is_fully_paid']): ?>
                                    <button class="btn-pay" onclick="openPaymentModal(<?php echo intval($l['id']); ?>, <?php echo floatval($l['remaining_balance'] ?? 0); ?>)">
                                        Make Payment
                                    </button>
                                <?php elseif (isset($l['is_fully_paid']) && $l['is_fully_paid']): ?>
                                    <span class="paid-text">✓ Fully Paid</span>
                                <?php else: ?>
                                    <span class="pending-text">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Make Loan Payment</h3>
                <span class="close" onclick="closePaymentModal()">&times;</span>
            </div>
            <form method="POST" class="payment-form">
                <input type="hidden" name="loan_id" id="modal_loan_id">
                <div class="form-group">
                    <label for="payment_amount">Payment Amount (KES)</label>
                    <input type="number" name="payment_amount" id="payment_amount" step="0.01" min="1" required>
                    <small id="balance-info" class="balance-info"></small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closePaymentModal()">Cancel</button>
                    <button type="submit" name="make_payment" class="btn-pay">Submit Payment</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($payment_success || $payment_error): ?>
        <div class="alert <?php echo $payment_success ? 'alert-success' : 'alert-error'; ?>">
            <?php echo $payment_success ?: $payment_error; ?>
        </div>
    <?php endif; ?>

    <div class="back-link">
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </div>
</div>

<script>
function openPaymentModal(loanId, remainingBalance) {
    document.getElementById('paymentModal').style.display = 'block';
    document.getElementById('modal_loan_id').value = loanId;
    document.getElementById('payment_amount').max = remainingBalance;
    document.getElementById('balance-info').textContent = 
        'Remaining balance: KES ' + remainingBalance.toLocaleString('en-KE', {minimumFractionDigits: 2});
    document.getElementById('payment_amount').focus();
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
    document.getElementById('payment_amount').value = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target === modal) {
        closePaymentModal();
    }
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s ease-out';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.style.display = 'none';
        }, 500);
    });
}, 5000);
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>