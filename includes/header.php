<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Windsor Tea Factory Staff Welfare</title>
    <link rel="stylesheet" href="/windsor_welfare/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <h1>Windsor Tea Factory Staff Welfare</h1>
        <nav>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="/windsor_welfare/pages/admin/dashboard.php">Dashboard</a> |
                <a href="/windsor_welfare/pages/admin/members/list_members.php">Users</a> |
                <a href="/windsor_welfare/pages/admin/contributions/list_contributions.php">Contributions</a> |
                <a href="/windsor_welfare/pages/admin/loans/list_loans.php">Loans</a> |
                <a href="/windsor_welfare/pages/admin/reports/annual_summary.php">Reports</a> |
                <a href="/windsor_welfare/logout.php">Logout</a>
            <?php else: ?>
                <a href="/windsor_welfare/pages/member/dashboard.php">Dashboard</a> |
                <a href="/windsor_welfare/pages/member/profile.php">Profile</a> |
                <a href="/windsor_welfare/pages/member/apply_loan.php">Apply Loan</a> |
                <a href="/windsor_welfare/logout.php">Logout</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container">