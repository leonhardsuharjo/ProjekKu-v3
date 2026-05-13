<?php
require_once "db.php";
$message = "";
$msg_type = "ok";
$edit_record = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['project_name'] ?? '');
        $date = trim($_POST['project_date'] ?? '');
        $transport = trim($_POST['transport_cost'] ?? '0');
        $value = trim($_POST['project_value'] ?? '');
        $cid = (int)($_POST['customer_id'] ?? 0);
        if ($name === '') {
            $message = "Project name is required.";
            $msg_type = "err";
        } elseif ($date === '') {
            $message = "Project date is required.";
            $msg_type = "err";
        } elseif ($cid <= 0) {
            $message = "Please select a customer.";
            $msg_type = "err";
        } elseif (!is_numeric($value) || (float)$value < 0) {
            $message = "Project value must be a valid number.";
            $msg_type = "err";
        } else {
            $value_f = (float)$value;
            $transport_f = is_numeric($transport) ? (float)$transport : 0;
            $stmt = $conn->prepare("INSERT INTO project (ProjectName, ProjectDate, TransportCost, ProjectValue, CustomerID) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddi", $name, $date, $transport_f, $value_f, $cid);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Project added successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['project_name'] ?? '');
        $date = trim($_POST['project_date'] ?? '');
        $transport = trim($_POST['transport_cost'] ?? '0');
        $value = trim($_POST['project_value'] ?? '');
        $cid = (int)($_POST['customer_id'] ?? 0);
        if ($name === '') {
            $message = "Project name is required.";
            $msg_type = "err";
        } elseif ($date === '') {
            $message = "Project date is required.";
            $msg_type = "err";
        } elseif ($cid <= 0) {
            $message = "Please select a customer.";
            $msg_type = "err";
        } elseif (!is_numeric($value) || (float)$value < 0) {
            $message = "Project value must be a valid number.";
            $msg_type = "err";
        } else {
            $value_f = (float)$value;
            $transport_f = is_numeric($transport) ? (float)$transport : 0;
            $stmt = $conn->prepare("UPDATE project SET ProjectName=?, ProjectDate=?, TransportCost=?, ProjectValue=?, CustomerID=? WHERE ProjectID=?");
            $stmt->bind_param("ssddii", $name, $date, $transport_f, $value_f, $cid, $id);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Project updated successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM project WHERE ProjectID=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->error) {
            $message = "Cannot delete this project: " . $stmt->error;
            $msg_type = "err";
        } else {
            $message = "Project deleted.";
        }
        $stmt->close();
    }
}

if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM project WHERE ProjectID=?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $edit_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$records = $conn->query("SELECT p.*, c.CustomerName FROM project p JOIN customer c ON p.CustomerID = c.CustomerID ORDER BY p.ProjectDate DESC");
$customers = $conn->query("SELECT * FROM customer ORDER BY CustomerName ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Projects - Project Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div class="container">
    <h1>Projects</h1>
    <?php if ($message): ?>
        <div class="msg <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="section-title"><?php echo $edit_record ? 'Edit Project' : 'Add New Project'; ?></div>
    <?php if ($customers->num_rows === 0): ?>
        <p class="msg err">No customers found. Please add a customer first.</p>
    <?php else: ?>
    <form method="POST" class="inline">
        <input type="hidden" name="action" value="<?php echo $edit_record ? 'edit' : 'add'; ?>">
        <?php if ($edit_record): ?>
            <input type="hidden" name="id" value="<?php echo $edit_record['ProjectID']; ?>">
        <?php endif; ?>
        <div>
            <label>Project Name *</label>
            <input type="text" name="project_name" value="<?php echo htmlspecialchars($edit_record['ProjectName'] ?? ''); ?>" required>
        </div>
        <div>
            <label>Date *</label>
            <input type="date" name="project_date" value="<?php echo htmlspecialchars($edit_record['ProjectDate'] ?? ''); ?>" required>
        </div>
        <div>
            <label>Customer *</label>
            <select name="customer_id" required>
                <option value="">-- Select Customer --</option>
                <?php
                $customers->data_seek(0);
                while ($c = $customers->fetch_assoc()):
                    $sel = ($edit_record && $edit_record['CustomerID'] == $c['CustomerID']) ? 'selected' : '';
                ?>
                    <option value="<?php echo $c['CustomerID']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($c['CustomerName']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label>Project Value *</label>
            <input type="number" step="0.01" min="0" name="project_value" value="<?php echo htmlspecialchars($edit_record['ProjectValue'] ?? ''); ?>" required>
        </div>
        <div>
            <label>Transport Cost</label>
            <input type="number" step="0.01" min="0" name="transport_cost" value="<?php echo htmlspecialchars($edit_record['TransportCost'] ?? '0'); ?>">
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit"><?php echo $edit_record ? 'Update' : 'Add'; ?></button>
        </div>
        <?php if ($edit_record): ?>
            <div><label>&nbsp;</label><a href="projects.php" class="cancel">Cancel</a></div>
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <div class="section-title">Project Records</div>
    <?php if (!$records || $records->num_rows === 0): ?>
        <p class="note">No projects found.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>ID</th><th>Project Name</th><th>Date</th><th>Customer</th><th>Value</th><th>Transport</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $records->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['ProjectID']; ?></td>
                <td><?php echo htmlspecialchars($row['ProjectName']); ?></td>
                <td><?php echo $row['ProjectDate']; ?></td>
                <td><?php echo htmlspecialchars($row['CustomerName']); ?></td>
                <td><?php echo number_format($row['ProjectValue'], 2); ?></td>
                <td><?php echo number_format($row['TransportCost'], 2); ?></td>
                <td>
                    <a href="project_products.php?project_id=<?php echo $row['ProjectID']; ?>" class="btn btn-sm">Products</a>
                    <a href="project_labour.php?project_id=<?php echo $row['ProjectID']; ?>" class="btn btn-sm" style="margin-left:4px;">Labour</a>
                    <a href="projects.php?edit=<?php echo $row['ProjectID']; ?>" class="btn btn-edit" style="margin-left:4px;">Edit</a>
                    <form method="POST" style="display:inline;margin-left:4px;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $row['ProjectID']; ?>">
                        <button type="submit" class="btn btn-del" onclick="return confirm('Delete this project and all its linked products and labour?')">Delete</button>
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
