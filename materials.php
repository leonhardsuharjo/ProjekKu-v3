<?php
require_once "db.php";
$message = "";
$msg_type = "ok";
$edit_record = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['material_name'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $sid = (int)($_POST['supplier_id'] ?? 0);
        if ($name === '') {
            $message = "Material name is required.";
            $msg_type = "err";
        } elseif ($sid <= 0) {
            $message = "Please select a supplier.";
            $msg_type = "err";
        } elseif (!is_numeric($price) || (float)$price < 0) {
            $message = "Price must be a valid non-negative number.";
            $msg_type = "err";
        } else {
            $price_f = (float)$price;
            $stmt = $conn->prepare("INSERT INTO material (MaterialName, PricePerUnit, SupplierID) VALUES (?, ?, ?)");
            $stmt->bind_param("sdi", $name, $price_f, $sid);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Material added successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['material_name'] ?? '');
        $price = trim($_POST['price'] ?? '');
        $sid = (int)($_POST['supplier_id'] ?? 0);
        if ($name === '') {
            $message = "Material name is required.";
            $msg_type = "err";
        } elseif ($sid <= 0) {
            $message = "Please select a supplier.";
            $msg_type = "err";
        } elseif (!is_numeric($price) || (float)$price < 0) {
            $message = "Price must be a valid non-negative number.";
            $msg_type = "err";
        } else {
            $price_f = (float)$price;
            $stmt = $conn->prepare("UPDATE material SET MaterialName=?, PricePerUnit=?, SupplierID=? WHERE MaterialID=?");
            $stmt->bind_param("sdii", $name, $price_f, $sid, $id);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Material updated successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM material WHERE MaterialID=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->error) {
            $message = "Cannot delete: this material is linked to a product.";
            $msg_type = "err";
        } else {
            $message = "Material deleted.";
        }
        $stmt->close();
    }
}

if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM material WHERE MaterialID=?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $edit_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$records = $conn->query("SELECT m.*, s.SupplierName FROM material m JOIN supplier s ON m.SupplierID = s.SupplierID ORDER BY m.MaterialID ASC");
$suppliers = $conn->query("SELECT * FROM supplier ORDER BY SupplierName ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Materials - Project Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div class="container">
    <h1>Materials</h1>
    <?php if ($message): ?>
        <div class="msg <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="section-title"><?php echo $edit_record ? 'Edit Material' : 'Add New Material'; ?></div>
    <?php if ($suppliers->num_rows === 0): ?>
        <p class="msg err">No suppliers found. Please add a supplier first.</p>
    <?php else: ?>
    <form method="POST" class="inline">
        <input type="hidden" name="action" value="<?php echo $edit_record ? 'edit' : 'add'; ?>">
        <?php if ($edit_record): ?>
            <input type="hidden" name="id" value="<?php echo $edit_record['MaterialID']; ?>">
        <?php endif; ?>
        <div>
            <label>Material Name *</label>
            <input type="text" name="material_name" value="<?php echo htmlspecialchars($edit_record['MaterialName'] ?? ''); ?>" required>
        </div>
        <div>
            <label>Price Per Unit *</label>
            <input type="number" step="0.01" min="0" name="price" value="<?php echo htmlspecialchars($edit_record['PricePerUnit'] ?? ''); ?>" required>
        </div>
        <div>
            <label>Supplier *</label>
            <select name="supplier_id" required>
                <option value="">-- Select Supplier --</option>
                <?php
                $suppliers->data_seek(0);
                while ($s = $suppliers->fetch_assoc()):
                    $sel = ($edit_record && $edit_record['SupplierID'] == $s['SupplierID']) ? 'selected' : '';
                ?>
                    <option value="<?php echo $s['SupplierID']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($s['SupplierName']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit"><?php echo $edit_record ? 'Update' : 'Add'; ?></button>
        </div>
        <?php if ($edit_record): ?>
            <div><label>&nbsp;</label><a href="materials.php" class="cancel">Cancel</a></div>
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <div class="section-title">Material Records</div>
    <?php if (!$records || $records->num_rows === 0): ?>
        <p class="note">No materials found.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>ID</th><th>Material Name</th><th>Price / Unit</th><th>Supplier</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $records->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['MaterialID']; ?></td>
                <td><?php echo htmlspecialchars($row['MaterialName']); ?></td>
                <td><?php echo number_format($row['PricePerUnit'], 2); ?></td>
                <td><?php echo htmlspecialchars($row['SupplierName']); ?></td>
                <td>
                    <a href="materials.php?edit=<?php echo $row['MaterialID']; ?>" class="btn btn-edit">Edit</a>
                    &nbsp;
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $row['MaterialID']; ?>">
                        <button type="submit" class="btn btn-del" onclick="return confirm('Delete this material?')">Delete</button>
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
