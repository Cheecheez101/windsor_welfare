<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();

// Optional status filter
$status = $_GET['status'] ?? ''; // all, pending, approved, paid, rejected

$query = "
    SELECT l.id, l.member_id, u.full_name AS member_name, l.amount, l.status,
           l.apply_date, l.approve_date, l.interest_rate,
           COALESCE(SUM(lp.amount), 0) AS total_paid,
           l.total_interest
    FROM loans l
    JOIN users u ON l.member_id = u.id
    LEFT JOIN loan_payments lp ON l.id = lp.loan_id
";
$params = [];

if ($status) {
    $query .= " WHERE l.status = ?";
    $params[] = $status;
}

$query .= " GROUP BY l.id, l.member_id, member_name, l.amount, l.status, l.apply_date, l.approve_date, l.interest_rate, l.total_interest
           ORDER BY l.apply_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$loans = $stmt->fetchAll();

// Calculate totals
$total_loans = count($loans);
$total_amount = array_sum(array_column($loans, 'amount'));
$total_interest = array_sum(array_column($loans, 'total_interest'));
$total_outstanding = 0;

foreach ($loans as &$loan) {
    // Outstanding balance = loan amount + accrued interest - payments made
    $loan['outstanding_balance'] = $loan['amount'] + $loan['total_interest'] - $loan['total_paid'];
    $total_outstanding += $loan['outstanding_balance'];
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    $filename = 'loan_report_' . ($status ? $status . '_' : '') . date('Y-m-d') . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, ['ID', 'Member', 'Amount (KES)', 'Interest Rate (%)', 'Total Interest (KES)', 'Outstanding Balance (KES)', 'Status', 'Requested', 'Approved']);

    // Data rows
    foreach ($loans as $loan) {
        fputcsv($output, [
            $loan['id'],
            $loan['member_name'],
            number_format($loan['amount'], 2),
            number_format($loan['interest_rate'], 2) . '%',
            number_format($loan['total_interest'], 2),
            number_format($loan['outstanding_balance'], 2),
            ucfirst($loan['status']),
            date('d M Y', strtotime($loan['apply_date'])),
            $loan['approve_date'] ? date('d M Y', strtotime($loan['approve_date'])) : '-'
        ]);
    }

    // Add totals
    fputcsv($output, []); // Empty row
    fputcsv($output, ['Totals', '', number_format($total_amount, 2), '', number_format($total_interest, 2), number_format($total_outstanding, 2), '', '', '']);

    fclose($output);
    exit;
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set title
    $sheet->setTitle('Loan Report' . ($status ? ' - ' . ucfirst($status) : ''));

    // Headers
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'MEMBER');
    $sheet->setCellValue('C1', 'AMOUNT (KES)');
    $sheet->setCellValue('D1', 'INTEREST RATE (%)');
    $sheet->setCellValue('E1', 'TOTAL INTEREST (KES)');
    $sheet->setCellValue('F1', 'OUTSTANDING BALANCE (KES)');
    $sheet->setCellValue('G1', 'STATUS');
    $sheet->setCellValue('H1', 'REQUESTED');
    $sheet->setCellValue('I1', 'APPROVED');

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
        $sheet->setCellValue('A' . $rowIndex, $loan['id']);
        $sheet->setCellValue('B' . $rowIndex, $loan['member_name']);
        $sheet->setCellValue('C' . $rowIndex, $loan['amount']);
        $sheet->setCellValue('D' . $rowIndex, $loan['interest_rate']);
        $sheet->setCellValue('E' . $rowIndex, $loan['total_interest']);
        $sheet->setCellValue('F' . $rowIndex, $loan['outstanding_balance']);
        $sheet->setCellValue('G' . $rowIndex, ucfirst($loan['status']));
        $sheet->setCellValue('H' . $rowIndex, date('d M Y', strtotime($loan['apply_date'])));
        $sheet->setCellValue('I' . $rowIndex, $loan['approve_date'] ? date('d M Y', strtotime($loan['approve_date'])) : '-');
        $rowIndex++;
    }

    // Add totals
    $rowIndex++; // Empty row
    $sheet->setCellValue('B' . $rowIndex, 'Totals');
    $sheet->setCellValue('C' . $rowIndex, $total_amount);
    $sheet->setCellValue('E' . $rowIndex, $total_interest);
    $sheet->setCellValue('F' . $rowIndex, $total_outstanding);

    // Style totals
    $totalStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->applyFromArray($totalStyle);

    // Auto-size columns
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Format numbers
    $sheet->getStyle('C2:C' . ($rowIndex))->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('D2:D' . ($rowIndex))->getNumberFormat()->setFormatCode('0.00"%"');
    $sheet->getStyle('E2:E' . ($rowIndex))->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('F2:F' . ($rowIndex))->getNumberFormat()->setFormatCode('#,##0.00');

    // Output Excel file
    $filename = 'loan_report_' . ($status ? $status . '_' : '') . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

?>

<h2>Loan Report</h2>

<form method="GET" style="margin-bottom: 20px;">
    <div style="display: flex; gap: 10px; align-items: center;">
        <div>
            <label>Status:</label>
            <select name="status">
                <option value="">All</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
            </select>
        </div>
        <div>
            <button type="submit">Filter</button>
            <a href="loan_report.php" style="margin-left: 10px;">Clear Filters</a>
        </div>
    </div>
</form>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <p>Total Loans: <?php echo $total_loans; ?> | Total Amount: KES <?php echo number_format($total_amount, 2); ?> | Total Interest: KES <?php echo number_format($total_interest, 2); ?> | Total Outstanding: KES <?php echo number_format($total_outstanding, 2); ?></p>
    </div>
    <div class="export-buttons">
        <a href="?status=<?php echo urlencode($status); ?>&export=csv" class="btn-export btn-csv">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
        <a href="?status=<?php echo urlencode($status); ?>&export=excel" class="btn-export btn-excel">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <button onclick="window.print()" class="btn-export btn-print">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<?php if (empty($loans)): ?>
    <p>No loans found.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Amount (KES)</th>
                <th>Interest Rate (%)</th>
                <th>Total Interest (KES)</th>
                <th>Outstanding Balance (KES)</th>
                <th>Status</th>
                <th>Requested</th>
                <th>Approved</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($loans as $loan): ?>
            <tr>
                <td><?php echo $loan['id']; ?></td>
                <td><?php echo htmlspecialchars($loan['member_name']); ?></td>
                <td><?php echo number_format($loan['amount'], 2); ?></td>
                <td><?php echo number_format($loan['interest_rate'], 2); ?>%</td>
                <td><?php echo number_format($loan['total_interest'], 2); ?></td>
                <td><?php echo number_format($loan['outstanding_balance'], 2); ?></td>
                <td>
                    <span style="color: <?php
                        echo $loan['status'] === 'approved' ? 'green' :
                             ($loan['status'] === 'paid' ? 'blue' :
                             ($loan['status'] === 'rejected' ? 'red' : 'orange')); ?>">
                        <?php echo ucfirst($loan['status']); ?>
                    </span>
                </td>
                <td><?php echo date('d M Y', strtotime($loan['apply_date'])); ?></td>
                <td><?php echo $loan['approve_date'] ? date('d M Y', strtotime($loan['approve_date'])) : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p><a href="annual_summary.php">View Annual Summary →</a></p>

<style>
.export-buttons {
    display: flex;
    gap: 10px;
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

.btn-print {
    background: linear-gradient(135deg, #6c757d, #5a6268);
    color: white;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-print:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
}
</style>

<script>
// Removed client-side CSV export - now handled server-side
</script>

<style media="print">
    .export-buttons,
    form,
    a[href*="annual_summary.php"] {
        display: none !important;
    }

    body {
        font-size: 12px;
    }

    table {
        font-size: 11px;
    }

    th, td {
        padding: 6px 8px;
    }
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>