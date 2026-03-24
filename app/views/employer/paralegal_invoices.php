<div class="section">
  <div class="section-title">
    <h1><?= e($title ?? 'Invoices') ?></h1>
    <p>One invoice per paralegal per month. Commission shown beneath each invoice.</p>
  </div>

  <form method="get" class="section" style="padding:10px; margin-bottom:12px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
      <div>
        <label style="display:block; margin-bottom:6px;">Year</label>
        <input class="input" type="number" name="year" value="<?= (int)($year ?? 0) ?>" style="max-width:120px;">
      </div>
      <div>
        <label style="display:block; margin-bottom:6px;">Month</label>
        <select class="input" name="month" style="max-width:180px;">
          <option value="0">All months</option>
          <?php for ($m=1; $m<=12; $m++): ?>
            <option value="<?= $m ?>" <?= ((int)($month ?? 0) === $m ? 'selected' : '') ?>><?= e(date('F', mktime(0,0,0,$m,1))) ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div>
        <label style="display:block; margin-bottom:6px;">Client ref</label>
        <select class="input" name="client_ref" style="max-width:220px;">
          <option value="">All client refs</option>
          <?php foreach (($client_refs ?? []) as $cr): ?>
            <option value="<?= e($cr['client_ref']) ?>" <?= (($client_ref ?? '') === (string)$cr['client_ref']) ? 'selected' : '' ?>><?= e($cr['client_ref']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn" type="submit" style="width:auto; display:inline-flex; white-space:nowrap;">Filter</button>
        <a class="btn secondary" href="/e/paralegal_invoices.php" style="width:auto; display:inline-flex; white-space:nowrap;">Clear</a>
      </div>
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

    $render_block = function(array $list, string $heading) use ($commission_rate_default) {
      ?>
      <div class="section" style="margin-top:16px;">
        <div class="section-title"><?= e($heading) ?></div>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Paralegal</th>
                <th>Client refs</th>
                <th>Period</th>
                <th>Hours</th>
                <th>Gross</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$list): ?>
                <tr><td colspan="7" style="opacity:.8;">None.</td></tr>
              <?php endif; ?>
              <?php foreach ($list as $inv): ?>
                <?php
                  $rate = (float)($commission_rate_default ?? 0);
                  $commission_due = round(((float)($inv['gross_amount'] ?? 0)) * ($rate / 100), 2);
                  $displayStatus = (string)($inv['status'] ?? 'Draft');
                  if ($displayStatus === 'Draft' && (int)date('j') <= 3) $displayStatus = 'To Submit';
                ?>
                <tr>
                  <td><strong><?= e($inv['paralegal_name'] ?? '') ?></strong></td>
                  <td><?= e(($inv['client_refs'] ?? '') !== '' ? $inv['client_refs'] : '—') ?></td>
                  <td><?= e(uk_date($inv['period_start'] ?? '')) ?> to <?= e(uk_date($inv['period_end'] ?? '')) ?></td>
                  <td><?= number_format((float)($inv['total_hours'] ?? 0), 2) ?></td>
                  <td>£<?= number_format((float)($inv['gross_amount'] ?? 0), 2) ?></td>
                  <td><?= e($displayStatus) ?></td>
                  <td class="right"><a class="btn small" href="/e/paralegal_invoice.php?id=<?= (int)($inv['invoice_id'] ?? 0) ?>" style="width:auto;">View</a></td>
                </tr>
              <tr>
  <td colspan="7" style="background:#f8fafc !important; color:#111827 !important; border-top:1px solid #d1d5db; padding:12px 14px;">
    <span style="color:#111827 !important; opacity:1;">Paralete commission:</span>
    <strong style="color:#111827 !important;">£<?= number_format($commission_due, 2) ?></strong>
    <span style="color:#111827 !important; opacity:1;">at <?= rtrim(rtrim(number_format($rate, 2), '0'), '.') ?>%</span>
  </td>
</tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php
    };
  ?>

  <?php foreach ($bucket_titles as $k => $label): ?>
    <?php $render_block($buckets[$k], $label); ?>
  <?php endforeach; ?>
</div>
