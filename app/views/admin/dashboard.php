<?php
/* ==========================================================
   app/views/admin/dashboard.php
   - Uses existing Paralete UI classes (.section, .stats-grid, .stat-card, .table, .btn)
   - Adds row titles inside the grid (Platform / Workflow / Exceptions / Finance)
   - Commission card: Standard ONLY (in-place edit + Save)
   - Adds Disputes card (red highlight when >0)
========================================================== */
?>

<div class="section">
  <div class="section-title">Admin Dashboard</div>
  <div class="section-hint">Live platform metrics and queues.</div>

<?php $open_disputes = (int)($metrics['open_disputes'] ?? 0); ?>

<div class="stats-row-title">Platform</div>
<div class="stats-grid">

  <div class="stat-card">
    <div class="stat-number"><?= e(number_format((float)($metrics['commission_rate_default'] ?? 0), 2)) ?>%</div>
    <div class="stat-label">Commission Rate (Standard)</div>

    <form method="post" action="/a/update_commission_rate.php" style="margin-top:10px;display:grid;gap:8px;">
      <div style="display:flex;gap:8px;align-items:center;justify-content:space-between;">
        <span style="opacity:.85;">Standard</span>
        <input
          type="number" step="0.01" min="0" max="100" name="commission_rate_default"
          value="<?= e(number_format((float)($metrics['commission_rate_default'] ?? 0), 2)) ?>"
          style="width:110px;padding:8px;border-radius:10px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.03);color:inherit;">
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <button class="btn" type="submit">Save</button>
        <span style="opacity:.75; font-size:12px;">Used when generating billing totals.</span>
      </div>
    </form>
  </div>

  <a class="stat-card" href="/a/employers.php" style="text-decoration:none;color:inherit">
    <div class="stat-number"><?= e((int)($metrics['employers'] ?? 0)) ?></div>
    <div class="stat-label">Employers</div>
  </a>

  <a class="stat-card" href="/a/paralegals.php" style="text-decoration:none;color:inherit">
    <div class="stat-number"><?= e((int)($metrics['paralegals'] ?? 0)) ?></div>
    <div class="stat-label">Paralegals</div>
  </a>

</div>

<div class="stats-row-title">Workflow</div>
<div class="stats-grid">

  <a class="stat-card" href="/a/jobs_open.php" style="text-decoration:none;color:inherit">
    <div class="stat-number"><?= e((int)($metrics['open_jobs'] ?? 0)) ?></div>
    <div class="stat-label">Open Jobs</div>
  </a>

  <a class="stat-card" href="/a/invited.php" style="text-decoration:none;color:inherit">
    <div class="stat-number"><?= e((int)($metrics['pending_invites'] ?? 0)) ?></div>
    <div class="stat-label">Pending Invites</div>
  </a>

  <a class="stat-card" href="/a/timesheets_submitted.php" style="text-decoration:none;color:inherit">
    <div class="stat-number"><?= e((int)($metrics['submitted_timesheets'] ?? 0)) ?></div>
    <div class="stat-label">Submitted Timesheets</div>
  </a>

</div>

<div class="stats-row-title">Exceptions</div>
<div class="stats-grid">

  <a class="stat-card <?= $open_disputes > 0 ? 'stat-card--alert' : '' ?>"
     href="/a/timesheet_disputes.php"
     style="text-decoration:none;color:inherit">
    <div class="stat-number"><?= e($open_disputes) ?></div>
    <div class="stat-label">Disputes</div>
  </a>

  <a class="stat-card" href="/a/timesheets_submitted.php" style="text-decoration:none;color:inherit">
    <div class="stat-number"><?= e((int)($metrics['overdue_submitted_timesheets'] ?? 0)) ?></div>
    <div class="stat-label">Overdue (7+ days)</div>
  </a>

  <a class="stat-card" href="/a/timesheets_submitted.php" style="text-decoration:none;color:inherit">
    <div class="stat-number"><?= e((int)($metrics['queried_timesheets'] ?? 0)) ?></div>
    <div class="stat-label">Queried Timesheets</div>
  </a>

</div>

<div class="stats-row-title">Finance</div>
<div class="stats-grid">

  <a class="stat-card" href="/a/commission_invoices.php" style="text-decoration:none;color:inherit">
    <div class="stat-number"><?= e((int)($metrics['unpaid_commission_invoices'] ?? 0)) ?></div>
    <div class="stat-label">Unpaid Commission Invoices</div>
  </a>

  <a class="stat-card" href="/a/commission_invoices.php" style="text-decoration:none;color:inherit">
    <div class="stat-number">£<?= e(number_format((float)($metrics['total_commission_invoiced'] ?? 0), 2)) ?></div>
    <div class="stat-label">Total Commission Invoiced</div>
  </a>

  <a class="stat-card" href="/a/billing.php" style="text-decoration:none;color:inherit">
    <div class="stat-number">View</div>
    <div class="stat-label">All Invoices</div>
  </a>

</div>	
	
	
</div>


<div class="section">
  <div class="section-title">Pending Timesheets</div>

  <table class="table">
    <tr>
      <th>Employer</th>
      <th>Paralegal</th>
      <th>Date</th>
      <th>Hours</th>
      <th>Status</th>
    </tr>

    <?php if (!$pending_timesheets): ?>
      <tr><td colspan="5">No pending timesheets.</td></tr>
    <?php endif; ?>

    <?php foreach ($pending_timesheets as $t): ?>
      <tr>
        <td><?= e($t['employer_name']) ?></td>
        <td><?= e($t['paralegal_name']) ?></td>
        <td><?= e($t['work_date']) ?></td>
        <td><?= e($t['hours_worked']) ?></td>
        <td><?= e($t['status']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>


<div class="section">
  <div class="section-title">Recent Jobs</div>

  <table class="table">
    <tr>
      <th>Title</th>
      <th>Employer</th>
      <th>Status</th>
      <th>Created</th>
    </tr>

    <?php if (!$recent_jobs): ?>
      <tr><td colspan="4">No jobs yet.</td></tr>
    <?php endif; ?>

    <?php foreach ($recent_jobs as $j): ?>
      <tr>
        <td><?= e($j['title']) ?></td>
        <td><?= e($j['employer_name']) ?></td>
        <td><?= e($j['status']) ?></td>
        <td><?= e($j['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>