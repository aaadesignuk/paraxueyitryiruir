<div class="section">
  <div class="section-title">
    <h1>My Invoices</h1>
    <p>One invoice per employer per month.</p>
  </div>

  <form method="get" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:12px;">
    <div>
      <label>Year</label>
      <input type="number" name="y" value="<?= (int)$y ?>" min="2020" max="2100" style="width:120px;" placeholder="e.g. 2026">
    </div>
    <div>
      <label>Month</label>
      <select name="m" style="width:180px;">
        <option value="0" <?= ((int)$m===0)?'selected':'' ?>>All months</option>
        <?php $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December']; ?>
        <?php foreach($months as $mi=>$mn): ?>
          <option value="<?= (int)$mi ?>" <?= ((int)$m===(int)$mi)?'selected':'' ?>><?= e($mn) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex; gap:10px; align-items:flex-end;">
      <button class="btn" type="submit" style="width:auto; display:inline-flex; white-space:nowrap;">Filter</button>
      <a class="btn secondary" href="/p/invoices.php" style="width:auto; display:inline-flex; white-space:nowrap;">Clear</a>
    </div>
  </form>

  <?php
    $bucket_titles = [
      'upcoming' => 'Upcoming / To Submit (first 3 days)',
      'awaiting' => 'Submitted – Awaiting Payment',
      'paid' => 'Paid',
    ];
    $buckets = ['upcoming' => [], 'awaiting' => [], 'paid' => []];
    foreach (($invoices ?? []) as $inv) {
      $st = strtolower((string)($inv['status'] ?? 'draft'));
      if ($st === 'paid') $buckets['paid'][] = $inv;
      elseif ($st === 'submitted') $buckets['awaiting'][] = $inv;
      else $buckets['upcoming'][] = $inv;
    }

    $render_rows = function(array $rows) {
      ?>
      <table class="table">
        <thead>
          <tr>
            <th>Employer</th>
            <th>Client refs</th>
            <th>Period</th>
            <th>Total hours</th>
            <th>Gross amount</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7">None.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $inv): ?>
            <?php
              $st = (string)($inv['status'] ?? 'Draft');
              $displayStatus = $st;
              if ($st === 'Draft' && (int)date('j') <= 3) $displayStatus = 'To Submit';
              $badgeStyle = 'display:inline-block;padding:4px 10px;border-radius:999px;font-weight:700;';
              if ($st === 'Draft') $badgeStyle .= 'background:#f3f4f6;color:#374151;';
              elseif ($st === 'Submitted') $badgeStyle .= 'background:#dbeafe;color:#1d4ed8;';
              elseif ($st === 'Paid') $badgeStyle .= 'background:#dcfce7;color:#166534;';
              else $badgeStyle .= 'background:#f3f4f6;color:#374151;';
            ?>
            <tr>
              <td><?= e(($inv['employer_name'] ?? '') !== '' ? $inv['employer_name'] : '-') ?></td>
              <td><?= e(($inv['client_refs'] ?? '') !== '' ? $inv['client_refs'] : '—') ?></td>
              <td><?= e(uk_date($inv['period_start'])) ?> to <?= e(uk_date($inv['period_end'])) ?></td>
              <td><?= number_format((float)$inv['total_hours'], 2) ?></td>
              <td>£<?= number_format((float)$inv['gross_amount'], 2) ?></td>
              <td><span style="<?= e($badgeStyle) ?>"><?= e($displayStatus) ?></span></td>
              <td><a class="btn small" href="/p/invoice.php?id=<?= (int)$inv['invoice_id'] ?>" style="width:auto;">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php
    };
  ?>

  <?php foreach ($bucket_titles as $key => $label): ?>
    <div class="section" style="margin-top:14px;">
      <div class="section-title"><?= e($label) ?></div>
      <?php $render_rows($buckets[$key]); ?>
    </div>
  <?php endforeach; ?>

  <?php if(!empty($pg) && ($pg['total_pages'] ?? 1) > 1): ?>
    <div class="row" style="gap:10px; align-items:center; margin-top:12px;">
      <?php $qs = []; if(!empty($y)) $qs['y']=(int)$y; if(!empty($m)) $qs['m']=(int)$m; ?>
      <?php if($pg['has_prev']): ?>
        <?php $qs['page']=(int)($pg['page']-1); ?>
        <a class="btn" href="/p/invoices.php?<?= e(http_build_query($qs)) ?>">&larr; Prev</a>
      <?php else: ?>
        <span class="btn" style="opacity:.5; pointer-events:none;">&larr; Prev</span>
      <?php endif; ?>
      <div style="opacity:.8;">Page <strong><?= (int)$pg['page'] ?></strong> of <strong><?= (int)$pg['total_pages'] ?></strong> (<?= (int)$pg['total'] ?> total)</div>
      <?php if($pg['has_next']): ?>
        <?php $qs['page']=(int)($pg['page']+1); ?>
        <a class="btn" href="/p/invoices.php?<?= e(http_build_query($qs)) ?>">Next &rarr;</a>
      <?php else: ?>
        <span class="btn" style="opacity:.5; pointer-events:none;">Next &rarr;</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
