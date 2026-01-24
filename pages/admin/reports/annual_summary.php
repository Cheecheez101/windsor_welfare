<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/functions.php';

// Get current year (or allow selection later)
$year = $_GET['year'] ?? date('Y');

// Fetch total savings per member for the selected year
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name AS name, SUM(c.amount) AS total_savings
    FROM users u
    LEFT JOIN contributions c ON u.id = c.member_id
        AND YEAR(c.contribution_date) = ?
    WHERE u.role = 'member'
    GROUP BY u.id, u.full_name
    HAVING total_savings > 0
    ORDER BY total_savings DESC
");
$stmt->execute([$year]);
$summary = $stmt->fetchAll();

$total_all_savings = array_sum(array_column($summary, 'total_savings'));

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="annual_summary_' . $year . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, ['Member', 'Total Savings (KES)', 'New Year Token (20%)']);

    // Data rows
    foreach ($summary as $row) {
        fputcsv($output, [
            $row['name'],
            number_format($row['total_savings'], 2),
            number_format(calculate_token($row['total_savings']), 2)
        ]);
    }

    // Add totals
    fputcsv($output, []); // Empty row
    fputcsv($output, ['Total Savings (all members)', number_format($total_all_savings, 2), '']);
    fputcsv($output, ['Total Tokens to be Distributed', '', number_format(array_sum(array_map(function($r) { return calculate_token($r['total_savings']); }, $summary)), 2)]);

    fclose($output);
    exit;
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set title
    $sheet->setTitle('Annual Summary ' . $year);

    // Headers
    $sheet->setCellValue('A1', 'MEMBER');
    $sheet->setCellValue('B1', 'TOTAL SAVINGS (KES)');
    $sheet->setCellValue('C1', 'NEW YEAR TOKEN (20%)');

    // Style headers
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

    // Data rows
    $rowIndex = 2;
    foreach ($summary as $row) {
        $sheet->setCellValue('A' . $rowIndex, $row['name']);
        $sheet->setCellValue('B' . $rowIndex, $row['total_savings']);
        $sheet->setCellValue('C' . $rowIndex, calculate_token($row['total_savings']));
        $rowIndex++;
    }

    // Add totals
    $rowIndex++; // Empty row
    $sheet->setCellValue('A' . $rowIndex, 'Total Savings (all members)');
    $sheet->setCellValue('B' . $rowIndex, $total_all_savings);
    $rowIndex++;
    $sheet->setCellValue('A' . $rowIndex, 'Total Tokens to be Distributed');
    $sheet->setCellValue('C' . $rowIndex, array_sum(array_map(function($r) { return calculate_token($r['total_savings']); }, $summary)));

    // Style totals
    $totalStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
    ];
    $sheet->getStyle('A' . ($rowIndex-1) . ':C' . $rowIndex)->applyFromArray($totalStyle);

    // Auto-size columns
    foreach (range('A', 'C') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Format numbers
    $sheet->getStyle('B2:B' . ($rowIndex))->getNumberFormat()->setFormatCode('#,##0.00');
    $sheet->getStyle('C2:C' . ($rowIndex))->getNumberFormat()->setFormatCode('#,##0.00');

    // Output Excel file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="annual_summary_' . $year . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f8f9fa;
    margin: 0;
    padding: 0;
    color: #333;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.report-header {
    text-align: center;
    margin-bottom: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.report-header h2 {
    margin: 0;
    font-size: 2.5em;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.filter-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
}

.filter-form {
    display: flex;
    gap: 20px;
    align-items: end;
    flex-wrap: wrap;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.form-group input {
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: white;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-filter {
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

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

.summary-section {
    background: white;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
}

.summary-title {
    font-size: 1.8em;
    color: #495057;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 3px solid #667eea;
    display: inline-block;
}

.total-savings {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.total-savings .amount {
    font-size: 2.5em;
    font-weight: bold;
    display: block;
    margin-top: 10px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    overflow: hidden;
}

.data-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.data-table th {
    padding: 18px 20px;
    text-align: left;
    font-weight: 600;
    font-size: 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 16px 20px;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.3s ease;
}

.data-table tbody tr:hover {
    background-color: #f8f9ff;
}

.data-table tbody tr:nth-child(even) {
    background-color: #fafbff;
}

.member-name {
    font-weight: 600;
    color: #495057;
    font-size: 16px;
}

.savings-amount {
    font-weight: 600;
    color: #28a745;
    font-size: 16px;
}

.token-amount {
    font-weight: bold;
    color: #e74c3c;
    font-size: 16px;
    background: linear-gradient(135deg, #ffeaa7, #fab1a0);
    padding: 8px 12px;
    border-radius: 6px;
    display: inline-block;
    margin-top: 5px;
}

.total-tokens {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    margin-top: 30px;
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
}

.total-tokens .amount {
    font-size: 2em;
    font-weight: bold;
    display: block;
    margin-top: 10px;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #6c757d;
    font-size: 18px;
    background: #f8f9fa;
    border-radius: 10px;
    margin: 20px 0;
}

.navigation-links {
    text-align: center;
    margin-top: 40px;
    padding: 20px;
}

.nav-link {
    display: inline-block;
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    margin: 0 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.nav-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    color: white;
}

@media (max-width: 768px) {
    .container {
        padding: 15px;
    }

    .report-header {
        padding: 20px;
    }

    .report-header h2 {
        font-size: 2em;
    }

    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }

    .data-table {
        font-size: 14px;
    }

    .data-table th,
    .data-table td {
        padding: 12px 10px;
    }

    .total-savings .amount,
    .total-tokens .amount {
        font-size: 1.8em;
    }
}
</style>

<div class="container">
    <div class="report-header">
        <h2>Annual Summary Report</h2>
    </div>

    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="year">Year:</label>
                <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" min="2000" max="2030" required>
            </div>
            <button type="submit" class="btn-filter">Filter</button>
        </form>
    </div>

    <div class="summary-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 class="summary-title">Year <?php echo $year; ?> Summary</h3>
            <div class="export-buttons">
                <a href="?year=<?php echo $year; ?>&export=csv" class="btn-export btn-csv">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
                <a href="?year=<?php echo $year; ?>&export=excel" class="btn-export btn-excel">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <button onclick="window.print()" class="btn-export btn-print">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>

        <div class="total-savings">
            <div>Total Savings (all members)</div>
            <div class="amount">KES <?php echo number_format($total_all_savings, 2); ?></div>
        </div>

        <?php if (empty($summary)): ?>
            <div class="no-data">
                <p>No contributions recorded for <?php echo $year; ?>.</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Total Savings (KES)</th>
                        <th>New Year Token (20%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $row): ?>
                    <tr>
                        <td class="member-name"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td class="savings-amount">KES <?php echo number_format($row['total_savings'], 2); ?></td>
                        <td><span class="token-amount">KES <?php echo number_format(calculate_token($row['total_savings']), 2); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-tokens">
                <div>Total Tokens to be Distributed</div>
                <div class="amount">KES <?php echo number_format(array_sum(array_map(function($r) { return calculate_token($r['total_savings']); }, $summary)), 2); ?></div>
            </div>
        <?php endif; ?>
    </div>

    <div class="navigation-links">
        <a href="contribution_report.php" class="nav-link">View Contribution Report</a>
        <a href="loan_report.php" class="nav-link">View Loan Report</a>
    </div>
</div>

<script>
// Removed client-side CSV export - now handled server-side
</script>

<style media="print">
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background: white !important;
    }

    .container {
        max-width: none;
        margin: 0;
        padding: 10px;
    }

    .export-buttons,
    .filter-section,
    .navigation-links,
    .btn-filter,
    footer,
    nav,
    header {
        display: none !important;
    }

    .report-header {
        background: #333 !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
        padding: 10px;
        margin: -10px -10px 20px -10px;
    }

    .report-header h2 {
        margin: 0;
        font-size: 18px;
    }

    .summary-title {
        font-size: 16px;
        margin-bottom: 10px;
    }

    .total-savings {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
        padding: 10px;
        border: 1px solid #dee2e6;
        margin-bottom: 20px;
        font-weight: bold;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        margin-top: 10px;
    }

    .data-table th,
    .data-table td {
        border: 1px solid #000;
        padding: 6px 8px;
        text-align: left;
    }

    .data-table th {
        background: #333 !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
        font-weight: bold;
    }

    .amount {
        font-size: 14px;
        font-weight: bold;
    }

    @page {
        margin: 0.5in;
        size: A4;
    }
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>