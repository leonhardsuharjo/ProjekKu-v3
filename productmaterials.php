<?php
require_once "db.php";
$message = "";
$msg_type = "ok";

$product_id = (int)($_GET['product_id'] ?? 0);
if ($product_id <= 0) {
    header("Location: products.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM product WHERE ProductID=?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: products.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $mid = (int)($_POST['material_id'] ?? 0);
        $qty = trim($_POST['quantity'] ?? '');
        if ($mid <= 0) {
            $message = "Please select a material.";
            $msg_type = "err";
        } elseif (!is_numeric($qty) || (float)$qty <= 0) {
            $message = "Quantity must be a positive number.";
            $msg_type = "err";
        } else {
            $qty_f = (float)$qty;
            $stmt = $conn->prepare("INSERT INTO productmaterial (ProductID, MaterialID, QuantityNeeded) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $product_id, $mid, $qty_f);
            $stmt->execute();
            if ($stmt->error) {
                $message = "This material is already linked to this product.";
                $msg_type = "err";
            } else {
                $message = "Material linked successfully.";
            }
            $stmt->close();
        }
    } elseif ($action === 'update_qty') {
        $mid = (int)($_POST['material_id'] ?? 0);
        $qty = trim($_POST['quantity'] ?? '');
        if (!is_numeric($qty) || (float)$qty <= 0) {
            $message = "Quantity must be a positive number.";
            $msg_type = "err";
        } else {
            $qty_f = (float)$qty;
            $stmt = $conn->prepare("UPDATE productmaterial SET QuantityNeeded=? WHERE ProductID=? AND MaterialID=?");
            $stmt->bind_param("dii", $qty_f, $product_id, $mid);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Quantity updated.";
            }
            $stmt->close();
        }
    } elseif ($action === 'remove') {
        $mid = (int)($_POST['material_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM productmaterial WHERE ProductID=? AND MaterialID=?");
        $stmt->bind_param("ii", $product_id, $mid);
        $stmt->execute();
        if ($stmt->error) {
            $message = "Error: " . $stmt->error;
            $msg_type = "err";
        } else {
            $message = "Material removed from product.";
        }
        $stmt->close();
    }
}

$linked = $conn->query("SELECT pm.*, m.MaterialName, m.PricePerUnit FROM productmaterial pm JOIN material m ON pm.MaterialID = m.MaterialID WHERE pm.ProductID = $product_id ORDER BY m.MaterialName ASC");

$unlinked = $conn->query("SELECT * FROM material WHERE MaterialID NOT IN (SELECT MaterialID FROM productmaterial WHERE ProductID = $product_id) ORDER BY MaterialName ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Materials - Project Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div class="container">
    <a href="products.php" class="back-link">&larr; Back to Products</a>
    <h1>Materials for: <?php echo htmlspecialchars($product['ProductName']); ?></h1>
    <?php if ($message): ?>
        <div class="msg <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="section-title">Linked Materials</div>
    <?php if ($linked->num_rows === 0): ?>
        <p class="note">No materials linked to this product yet.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Material</th><th>Price / Unit</th><th>Qty Needed</th><th>Line Cost</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $linked->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['MaterialName']); ?></td>
                <td><?php echo number_format($row['PricePerUnit'], 2); ?></td>
                <td>
                    <form method="POST" style="display:inline;margin:0;">
                        <input type="hidden" name="action" value="update_qty">
                        <input type="hidden" name="material_id" value="<?php echo $row['MaterialID']; ?>">
                        <input type="number" step="0.01" min="0.01" name="quantity" value="<?php echo $row['QuantityNeeded']; ?>" style="width:80px;padding:4px 6px;border:1px solid #ccc;border-radius:3px;font-size:13px;">
                        <button type="submit" class="btn btn-sm">Save</button>
                    </form>
                </td>
                <td><?php echo number_format($row['QuantityNeeded'] * $row['PricePerUnit'], 2); ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="material_id" value="<?php echo $row['MaterialID']; ?>">
                        <button type="submit" class="btn btn-del" onclick="return confirm('Remove this material?')">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="section-title">Link a New Material</div>
    <?php if ($unlinked->num_rows === 0): ?>
        <p class="note">All available materials are already linked to this product.</p>
    <?php else: ?>
    <form method="POST" class="inline">
        <input type="hidden" name="action" value="add">
        <div>
            <label>Material *</label>
            <select name="material_id" required>
                <option value="">-- Select Material --</option>
                <?php while ($m = $unlinked->fetch_assoc()): ?>
                    <option value="<?php echo $m['MaterialID']; ?>"><?php echo htmlspecialchars($m['MaterialName']); ?> (<?php echo number_format($m['PricePerUnit'],2); ?>/unit)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label>Quantity Needed *</label>
            <input type="number" step="0.01" min="0.01" name="quantity" required>
        </div>
        <div><label>&nbsp;</label><button type="submit">Link</button></div>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
