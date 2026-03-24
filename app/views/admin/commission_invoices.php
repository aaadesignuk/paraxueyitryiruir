<div class="section">
  <div class="section-title">
    <h1>Invoices</h1>
    <p>Admin commission view split by employer and paralegal, one row per monthly invoice.</p>
  </div>

  <?php
    $bucket_titles = [
      'upcoming' => 'Upcoming / To Submit (first 3 days)',
      'awaiting' => 'Submitted – Awaiting Payment',
      'paid' => 'Paid',
    ];
    $buckets = ['upcoming' => [], 'awaiting' => [], 'paid' => []];
    foreach (($rows ?? []) as $row) {
      $st = strtolower((string)($row['status'] ?? 'draft'));
      if ($st === 'paid') $buckets['paid'][] = $row;
      elseif ($st === 'submitted') $buckets['awaiting'][] = $row;
      else $buckets['upcoming'][] = $row;
    }

    $render_block = function(array $list, string $heading) use ($commission_rate_default) {
      ?>
      <div class="section" style="margin-top:16px;">
        <div class="section-title"><?= e($heading) ?></div>
        <table class="table">
          <thead>
            <tr>
              <th>Employer</th>
              <th>Firm</th>
              <th>Paralegal</th>
              <th>Client refs</th>
              <th>Period</th>
              <th>Gross</th>
              <th>Commission</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$list): ?>
              <tr><td colspan="9" style="opacity:.75;">None.</td></tr>
            <?php endif; ?>
            <?php foreach($list as $inv): ?>
              <?php
                $commission = round((float)$inv['gross_amount'] * ((float)$commission_rate_default / 100), 2);
                $displayStatus = (string)($inv['status'] ?? 'Draft');
                if ($displayStatus === 'Draft' && (int)date('j') <= 3) $displayStatus = 'To Submit';
              ?>
              <tr>
                <td><?= e($inv['employer_name']) ?></td>
                <td><?= e($inv['firm_name'] ?? '—') ?></td>
                <td><?= e($inv['paralegal_name']) ?></td>
                <td><?= e(($inv['client_refs'] ?? '') !== '' ? $inv['client_refs'] : '—') ?></td>
                <td><?= e(uk_date($inv['period_start'])) ?> → <?= e(uk_date($inv['period_end'])) ?></td>
                <td>£<?= number_format((float)$inv['gross_amount'], 2) ?></td>
                <td><strong>£<?= number_format($commission, 2) ?></strong></td>
                <td><?= e($displayStatus) ?></td>
                <td style="white-space:nowrap;">
                  <a class="btn" href="/a/invoice.php?id=<?= (int)$inv['invoice_id'] ?>">View</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php
    };
  ?>

  <?php foreach ($bucket_titles as $k => $label): ?>
    <?php $render_block($buckets[$k], $label); ?>
  <?php endforeach; ?>
</div>
