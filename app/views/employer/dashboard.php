<?php
?>
<style>
.dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:stretch;}
@media(max-width:900px){.dash-grid{grid-template-columns:1fr;}}
.dash-grid > .section{height:100%;display:flex;flex-direction:column;}
.card-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:12px;align-items:stretch;}
@media(max-width:560px){.card-grid-2{grid-template-columns:1fr;}}
.card-grid-2 .scope-item{min-height:132px;display:flex;flex-direction:column;justify-content:space-between;padding:14px;border-radius:16px;}
a.scope-item.scope-link{text-decoration:none;}
.scope-value--big{font-size:28px;line-height:1;margin:12px 0;}
.period-pill{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-top:12px;}
.nav-cards{margin-top:56px;}
.chip-count{display:inline-block;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.05);font-weight:800;}
</style>

<div class="section" style="margin-top:0;">
  <div class="section-title">Employer Dashboard</div>
  <div class="section-hint"><?= e($firm_name) ?> • Overview and navigation only</div>
</div>

<div class="dash-grid">
  <div class="section" style="margin-top:0;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
      <div>
        <div class="section-title" style="margin:0;">Jobs summary</div>
        <div class="section-hint" style="margin-top:4px;">Click to view.</div>
      </div>
      <a class="btn btn-primary" style="width:auto; display:inline-flex; white-space:nowrap;" href="/e/job_create.php">+ Create job</a>
    </div>
    <div class="card-grid-2 nav-cards">
      <a class="scope-item scope-link" href="/e/jobs.php?status=Open,Active">
        <div class="scope-label">Active jobs</div>
        <div class="scope-value scope-value--big"><?= (int)$kpi_active_jobs ?></div>
        <div class="muted-line">View jobs</div>
      </a>
      <a class="scope-item scope-link" href="/e/dashboard.php#timesheets-review">
        <div class="scope-label">Timesheets to review</div>
        <div class="scope-value scope-value--big"><?= (int)$kpi_timesheets_to_review ?></div>
        <div class="muted-line">Submitted and queried days</div>
      </a>
    </div>
  </div>

  <div class="section" style="margin-top:0;">
    <div class="section-title">Performance</div>
    <div class="section-hint">Figures update based on the selected period.</div>
    <div class="period-pill">
      <?php
        $btn = function(string $label, string $p, string $current){
          $cls = ($p === $current) ? 'btn micro' : 'btn micro secondary';
          return '<a class="'.$cls.'" href="/e/dashboard.php?period='.$p.'">'.e($label).'</a>';
        };
        echo $btn('This month','month',$period);
        echo $btn('Last month','last_month',$period);
        echo $btn('Year total','year',$period);
      ?>
    </div>
    <div class="card-grid-2" style="margin-top:14px;">
      <div class="scope-item">
        <div class="scope-label">Approved hours</div>
        <div class="scope-value scope-value--big"><?= e(number_format((float)$kpi_approved_hours, 2)) ?></div>
        <div class="muted-line"><?= e($period_label) ?></div>
      </div>
      <div class="scope-item">
        <div class="scope-label">Approved value</div>
        <div class="scope-value scope-value--big">£<?= e(number_format((float)$kpi_approved_value, 2)) ?></div>
        <div class="muted-line"><?= e($period_label) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="section" id="timesheets-review">
  <div class="section-title">Timesheets to review</div>
  <div class="section-hint">Open a job to review submitted or queried timesheets.</div>
  <div class="table-wrap" style="margin-top:10px;">
    <table class="table">
      <thead>
        <tr>
          <th>Job</th>
          <th style="width:190px;">Paralegal</th>
          <th style="width:160px;">Oldest date</th>
          <th style="width:130px;">Entries</th>
          <th style="width:160px;">Status mix</th>
          <th style="width:160px;">Job deadline</th>
          <th style="width:120px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($jobs_needing_review)): ?>
          <tr><td colspan="7">No jobs currently waiting for timesheet review.</td></tr>
        <?php else: ?>
          <?php foreach($jobs_needing_review as $r): ?>
            <tr>
              <td><?= e($r['title']) ?></td>
              <td><?= e($r['paralegal_name']) ?></td>
              <td><?= e($r['oldest_review_date'] ? uk_date($r['oldest_review_date']) : '-') ?></td>
              <td><span class="chip-count"><?= (int)$r['row_count'] ?></span></td>
              <td>Submitted: <?= (int)$r['submitted_count'] ?> · Queried: <?= (int)$r['queried_count'] ?></td>
              <td><?= e($r['deadline'] ? uk_date($r['deadline']) : '-') ?></td>
              <td><a class="btn micro" href="/e/job_view.php?job_id=<?= (int)$r['job_id'] ?>#timesheets">Open job</a></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
