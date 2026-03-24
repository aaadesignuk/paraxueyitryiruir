<div class="section">
  <div class="section-title">Jobs</div>
  <div class="section-hint">Click a job to open Job View.</div>

  <div class="table-wrap" style="margin-top:10px;">
    <table class="table">
      <tr>
        <th>Job</th>
        <th style="width:120px;">Status</th>
        <th style="width:160px;">Created</th>
        <th style="width:160px;">Deadline</th>
        <th style="width:140px;">Assignments</th>
        <th style="width:120px;"></th>
      </tr>

      <?php if(empty($jobs)): ?>
        <tr><td colspan="6">No jobs found.</td></tr>
      <?php else: ?>
        <?php foreach($jobs as $r): ?>
          <tr>
            <td><?= e($r['title']) ?></td>
            <td><?= e($r['status'] ?? '-') ?></td>
            <td><?= e(!empty($r['created_at']) ? uk_date($r['created_at']) : '-') ?></td>
            <td><?= e(!empty($r['deadline']) ? uk_date($r['deadline']) : '-') ?></td>
            <td><?= (int)$r['active_assignments'] ?></td>
            <td><a class="btn micro" href="/e/job_view.php?job_id=<?= (int)$r['job_id'] ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>

  <div style="margin-top:12px;">
    <a class="btn" href="/e/dashboard.php">&larr; Back</a>
  </div>
</div>