<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();

// Get filter parameters
$start_date = $_GET['start'] ?? '2020-01-01';
$end_date = $_GET['end'] ?? date('Y-m-d');
$member_search = $_GET['member_search'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';

$query = "
    SELECT c.id, c.member_id, u.full_name AS member_name, c.amount, c.contribution_date, c.payment_method
    FROM contributions c
    JOIN users u ON c.member_id = u.id
    WHERE c.contribution_date BETWEEN ? AND ?
";
$params = [$start_date, $end_date];

if ($member_search) {
    $query .= " AND u.full_name LIKE ?";
    $params[] = "%$member_search%";
}

if ($payment_method) {
    $query .= " AND c.payment_method = ?";
    $params[] = $payment_method;
}

$query .= " ORDER BY c.contribution_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$contributions = $stmt->fetchAll();

$total = array_sum(array_map('floatval', array_column($contributions, 'amount')));

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="contribution_report_' . $start_date . '_to_' . $end_date . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, ['ID', 'Member', 'Amount (KES)', 'Payment Method', 'Date']);

    // Data rows
    foreach ($contributions as $c) {
        fputcsv($output, [
            $c['id'],
            $c['member_name'],
            number_format($c['amount'], 2),
            ucfirst($c['payment_method']),
            date('d M Y', strtotime($c['contribution_date']))
        ]);
    }

    // Add totals
    fputcsv($output, []); // Empty row
    fputcsv($output, ['Total Contributions', '', number_format($total, 2), '', '']);

    fclose($output);
    exit;
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set title
    $sheet->setTitle('Contribution Report');

    // Headers
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'MEMBER');
    $sheet->setCellValue('C1', 'AMOUNT (KES)');
    $sheet->setCellValue('D1', 'PAYMENT METHOD');
    $sheet->setCellValue('E1', 'DATE');

    // Style headers
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

    // Data rows
    $rowIndex = 2;
    foreach ($contributions as $c) {
        $sheet->setCellValue('A' . $rowIndex, $c['id']);
        $sheet->setCellValue('B' . $rowIndex, $c['member_name']);
        $sheet->setCellValue('C' . $rowIndex, $c['amount']);
        $sheet->setCellValue('D' . $rowIndex, ucfirst($c['payment_method']));
        $sheet->setCellValue('E' . $rowIndex, date('d M Y', strtotime($c['contribution_date'])));
        $rowIndex++;
    }

    // Add totals
    $rowIndex++; // Empty row
    $sheet->setCellValue('B' . $rowIndex, 'Total Contributions');
    $sheet->setCellValue('C' . $rowIndex, $total);

    // Style totals
    $totalStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A' . $rowIndex . ':E' . $rowIndex)->applyFromArray($totalStyle);

    // Auto-size columns
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Format numbers
    $sheet->getStyle('C2:C' . ($rowIndex))->getNumberFormat()->setFormatCode('#,##0.00');

    // Output Excel file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="contribution_report_' . $start_date . '_to_' . $end_date . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

?>

<h2>Contribution Report</h2>

<form method="GET" style="margin-bottom: 20px;">
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <div>
            <label>Start Date:</label>
            <input type="date" name="start" value="<?php echo htmlspecialchars($start_date); ?>" required>
        </div>
        <div>
            <label>End Date:</label>
            <input type="date" name="end" value="<?php echo htmlspecialchars($end_date); ?>" required>
        </div>
        <div>
            <label>Member Name:</label>
            <input type="text" name="member_search" value="<?php echo htmlspecialchars($member_search); ?>" placeholder="Search member name">
        </div>
        <div>
            <label>Payment Method:</label>
            <select name="payment_method">
                <option value="">All</option>
                <option value="cash" <?php echo $payment_method === 'cash' ? 'selected' : ''; ?>>Cash</option>
                <option value="card" <?php echo $payment_method === 'card' ? 'selected' : ''; ?>>Card</option>
                <option value="mpesa" <?php echo $payment_method === 'mpesa' ? 'selected' : ''; ?>>M-Pesa</option>
            </select>
        </div>
        <div>
            <button type="submit">Filter</button>
            <a href="contribution_report.php" style="margin-left: 10px;">Clear Filters</a>
        </div>
    </div>
</form>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <p>Period: <?php echo date('d M Y', strtotime($start_date)); ?> – <?php echo date('d M Y', strtotime($end_date)); ?></p>
        <p><strong>Total Contributions:</strong> KES <?php echo number_format($total, 2); ?></p>
    </div>
    <div class="export-buttons">
        <a href="?start=<?php echo urlencode($start_date); ?>&end=<?php echo urlencode($end_date); ?>&member_search=<?php echo urlencode($member_search); ?>&payment_method=<?php echo urlencode($payment_method); ?>&export=csv" class="btn-export btn-csv">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
        <a href="?start=<?php echo urlencode($start_date); ?>&end=<?php echo urlencode($end_date); ?>&member_search=<?php echo urlencode($member_search); ?>&payment_method=<?php echo urlencode($payment_method); ?>&export=excel" class="btn-export btn-excel">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <button onclick="window.print()" class="btn-export btn-print">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<?php if (empty($contributions)): ?>
    <p>No contributions found in this period.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Amount (KES)</th>
                <th>Payment Method</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contributions as $c): ?>
            <tr>
                <td><?php echo $c['id']; ?></td>
                <td><?php echo htmlspecialchars($c['member_name']); ?></td>
                <td><?php echo number_format($c['amount'], 2); ?></td>
                <td><?php echo ucfirst($c['payment_method']); ?></td>
                <td><?php echo date('d M Y', strtotime($c['contribution_date'])); ?></td>
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