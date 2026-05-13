<?php require_once "db.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Project Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div class="container">
    <h1>Project Manager</h1>
    <p style="margin-bottom:20px;color:#555;font-size:14px;">Select a section to manage records.</p>
    <div class="card-grid">
        <div class="card"><a href="customers.php">Customers</a><p>Manage customer records</p></div>
        <div class="card"><a href="suppliers.php">Suppliers</a><p>Manage material suppliers</p></div>
        <div class="card"><a href="products.php">Products</a><p>Manage products and their materials</p></div>
        <div class="card"><a href="materials.php">Materials</a><p>Manage raw materials</p></div>
        <div class="card"><a href="jobroles.php">Job Roles</a><p>Manage job types and wages</p></div>
        <div class="card"><a href="projects.php">Projects</a><p>Manage project records</p></div>
        <div class="card"><a href="insight.php">Insight</a><p>View project profitability</p></div>
    </div>
</div>
</body>
</html>
