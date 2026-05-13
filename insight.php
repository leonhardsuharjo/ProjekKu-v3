<?php
require_once "db.php";

$sql = "
SELECT
    pr.ProjectID,
    pr.ProjectName,
    pr.ProjectDate,
    c.CustomerName,
    pr.ProjectValue,
    pr.TransportCost,
    COALESCE((
        SELECT SUM(pp.Quantity * COALESCE(mc.mat_cost, 0))
        FROM projectproduct pp
        LEFT JOIN (
            SELECT pm.ProductID, SUM(pm.QuantityNeeded * m.PricePerUnit) AS mat_cost
            FROM productmaterial pm
            JOIN material m ON pm.MaterialID = m.MaterialID
            GROUP BY pm.ProductID
        ) mc ON pp.ProductID = mc.ProductID
        WHERE pp.ProjectID = pr.ProjectID
    ), 0) AS MaterialCost,
    COALESCE((
        SELECT SUM(pl.NumWorkers * pl.NumDays * jr.WagePerDay)
        FROM projectlabour pl
        JOIN jobrole jr ON pl.JobRoleID = jr.JobRoleID
        WHERE pl.ProjectID = pr.ProjectID
    ), 0) AS LabourCost
FROM project pr
JOIN customer c ON pr.CustomerID = c.CustomerID
ORDER BY pr.ProjectDate DESC
";

$result = $conn->query($sql);

$total_value = 0;
$total_transport = 0;
$total_material = 0;
$total_labour = 0;
$total_profit = 0;
$rows = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['GrossProfit'] = $row['ProjectValue'] - $row['TransportCost'] - $row['MaterialCost'] - $row['LabourCost'];
        $rows[] = $row;
        $total_value += $row['ProjectValue'];
        $total_transport += $row['TransportCost'];
        $total_material += $row['MaterialCost'];
        $total_labour += $row['LabourCost'];
        $total_profit += $row['GrossProfit'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Insight - Project Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div class="container">
    <h1>Project Insight</h1>
    <p style="color:#555;font-size:13px;margin-bottom:18px;">All costs are computed at runtime. GrossProfit = ProjectValue &minus; TransportCost &minus; MaterialCost &minus; LabourCost</p>

    <?php if (empty($rows)): ?>
        <p class="note">No projects found.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Project</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Value</th>
                <th>Transport</th>
                <th>Material Cost</th>
                <th>Labour Cost</th>
                <th>Gross Profit</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row):
            $profit_color = $row['GrossProfit'] >= 0 ? '#155724' : '#721c24';
        ?>
            <tr>
                <td><?php echo $row['ProjectID']; ?></td>
                <td><?php echo htmlspecialchars($row['ProjectName']); ?></td>
                <td><?php echo $row['ProjectDate']; ?></td>
                <td><?php echo htmlspecialchars($row['CustomerName']); ?></td>
                <td><?php echo number_format($row['ProjectValue'], 2); ?></td>
                <td><?php echo number_format($row['TransportCost'], 2); ?></td>
                <td><?php echo number_format($row['MaterialCost'], 2); ?></td>
                <td><?php echo number_format($row['LabourCost'], 2); ?></td>
                <td style="font-weight:bold;color:<?php echo $profit_color; ?>;"><?php echo number_format($row['GrossProfit'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:#1a3c5e;color:#fff;font-weight:bold;">
                <td colspan="4">TOTAL (<?php echo count($rows); ?> projects)</td>
                <td><?php echo number_format($total_value, 2); ?></td>
                <td><?php echo number_format($total_transport, 2); ?></td>
                <td><?php echo number_format($total_material, 2); ?></td>
                <td><?php echo number_format($total_labour, 2); ?></td>
                <td style="color:<?php echo $total_profit >= 0 ? '#90ee90' : '#ffb3b3'; ?>;"><?php echo number_format($total_profit, 2); ?></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
