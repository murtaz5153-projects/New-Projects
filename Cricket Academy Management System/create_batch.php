<?php declare(strict_types=1);
require_once 'config.php';
require_once 'auth.php';

// Only logged-in coaches can create batches
require_coach();

$errors = [];
$success = '';

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid request.";
    } else {
        // Sanitize and retrieve form data
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? ''); // End date is now required
        $fees = filter_var($_POST['fees'] ?? '0', FILTER_VALIDATE_FLOAT);
        $max_students = filter_var($_POST['max_students'] ?? '0', FILTER_VALIDATE_INT);
        $timings = trim($_POST['timings'] ?? '');
        
        // --- UPDATED VALIDATION ---
        if (empty($name)) { $errors[] = "Batch Name is required."; }
        if (empty($start_date)) { $errors[] = "Start Date is required."; }
        if (empty($end_date)) { $errors[] = "End Date is required."; } // Added this check
        if ($fees === false || $fees < 0) { $errors[] = "Fees must be a valid, non-negative number."; }
        if ($max_students === false || $max_students <= 0) { $errors[] = "Max Students must be a valid, positive number."; }
        if (!empty($start_date) && !empty($end_date) && $end_date < $start_date) {
            $errors[] = "End Date cannot be before the Start Date.";
        }

        // If no validation errors, insert into the database
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO batches (coach_id, name, description, start_date, end_date, fees, max_students, timings) 
                        VALUES (:coach_id, :name, :description, :start_date, :end_date, :fees, :max_students, :timings)";
                
                $stmt = $conn->prepare($sql);
                
                // --- UPDATED EXECUTION ARRAY ---
                $stmt->execute([
                    ':coach_id' => $_SESSION['user_id'],
                    ':name' => $name,
                    ':description' => $description,
                    ':start_date' => $start_date,
                    ':end_date' => $end_date, // Directly use the required end date
                    ':fees' => $fees,
                    ':max_students' => $max_students,
                    ':timings' => $timings
                ]);
                
                $success = "New batch '".htmlspecialchars($name)."' created successfully!";

            } catch (PDOException $e) {
                error_log("Create Batch Error: " . $e->getMessage());
                $errors[] = "A system error occurred. Could not create the batch.";
            }
        }
    }
}


$page_title = "Create New Batch";
require_once 'header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2><i class="fas fa-plus-circle"></i> Create New Batch</h2>
                <a href="coach_dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a>
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

            <form method="POST" action="create_batch.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="name">Batch Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="timings">Timings (e.g., Mon-Wed-Fri 5-7 PM)</label>
                    <input type="text" id="timings" name="timings">
                </div>

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>
                    <div class="form--group" style="flex: 1;">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" required>
                    </div>
                </div>

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="fees">Fees (per month)</label>
                        <input type="number" id="fees" name="fees" step="0.01" min="0" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="max_students">Max Students</label>
                        <input type="number" id="max_students" name="max_students" min="1" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Batch</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>