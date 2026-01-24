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
<h2>Users Management</h2>

<a href="add_member.php" class="btn">Add New Member</a>

<form method="GET" style="margin-bottom: 20px;">
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <div>
            <label>Search (Name/ID):</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter name or employee ID">
        </div>
        <div>
            <label>Status:</label>
            <select name="status">
                <option value="">All</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div>
            <label>Join Date From:</label>
            <input type="date" name="join_date_from" value="<?php echo htmlspecialchars($join_date_from); ?>">
        </div>
        <div>
            <label>Join Date To:</label>
            <input type="date" name="join_date_to" value="<?php echo htmlspecialchars($join_date_to); ?>">
        </div>
        <div>
            <button type="submit">Filter</button>
            <a href="list_members.php" style="margin-left: 10px;">Clear Filters</a>
        </div>
    </div>
</form>

<table>
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
    <?php foreach ($members as $m): ?>
    <tr>
        <td><?php echo $m['id']; ?></td>
        <td><?php echo htmlspecialchars($m['employee_id'] ?? '-'); ?></td>
        <td><?php echo htmlspecialchars($m['full_name']); ?></td>
        <td><?php echo htmlspecialchars($m['phone'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($m['email'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($m['department'] ?? ''); ?></td>
        <td>
            <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; text-transform: uppercase;
                <?php echo $m['role'] === 'admin' ? 'background: #e3f2fd; color: #1976d2;' : 'background: #e8f5e8; color: #2e7d32;'; ?>">
                <?php echo $m['role'] === 'admin' ? 'Admin' : 'Staff'; ?>
            </span>
        </td>
        <td><?php echo ucfirst($m['status'] ?? 'active'); ?></td>
        <td><?php echo $m['join_date'] ? date('d M Y', strtotime($m['join_date'])) : '-'; ?></td>
        <td>
            <a href="edit_member.php?id=<?php echo $m['id']; ?>" style="color: #007bff; text-decoration: none;">Edit</a> |
            <a href="delete_member.php?id=<?php echo $m['id']; ?>" onclick="return confirm('Delete user?')" style="color: #dc3545; text-decoration: none;">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>