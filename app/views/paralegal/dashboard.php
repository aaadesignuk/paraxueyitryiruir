<?php ?>
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
</style>

<div class="page-head">
  <h1>Paralegal Dashboard Page</h1>
  <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
    <a class="btn micro" href="/p/daily_timesheet.php?date=<?= e(date('Y-m-d')) ?>">Today’s timesheet</a>
  </div>
  <?php if (empty($profileComplete)): ?>
    <p class="muted"><strong style="color:#d98b1f;">Profile incomplete</strong> — <a href="/p/complete_profile.php">Complete profile</a></p>
  <?php else: ?>
    <p class="muted">Profile complete.</p>
  <?php endif; ?>
</div>

<div class="dash-grid">
  <div class="section" style="margin-top:0;">
    <div class="section-title">Jobs summary</div>
    <div class="section-hint">Click to view.</div>
    <div class="card-grid-2 nav-cards">
      <a class="scope-item scope-link" href="/p/jobs.php?status=Active">
        <div class="scope-label">Active assignments</div>
        <div class="scope-value scope-value--big"><?= (int)$kpi_active_jobs ?></div>
        <div class="muted-line">View jobs</div>
      </a>
      <a class="scope-item scope-link" href="/p/dashboard.php#queried-timesheets">
        <div class="scope-label">Queried timesheets</div>
        <div class="scope-value scope-value--big"><?= (int)$kpi_timesheets_outstanding ?></div>
        <div class="muted-line">Review queried timesheets</div>
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
          return '<a class="'.$cls.'" href="/p/dashboard.php?period='.$p.'">'.e($label).'</a>';
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

<div class="section" id="queried-timesheets">
  <div class="section-title">Queried timesheets</div>
  <div class="section-hint">These need your response or resubmission.</div>
  <div class="table-wrap" style="margin-top:10px;">
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Job</th>
          <th>Employer</th>
          <th>Hours</th>
          <th>Dispute opened</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($queried_timesheets)): ?>
          <tr><td colspan="6">No queried timesheets right now.</td></tr>
        <?php else: ?>
          <?php foreach ($queried_timesheets as $q): ?>
            <tr>
              <td><?= e(uk_date($q['work_date'] ?? '')) ?></td>
              <td><?= e($q['job_title'] ?? '-') ?></td>
              <td><?= e($q['employer_name'] ?? '-') ?></td>
              <td><?= number_format((float)($q['hours_worked'] ?? 0), 2) ?></td>
              <td><?= e(uk_date($q['dispute_created_at'] ?? '')) ?></td>
              <td><a class="btn micro" href="/p/timesheets.php?status=Queried">Open</a></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="section">
  <div class="section-title">Invites</div>
  <div class="section-hint">Open a job to review details before accepting or declining.</div>
  <div class="table-wrap" style="margin-top:10px;">
    <table class="table">
      <thead>
        <tr>
          <th style="width:160px;">Date invited</th>
          <th>Job</th>
          <th style="width:220px;">Employer</th>
          <th style="width:160px;">Deadline</th>
          <th style="width:140px;">Job value</th>
          <th style="width:120px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($invites)): ?>
          <tr><td colspan="6">No invites right now.</td></tr>
        <?php else: ?>
          <?php foreach ($invites as $i): ?>
            <tr>
              <td><?= e($i['created_at'] ? uk_date($i['created_at']) : '-') ?></td>
              <td><?= e($i['title']) ?></td>
              <td><?= e($i['employer_firm'] ?: 'Employer') ?></td>
              <td><?= e($i['deadline'] ? uk_date($i['deadline']) : '-') ?></td>
              <td>
                <?php
                  $rate = $i['max_rate'];
                  $hrs  = $i['hours_required'];
                  if ($rate !== null && $hrs !== null && (float)$hrs > 0) {
                    $est = (float)$rate * (float)$hrs;
                    echo '£' . e(number_format($est, 2));
                  } elseif ($rate !== null) {
                    echo '£' . e(number_format((float)$rate, 2)) . '/hr';
                  } else {
                    echo '-';
                  }
                ?>
              </td>
              <td><a class="btn micro" href="/p/invite.php?id=<?= (int)$i['invitation_id'] ?>">Open job</a></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
