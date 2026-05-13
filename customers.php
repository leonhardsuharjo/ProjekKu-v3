<?php
require_once "db.php";
$message = "";
$msg_type = "ok";
$edit_record = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['customer_name'] ?? '');
        $addr = trim($_POST['address'] ?? '');
        $phone = trim($_POST['contact'] ?? '');
        if ($name === '') {
            $message = "Customer name is required.";
            $msg_type = "err";
        } else {
            $stmt = $conn->prepare("INSERT INTO customer (CustomerName, Address, ContactNumber) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $addr, $phone);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Customer added successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['customer_name'] ?? '');
        $addr = trim($_POST['address'] ?? '');
        $phone = trim($_POST['contact'] ?? '');
        if ($name === '') {
            $message = "Customer name is required.";
            $msg_type = "err";
        } else {
            $stmt = $conn->prepare("UPDATE customer SET CustomerName=?, Address=?, ContactNumber=? WHERE CustomerID=?");
            $stmt->bind_param("sssi", $name, $addr, $phone, $id);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Customer updated successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM customer WHERE CustomerID=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->error) {
            $message = "Cannot delete: this customer is linked to existing projects.";
            $msg_type = "err";
        } else {
            $message = "Customer deleted.";
        }
        $stmt->close();
    }
}

if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM customer WHERE CustomerID=?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $edit_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$records = $conn->query("SELECT * FROM customer ORDER BY CustomerID ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers - Project Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div class="container">
    <h1>Customers</h1>
    <?php if ($message): ?>
        <div class="msg <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="section-title"><?php echo $edit_record ? 'Edit Customer' : 'Add New Customer'; ?></div>
    <form method="POST" class="inline">
        <input type="hidden" name="action" value="<?php echo $edit_record ? 'edit' : 'add'; ?>">
        <?php if ($edit_record): ?>
            <input type="hidden" name="id" value="<?php echo $edit_record['CustomerID']; ?>">
        <?php endif; ?>
        <div>
            <label>Customer Name *</label>
            <input type="text" name="customer_name" value="<?php echo htmlspecialchars($edit_record['CustomerName'] ?? ''); ?>" required>
        </div>
        <div>
            <label>Address</label>
            <input type="text" name="address" value="<?php echo htmlspecialchars($edit_record['Address'] ?? ''); ?>">
        </div>
        <div>
            <label>Contact Number</label>
            <input type="text" name="contact" value="<?php echo htmlspecialchars($edit_record['ContactNumber'] ?? ''); ?>">
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit"><?php echo $edit_record ? 'Update' : 'Add'; ?></button>
        </div>
        <?php if ($edit_record): ?>
            <div>
                <label>&nbsp;</label>
                <a href="customers.php" class="cancel">Cancel</a>
            </div>
        <?php endif; ?>
    </form>

    <div class="section-title">Customer Records</div>
    <?php if ($records->num_rows === 0): ?>
        <p class="note">No customers found.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Address</th><th>Contact</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $records->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['CustomerID']; ?></td>
                <td><?php echo htmlspecialchars($row['CustomerName']); ?></td>
                <td><?php echo htmlspecialchars($row['Address']); ?></td>
                <td><?php echo htmlspecialchars($row['ContactNumber']); ?></td>
                <td>
                    <a href="customers.php?edit=<?php echo $row['CustomerID']; ?>" class="btn btn-edit">Edit</a>
                    &nbsp;
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $row['CustomerID']; ?>">
                        <button type="submit" class="btn btn-del" onclick="return confirm('Delete this customer?')">Delete</button>
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
