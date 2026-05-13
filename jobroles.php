<?php
require_once "db.php";
$message = "";
$msg_type = "ok";
$edit_record = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $type = trim($_POST['job_type'] ?? '');
        $wage = trim($_POST['wage'] ?? '');
        if ($type === '') {
            $message = "Job type is required.";
            $msg_type = "err";
        } elseif (!is_numeric($wage) || (float)$wage < 0) {
            $message = "Wage must be a valid non-negative number.";
            $msg_type = "err";
        } else {
            $wage_f = (float)$wage;
            $stmt = $conn->prepare("INSERT INTO jobrole (JobType, WagePerDay) VALUES (?, ?)");
            $stmt->bind_param("sd", $type, $wage_f);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Job role added successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $type = trim($_POST['job_type'] ?? '');
        $wage = trim($_POST['wage'] ?? '');
        if ($type === '') {
            $message = "Job type is required.";
            $msg_type = "err";
        } elseif (!is_numeric($wage) || (float)$wage < 0) {
            $message = "Wage must be a valid non-negative number.";
            $msg_type = "err";
        } else {
            $wage_f = (float)$wage;
            $stmt = $conn->prepare("UPDATE jobrole SET JobType=?, WagePerDay=? WHERE JobRoleID=?");
            $stmt->bind_param("sdi", $type, $wage_f, $id);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Job role updated successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM jobrole WHERE JobRoleID=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->error) {
            $message = "Cannot delete: this job role is assigned to an existing project.";
            $msg_type = "err";
        } else {
            $message = "Job role deleted.";
        }
        $stmt->close();
    }
}

if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM jobrole WHERE JobRoleID=?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $edit_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$records = $conn->query("SELECT * FROM jobrole ORDER BY JobRoleID ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Job Roles - Project Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div class="container">
    <h1>Job Roles</h1>
    <?php if ($message): ?>
        <div class="msg <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="section-title"><?php echo $edit_record ? 'Edit Job Role' : 'Add New Job Role'; ?></div>
    <form method="POST" class="inline">
        <input type="hidden" name="action" value="<?php echo $edit_record ? 'edit' : 'add'; ?>">
        <?php if ($edit_record): ?>
            <input type="hidden" name="id" value="<?php echo $edit_record['JobRoleID']; ?>">
        <?php endif; ?>
        <div>
            <label>Job Type *</label>
            <input type="text" name="job_type" value="<?php echo htmlspecialchars($edit_record['JobType'] ?? ''); ?>" required>
        </div>
        <div>
            <label>Wage Per Day *</label>
            <input type="number" step="0.01" min="0" name="wage" value="<?php echo htmlspecialchars($edit_record['WagePerDay'] ?? ''); ?>" required>
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit"><?php echo $edit_record ? 'Update' : 'Add'; ?></button>
        </div>
        <?php if ($edit_record): ?>
            <div><label>&nbsp;</label><a href="jobroles.php" class="cancel">Cancel</a></div>
        <?php endif; ?>
    </form>

    <div class="section-title">Job Role Records</div>
    <?php if ($records->num_rows === 0): ?>
        <p class="note">No job roles found.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>ID</th><th>Job Type</th><th>Wage / Day</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $records->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['JobRoleID']; ?></td>
                <td><?php echo htmlspecialchars($row['JobType']); ?></td>
                <td><?php echo number_format($row['WagePerDay'], 2); ?></td>
                <td>
                    <a href="jobroles.php?edit=<?php echo $row['JobRoleID']; ?>" class="btn btn-edit">Edit</a>
                    &nbsp;
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $row['JobRoleID']; ?>">
                        <button type="submit" class="btn btn-del" onclick="return confirm('Delete this job role?')">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
