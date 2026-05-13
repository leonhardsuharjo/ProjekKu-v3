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
        $jid = (int)($_POST['jobrole_id'] ?? 0);
        $workers = (int)($_POST['num_workers'] ?? 0);
        $days = (int)($_POST['num_days'] ?? 0);
        if ($jid <= 0) {
            $message = "Please select a job role.";
            $msg_type = "err";
        } elseif ($workers <= 0) {
            $message = "Number of workers must be at least 1.";
            $msg_type = "err";
        } elseif ($days <= 0) {
            $message = "Number of days must be at least 1.";
            $msg_type = "err";
        } else {
            $stmt = $conn->prepare("INSERT INTO projectlabour (ProjectID, JobRoleID, NumWorkers, NumDays) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiii", $project_id, $jid, $workers, $days);
            $stmt->execute();
            if ($stmt->error) {
                $message = "This job role is already assigned to this project.";
                $msg_type = "err";
            } else {
                $message = "Labour added to project.";
            }
            $stmt->close();
        }
    } elseif ($action === 'update') {
        $jid = (int)($_POST['jobrole_id'] ?? 0);
        $workers = (int)($_POST['num_workers'] ?? 0);
        $days = (int)($_POST['num_days'] ?? 0);
        if ($workers <= 0 || $days <= 0) {
            $message = "Workers and days must be at least 1.";
            $msg_type = "err";
        } else {
            $stmt = $conn->prepare("UPDATE projectlabour SET NumWorkers=?, NumDays=? WHERE ProjectID=? AND JobRoleID=?");
            $stmt->bind_param("iiii", $workers, $days, $project_id, $jid);
            $stmt->execute();
            if ($stmt->error) {
                $message = "Error: " . $stmt->error;
                $msg_type = "err";
            } else {
                $message = "Labour record updated.";
            }
            $stmt->close();
        }
    } elseif ($action === 'remove') {
        $jid = (int)($_POST['jobrole_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM projectlabour WHERE ProjectID=? AND JobRoleID=?");
        $stmt->bind_param("ii", $project_id, $jid);
        $stmt->execute();
        if ($stmt->error) {
            $message = "Error: " . $stmt->error;
            $msg_type = "err";
        } else {
            $message = "Labour removed from project.";
        }
        $stmt->close();
    }
}

$linked = $conn->query("SELECT pl.*, jr.JobType, jr.WagePerDay FROM projectlabour pl JOIN jobrole jr ON pl.JobRoleID=jr.JobRoleID WHERE pl.ProjectID=$project_id ORDER BY jr.JobType ASC");
$unlinked = $conn->query("SELECT * FROM jobrole WHERE JobRoleID NOT IN (SELECT JobRoleID FROM projectlabour WHERE ProjectID=$project_id) ORDER BY JobType ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Project Labour - Project Manager</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include "nav.php"; ?>
<div class="container">
    <a href="projects.php" class="back-link">&larr; Back to Projects</a>
    <h1>Labour for: <?php echo htmlspecialchars($project['ProjectName']); ?></h1>
    <p style="color:#666;font-size:13px;margin-bottom:16px;">Customer: <?php echo htmlspecialchars($project['CustomerName']); ?> &nbsp;|&nbsp; Date: <?php echo $project['ProjectDate']; ?></p>
    <?php if ($message): ?>
        <div class="msg <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="section-title">Assigned Labour</div>
    <?php if ($linked->num_rows === 0): ?>
        <p class="note">No labour assigned to this project yet.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Job Role</th><th>Wage/Day</th><th>Workers</th><th>Days</th><th>Labour Cost</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $linked->fetch_assoc()):
            $cost = $row['NumWorkers'] * $row['NumDays'] * $row['WagePerDay'];
        ?>
            <tr>
                <td><?php echo htmlspecialchars($row['JobType']); ?></td>
                <td><?php echo number_format($row['WagePerDay'], 2); ?></td>
                <td>
                    <form method="POST" style="display:inline;margin:0;">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="jobrole_id" value="<?php echo $row['JobRoleID']; ?>">
                        <input type="number" min="1" name="num_workers" value="<?php echo $row['NumWorkers']; ?>" style="width:60px;padding:4px 6px;border:1px solid #ccc;border-radius:3px;font-size:13px;">
                </td>
                <td>
                        <input type="number" min="1" name="num_days" value="<?php echo $row['NumDays']; ?>" style="width:60px;padding:4px 6px;border:1px solid #ccc;border-radius:3px;font-size:13px;">
                        <button type="submit" class="btn btn-sm">Save</button>
                    </form>
                </td>
                <td><?php echo number_format($cost, 2); ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="jobrole_id" value="<?php echo $row['JobRoleID']; ?>">
                        <button type="submit" class="btn btn-del" onclick="return confirm('Remove this labour entry?')">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="section-title">Add Labour</div>
    <?php if ($unlinked->num_rows === 0): ?>
        <p class="note">All job roles are already assigned to this project.</p>
    <?php else: ?>
    <form method="POST" class="inline">
        <input type="hidden" name="action" value="add">
        <div>
            <label>Job Role *</label>
            <select name="jobrole_id" required>
                <option value="">-- Select Job Role --</option>
                <?php while ($jr = $unlinked->fetch_assoc()): ?>
                    <option value="<?php echo $jr['JobRoleID']; ?>"><?php echo htmlspecialchars($jr['JobType']); ?> (<?php echo number_format($jr['WagePerDay'],2); ?>/day)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label>Num. Workers *</label>
            <input type="number" min="1" name="num_workers" required>
        </div>
        <div>
            <label>Num. Days *</label>
            <input type="number" min="1" name="num_days" required>
        </div>
        <div><label>&nbsp;</label><button type="submit">Add</button></div>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
