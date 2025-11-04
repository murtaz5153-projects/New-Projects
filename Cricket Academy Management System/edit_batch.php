<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Only coaches can access this page
require_coach();

$errors = [];
$success = '';

// --- 1. Get and Validate Batch ID from URL ---
$batch_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$batch_id) {
    header("Location: manage_batches.php");
    exit();
}

// --- 2. Security Check & Fetch current batch data ---
try {
    $stmt = $conn->prepare("SELECT * FROM batches WHERE id = ? AND coach_id = ?");
    $stmt->execute([$batch_id, $_SESSION['user_id']]);
    $batch = $stmt->fetch();

    if (!$batch) {
        // If batch doesn't exist or doesn't belong to this coach, redirect
        header("Location: manage_batches.php?error=notfound");
        exit();
    }
} catch (PDOException $e) {
    error_log("Edit Batch Fetch Error: " . $e->getMessage());
    die("A system error occurred.");
}

// --- 3. Handle form submission for UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid request.";
    } else {
        // Sanitize and retrieve form data
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $fees = filter_var($_POST['fees'] ?? '0', FILTER_VALIDATE_FLOAT);
        $max_students = filter_var($_POST['max_students'] ?? '0', FILTER_VALIDATE_INT);
        $timings = trim($_POST['timings'] ?? '');
        
        // Validation
        if (empty($name)) { $errors[] = "Batch Name is required."; }
        if (empty($start_date)) { $errors[] = "Start Date is required."; }
        if (empty($end_date)) { $errors[] = "End Date is required."; }
        if ($fees === false || $fees < 0) { $errors[] = "Fees must be a valid, non-negative number."; }
        if ($max_students === false || $max_students <= 0) { $errors[] = "Max Students must be a valid, positive number."; }
        if ($end_date < $start_date) { $errors[] = "End Date cannot be before the Start Date."; }

        if (empty($errors)) {
            try {
                $sql = "UPDATE batches SET 
                            name = :name, 
                            description = :description, 
                            start_date = :start_date, 
                            end_date = :end_date, 
                            fees = :fees, 
                            max_students = :max_students, 
                            timings = :timings
                        WHERE id = :id AND coach_id = :coach_id";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':start_date' => $start_date,
                    ':end_date' => $end_date,
                    ':fees' => $fees,
                    ':max_students' => $max_students,
                    ':timings' => $timings,
                    ':id' => $batch_id,
                    ':coach_id' => $_SESSION['user_id'] // Extra security check
                ]);
                
                $success = "Batch updated successfully!";
                // Refresh batch data to show updated values in the form
                $stmt = $conn->prepare("SELECT * FROM batches WHERE id = ?");
                $stmt->execute([$batch_id]);
                $batch = $stmt->fetch();

            } catch (PDOException $e) {
                error_log("Update Batch Error: " . $e->getMessage());
                $errors[] = "A system error occurred. Could not update the batch.";
            }
        }
    }
}

$page_title = "Edit Batch";
require_once 'header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2><i class="fas fa-edit"></i> Edit Batch</h2>
                <a href="manage_batches.php" class="btn btn-sm btn-secondary">Back to Batches</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="edit_batch.php?id=<?= $batch_id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="name">Batch Name</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($batch['name']) ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?= htmlspecialchars($batch['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="timings">Timings (e.g., Mon-Wed-Fri 5-7 PM)</label>
                    <input type="text" id="timings" name="timings" value="<?= htmlspecialchars($batch['timings']) ?>">
                </div>

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" required value="<?= htmlspecialchars($batch['start_date']) ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" required value="<?= htmlspecialchars($batch['end_date']) ?>">
                    </div>
                </div>

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="fees">Fees (per month)</label>
                        <input type="number" id="fees" name="fees" step="0.01" min="0" required value="<?= $batch['fees'] ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="max_students">Max Students</label>
                        <input type="number" id="max_students" name="max_students" min="1" required value="<?= $batch['max_students'] ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>