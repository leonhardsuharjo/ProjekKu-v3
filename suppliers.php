<?php
require_once "db.php";
$message = "";
$msg_type = "ok";
$edit_record = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['supplier_name'] ?? '');
        $addr = trim($_POST['address'] ?? '');
        $phone = trim($_POST['contact'] ?? '');
        if ($name === '') {
            $message = "Supplier name is required.";
            $msg_type = "err";
        } else {
            $stmt = $conn->prepare("INSERT INTO supplier (SupplierName, Address, ContactNo) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $addr, $phone);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Supplier added successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['supplier_name'] ?? '');
        $addr = trim($_POST['address'] ?? '');
        $phone = trim($_POST['contact'] ?? '');
        if ($name === '') {
            $message = "Supplier name is required.";
            $msg_type = "err";
        } else {
            $stmt = $conn->prepare("UPDATE supplier SET SupplierName=?, Address=?, ContactNo=? WHERE SupplierID=?");
            $stmt->bind_param("sssi", $name, $addr, $phone, $id);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Supplier updated successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM supplier WHERE SupplierID=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->error) {
            $message = "Cannot delete: this supplier is linked to existing materials.";
            $msg_type = "err";
        } else {
            $message = "Supplier deleted.";
        }
        $stmt->close();
    }
}

if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM supplier WHERE SupplierID=?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $edit_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$records = $conn->query("SELECT * FROM supplier ORDER BY SupplierID ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Suppliers - Project Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div class="container">
    <h1>Suppliers</h1>
    <?php if ($message): ?>
        <div class="msg <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="section-title"><?php echo $edit_record ? 'Edit Supplier' : 'Add New Supplier'; ?></div>
    <form method="POST" class="inline">
        <input type="hidden" name="action" value="<?php echo $edit_record ? 'edit' : 'add'; ?>">
        <?php if ($edit_record): ?>
            <input type="hidden" name="id" value="<?php echo $edit_record['SupplierID']; ?>">
        <?php endif; ?>
        <div>
            <label>Supplier Name *</label>
            <input type="text" name="supplier_name" value="<?php echo htmlspecialchars($edit_record['SupplierName'] ?? ''); ?>" required>
        </div>
        <div>
            <label>Address</label>
            <input type="text" name="address" value="<?php echo htmlspecialchars($edit_record['Address'] ?? ''); ?>">
        </div>
        <div>
            <label>Contact No</label>
            <input type="text" name="contact" value="<?php echo htmlspecialchars($edit_record['ContactNo'] ?? ''); ?>">
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit"><?php echo $edit_record ? 'Update' : 'Add'; ?></button>
        </div>
        <?php if ($edit_record): ?>
            <div><label>&nbsp;</label><a href="suppliers.php" class="cancel">Cancel</a></div>
        <?php endif; ?>
    </form>

    <div class="section-title">Supplier Records</div>
    <?php if ($records->num_rows === 0): ?>
        <p class="note">No suppliers found.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>ID</th><th>Name</th><th>Address</th><th>Contact</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $records->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['SupplierID']; ?></td>
                <td><?php echo htmlspecialchars($row['SupplierName']); ?></td>
                <td><?php echo htmlspecialchars($row['Address']); ?></td>
                <td><?php echo htmlspecialchars($row['ContactNo']); ?></td>
                <td>
                    <a href="suppliers.php?edit=<?php echo $row['SupplierID']; ?>" class="btn btn-edit">Edit</a>
                    &nbsp;
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $row['SupplierID']; ?>">
                        <button type="submit" class="btn btn-del" onclick="return confirm('Delete this supplier?')">Delete</button>
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
