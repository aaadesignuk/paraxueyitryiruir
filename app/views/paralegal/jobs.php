<?php
// expects: $status, $assignments, $outstanding
?>

<div class="section">
  <div class="section-title">Jobs</div>
  <div class="section-hint">Open a job to view details and submit/edit timesheets inside Job View.</div>

  <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
    <?php
      $pill = function(string $label, string $s, string $current){
        $cls = ($s === $current) ? 'btn small' : 'btn small btn-light';
        return '<a class="'.$cls.'" href="/p/jobs.php?status='.urlencode($s).'">'.e($label).'</a>';
      };
      echo $pill('Active','Active',$status);
      echo $pill('Completed','Completed',$status);
      echo $pill('Cancelled','Cancelled',$status);
      echo $pill('All','All',$status);
    ?>
  </div>

  <div class="table-wrap" style="margin-top:12px;">
    <table class="table">
      <thead>
        <tr>
          <th>Job</th>
          <th style="width:220px;">Employer</th>
          <th style="width:160px;">Deadline</th>
          <th style="width:120px;">Status</th>
          <th style="width:140px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assignments)): ?>
          <tr><td colspan="5">No jobs found.</td></tr>
        <?php else: ?>
          <?php foreach ($assignments as $a): ?>
            <tr>
              <td><?= e($a['title'] ?? '-') ?></td>
              <td><?= e($a['employer_firm'] ?: 'Employer') ?></td>
              <td><?= e($a['deadline'] ? uk_date($a['deadline']) : '-') ?></td>
              <td><?= e($a['status'] ?? '-') ?></td>
              <td class="right">
                <a class="btn small" href="/p/assignment.php?id=<?= (int)$a['assignment_id'] ?>">Open job</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="section" id="needs-action">
  <div class="section-title">Timesheets needing action</div>
  <div class="section-hint">Draft/queried timesheets. Click Open job to edit & submit inside Job View.</div>

  <div class="table-wrap" style="margin-top:10px;">
    <table class="table">
      <thead>
        <tr>
          <th style="width:160px;">Work date</th>
          <th>Job</th>
          <th style="width:220px;">Employer</th>
          <th style="width:90px;" class="right">Hours</th>
          <th style="width:120px;">Status</th>
          <th style="width:140px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($outstanding)): ?>
          <tr><td colspan="6">Nothing needs action right now.</td></tr>
        <?php else: ?>
          <?php foreach ($outstanding as $t): ?>
            <tr>
              <td><?= e($t['work_date'] ? uk_date($t['work_date']) : '-') ?></td>
              <td><?= e($t['job_title'] ?? '-') ?></td>
              <td><?= e($t['employer_firm'] ?: 'Employer') ?></td>
              <td class="right"><?= e(number_format((float)($t['hours_worked'] ?? 0), 2)) ?></td>
              <td><?= e(($t['status'] ?? '') === 'Rejected' ? 'Queried' : ($t['status'] ?? '-')) ?></td>
              <td class="right">
                <a class="btn small" href="/p/assignment.php?id=<?= (int)$t['assignment_id'] ?>&timesheet_id=<?= (int)$t['timesheet_id'] ?>#timesheets">Open job</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
