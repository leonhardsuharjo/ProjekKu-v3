<?php
require_once "db.php";
$message = "";
$msg_type = "ok";

$project_id = (int)($_GET['project_id'] ?? 0);
if ($project_id <= 0) {
    header("Location: projects.php");
    exit();
}

$stmt = $conn->prepare("SELECT p.*, c.CustomerName FROM project p JOIN customer c ON p.CustomerID=c.CustomerID WHERE p.ProjectID=?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    header("Location: projects.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);
        if ($pid <= 0) {
            $message = "Please select a product.";
            $msg_type = "err";
        } elseif ($qty <= 0) {
            $message = "Quantity must be at least 1.";
            $msg_type = "err";
        } else {
            $stmt = $conn->prepare("INSERT INTO projectproduct (ProjectID, ProductID, Quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $project_id, $pid, $qty);
            $stmt->execute();
            if ($stmt->error) {
                $message = "This product is already assigned to this project.";
                $msg_type = "err";
            } else {
                $message = "Product added to project.";
            }
            $stmt->close();
        }
    } elseif ($action === 'update_qty') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);
        if ($qty <= 0) {
            $message = "Quantity must be at least 1.";
            $msg_type = "err";
        } else {
            $stmt = $conn->prepare("UPDATE projectproduct SET Quantity=? WHERE ProjectID=? AND ProductID=?");
            $stmt->bind_param("iii", $qty, $project_id, $pid);
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
        $pid = (int)($_POST['product_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM projectproduct WHERE ProjectID=? AND ProductID=?");
        $stmt->bind_param("ii", $project_id, $pid);
        $stmt->execute();
        if ($stmt->error) {
            $message = "Error: " . $stmt->error;
            $msg_type = "err";
        } else {
            $message = "Product removed from project.";
        }
        $stmt->close();
    }
}

$linked = $conn->query("SELECT pp.*, pr.ProductName FROM projectproduct pp JOIN product pr ON pp.ProductID=pr.ProductID WHERE pp.ProjectID=$project_id ORDER BY pr.ProductName ASC");
$unlinked = $conn->query("SELECT * FROM product WHERE ProductID NOT IN (SELECT ProductID FROM projectproduct WHERE ProjectID=$project_id) ORDER BY ProductName ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Project Products - Project Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div class="container">
    <a href="projects.php" class="back-link">&larr; Back to Projects</a>
    <h1>Products in: <?php echo htmlspecialchars($project['ProjectName']); ?></h1>
    <p style="color:#666;font-size:13px;margin-bottom:16px;">Customer: <?php echo htmlspecialchars($project['CustomerName']); ?> &nbsp;|&nbsp; Date: <?php echo $project['ProjectDate']; ?></p>
    <?php if ($message): ?>
        <div class="msg <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="section-title">Assigned Products</div>
    <?php if ($linked->num_rows === 0): ?>
        <p class="note">No products assigned to this project yet.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Product</th><th>Quantity</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $linked->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                <td>
                    <form method="POST" style="display:inline;margin:0;">
                        <input type="hidden" name="action" value="update_qty">
                        <input type="hidden" name="product_id" value="<?php echo $row['ProductID']; ?>">
                        <input type="number" min="1" name="quantity" value="<?php echo $row['Quantity']; ?>" style="width:70px;padding:4px 6px;border:1px solid #ccc;border-radius:3px;font-size:13px;">
                        <button type="submit" class="btn btn-sm">Save</button>
                    </form>
                </td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?php echo $row['ProductID']; ?>">
                        <button type="submit" class="btn btn-del" onclick="return confirm('Remove this product?')">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="section-title">Add a Product</div>
    <?php if ($unlinked->num_rows === 0): ?>
        <p class="note">All products are already assigned to this project.</p>
    <?php else: ?>
    <form method="POST" class="inline">
        <input type="hidden" name="action" value="add">
        <div>
            <label>Product *</label>
            <select name="product_id" required>
                <option value="">-- Select Product --</option>
                <?php while ($p = $unlinked->fetch_assoc()): ?>
                    <option value="<?php echo $p['ProductID']; ?>"><?php echo htmlspecialchars($p['ProductName']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label>Quantity *</label>
            <input type="number" min="1" name="quantity" required>
        </div>
        <div><label>&nbsp;</label><button type="submit">Add</button></div>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
