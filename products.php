<?php
require_once "db.php";
$message = "";
$msg_type = "ok";
$edit_record = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['product_name'] ?? '');
        if ($name === '') {
            $message = "Product name is required.";
            $msg_type = "err";
        } else {
            $stmt = $conn->prepare("INSERT INTO product (ProductName) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Product added successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['product_name'] ?? '');
        if ($name === '') {
            $message = "Product name is required.";
            $msg_type = "err";
        } else {
            $stmt = $conn->prepare("UPDATE product SET ProductName=? WHERE ProductID=?");
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Product updated successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM product WHERE ProductID=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->error) {
            $message = "Cannot delete: this product is assigned to an existing project.";
            $msg_type = "err";
        } else {
            $message = "Product deleted.";
        }
        $stmt->close();
    }
}

if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM product WHERE ProductID=?");
    $stmt->bind_param("i", $eid);
    $stmt->execute();
    $edit_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$records = $conn->query("SELECT * FROM product ORDER BY ProductID ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products - Project Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div class="container">
    <h1>Products</h1>
    <?php if ($message): ?>
        <div class="msg <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="section-title"><?php echo $edit_record ? 'Edit Product' : 'Add New Product'; ?></div>
    <form method="POST" class="inline">
        <input type="hidden" name="action" value="<?php echo $edit_record ? 'edit' : 'add'; ?>">
        <?php if ($edit_record): ?>
            <input type="hidden" name="id" value="<?php echo $edit_record['ProductID']; ?>">
        <?php endif; ?>
        <div>
            <label>Product Name *</label>
            <input type="text" name="product_name" value="<?php echo htmlspecialchars($edit_record['ProductName'] ?? ''); ?>" required>
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit"><?php echo $edit_record ? 'Update' : 'Add'; ?></button>
        </div>
        <?php if ($edit_record): ?>
            <div><label>&nbsp;</label><a href="products.php" class="cancel">Cancel</a></div>
        <?php endif; ?>
    </form>

    <div class="section-title">Product Records</div>
    <?php if ($records->num_rows === 0): ?>
        <p class="note">No products found.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>ID</th><th>Product Name</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $records->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['ProductID']; ?></td>
                <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                <td>
                    <a href="productmaterials.php?product_id=<?php echo $row['ProductID']; ?>" class="btn btn-sm">Materials</a>
                    &nbsp;
                    <a href="products.php?edit=<?php echo $row['ProductID']; ?>" class="btn btn-edit">Edit</a>
                    &nbsp;
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $row['ProductID']; ?>">
                        <button type="submit" class="btn btn-del" onclick="return confirm('Delete this product?')">Delete</button>
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
