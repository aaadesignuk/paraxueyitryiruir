<?php
require_once __DIR__.'/../app/bootstrap.php';
require_role([ROLE_EMPLOYER]);

$employer_id = (int)auth_user()['user_id'];

$job_id = (int)($_GET['job_id'] ?? 0);
$paralegal_id = (int)($_GET['paralegal_id'] ?? 0);
$work_date = $_GET['date'] ?? '';

if (!$job_id || !$paralegal_id || !$work_date) {
    redirect('/e/timesheets.php');
}

/* Security: ensure job belongs to employer */
$job = db_fetch_one("
    SELECT * FROM jobs
    WHERE job_id = ? AND employer_id = ?
    LIMIT 1
", [$job_id, $employer_id]);

if (!$job) {
    redirect('/e/timesheets.php');
}

/* Fetch entries for that day */
$entries = db_fetch_all("
    SELECT *
    FROM timesheet_entries
    WHERE job_id = ?
      AND paralegal_id = ?
      AND work_date = ?
    ORDER BY id ASC
", [$job_id, $paralegal_id, $work_date]);

if (!$entries) {
    redirect('/e/timesheets.php');
}

$total_hours = 0;
$status = $entries[0]['status'];

foreach ($entries as $e) {
    $total_hours += $e['hours'];
}
?>

<h1>Timesheet – <?= e(date('d/m/Y', strtotime($work_date))) ?></h1>

<p><strong>Job:</strong> <?= e($job['title']) ?></p>

<table class="table">
    <thead>
        <tr>
            <th>Description</th>
            <th>Hours</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($entries as $entry): ?>
            <tr>
                <td><?= nl2br(e($entry['description'])) ?></td>
                <td><?= e(number_format($entry['hours'], 2)) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p><strong>Total Hours:</strong> <?= number_format($total_hours, 2) ?></p>
<p><strong>Status:</strong> <?= e($status) ?></p>

<?php if ($status === 'Submitted' || $status === 'Queried'): ?>
    <form method="post" action="/e/timesheet_action.php">
        <input type="hidden" name="job_id" value="<?= $job_id ?>">
        <input type="hidden" name="paralegal_id" value="<?= $paralegal_id ?>">
        <input type="hidden" name="work_date" value="<?= e($work_date) ?>">

        <textarea name="comment" placeholder="Optional comment (required if querying)"></textarea>

        <button name="action" value="approve" class="btn btn-success">Approve Day</button>
        <button name="action" value="query" class="btn btn-warning">Query Day</button>
    </form>
<?php endif; ?>