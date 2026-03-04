<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$join_date_from = $_GET['join_date_from'] ?? '';
$join_date_to = $_GET['join_date_to'] ?? '';
$message = $_GET['msg'] ?? '';
$message_type = $_GET['type'] ?? 'success';

// Build query
$query = "SELECT id, employee_id, full_name, phone, email, department, role, status, join_date FROM users WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (full_name LIKE ? OR employee_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
}

if ($join_date_from) {
    $query .= " AND join_date >= ?";
    $params[] = $join_date_from;
}

if ($join_date_to) {
    $query .= " AND join_date <= ?";
    $params[] = $join_date_to;
}

$query .= " ORDER BY full_name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();
?>
<style>
    .members-page {
        margin-top: 12px;
    }
    .members-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .members-header h2 {
        margin: 0;
        color: #2c3e50;
    }
    .members-card {
        background: #ffffff;
        border: 1px solid #e3e7eb;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 16px;
        box-shadow: 0 2px 8px rgba(44, 62, 80, 0.06);
    }
    .members-alert {
        padding: 10px 12px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-weight: 600;
    }
    .members-alert.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .members-alert.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .members-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        align-items: end;
    }
    .members-field label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 6px;
        color: #34495e;
    }
    .members-field input,
    .members-field select {
        margin-bottom: 0;
    }
    .members-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .btn-secondary {
        display: inline-block;
        background: #ecf0f1;
        color: #2c3e50;
        padding: 8px 14px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
    }
    .members-table-wrap {
        overflow-x: auto;
    }
    .members-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }
    .members-table th,
    .members-table td {
        border: 1px solid #e6eaee;
        padding: 10px;
        vertical-align: middle;
        white-space: nowrap;
    }
    .members-table th {
        background: #34495e;
        color: #fff;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.2px;
    }
    .members-table tbody tr:nth-child(even) {
        background: #f9fbfc;
    }
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .badge-role-admin {
        background: #e3f2fd;
        color: #1976d2;
    }
    .badge-role-staff {
        background: #e8f5e8;
        color: #2e7d32;
    }
    .badge-status-active {
        background: #e8f5e8;
        color: #2e7d32;
    }
    .badge-status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    .link-action {
        text-decoration: none;
        font-weight: 600;
        margin-right: 10px;
    }
    .link-edit {
        color: #007bff;
    }
    .link-delete {
        color: #dc3545;
    }
    .members-count {
        margin: 0 0 12px;
        color: #34495e;
        font-weight: 600;
    }
</style>

<div class="members-page">
    <div class="members-header">
        <h2>Users Management</h2>
        <a href="add_member.php" class="btn">Add New Member</a>
    </div>

    <?php if ($message): ?>
        <p class="members-alert <?php echo $message_type === 'error' ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <div class="members-card">
        <form method="GET">
            <div class="members-grid">
                <div class="members-field">
                    <label for="search">Search (Name/ID)</label>
                    <input id="search" type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter name or employee ID">
                </div>
                <div class="members-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="members-field">
                    <label for="join_date_from">Join Date From</label>
                    <input id="join_date_from" type="date" name="join_date_from" value="<?php echo htmlspecialchars($join_date_from); ?>">
                </div>
                <div class="members-field">
                    <label for="join_date_to">Join Date To</label>
                    <input id="join_date_to" type="date" name="join_date_to" value="<?php echo htmlspecialchars($join_date_to); ?>">
                </div>
                <div class="members-actions">
                    <button type="submit">Apply Filters</button>
                    <a href="list_members.php" class="btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <div class="members-card">
        <p class="members-count">Total Members: <?php echo count($members); ?></p>
        <div class="members-table-wrap">
            <table class="members-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Join Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$members): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; color: #7f8c8d;">No members found for the selected filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td><?php echo $m['id']; ?></td>
                                <td><?php echo htmlspecialchars($m['employee_id'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($m['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($m['phone'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($m['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($m['department'] ?? ''); ?></td>
                                <td>
                                    <span class="badge <?php echo $m['role'] === 'admin' ? 'badge-role-admin' : 'badge-role-staff'; ?>">
                                        <?php echo $m['role'] === 'admin' ? 'Admin' : 'Staff'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo ($m['status'] ?? 'active') === 'inactive' ? 'badge-status-inactive' : 'badge-status-active'; ?>">
                                        <?php echo ucfirst($m['status'] ?? 'active'); ?>
                                    </span>
                                </td>
                                <td><?php echo $m['join_date'] ? date('d M Y', strtotime($m['join_date'])) : '-'; ?></td>
                                <td>
                                    <a href="edit_member.php?id=<?php echo $m['id']; ?>" class="link-action link-edit">Edit</a>
                                    <a href="delete_member.php?id=<?php echo $m['id']; ?>" class="link-action link-delete" onclick="return confirm('Delete user?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>