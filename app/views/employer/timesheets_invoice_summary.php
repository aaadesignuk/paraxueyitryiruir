<?php
$fmt_hours = function($h){
  $s = rtrim(rtrim(number_format((float)$h, 2, '.', ''), '0'), '.');
  return $s === '' ? '0' : $s;
};

$range_title = e(uk_date($period_start)).' – '.e(uk_date($period_end)).' – Timesheet Summary';
$selected_client_ref = trim((string)($_GET['client_ref'] ?? ''));

// Build dropdown options from the existing $weeks payload
$client_refs_map = [];
foreach ($weeks as $w) {
  foreach (($w['days'] ?? []) as $d) {
    foreach (($d['line_items'] ?? []) as $li) {
      $ref = trim((string)($li['client_ref'] ?? ''));
      if ($ref !== '') {
        $client_refs_map[$ref] = true;
      }
    }
  }
}
$client_refs = array_keys($client_refs_map);
sort($client_refs, SORT_NATURAL | SORT_FLAG_CASE);

// Build filtered weeks in-view to keep this as the simplest fix
$filtered_weeks = [];
$filtered_month_total = 0.0;

foreach ($weeks as $w) {
  $week_total = 0.0;
  $days = [];

  foreach (($w['days'] ?? []) as $d) {
    $visible_items = array_values(array_filter(($d['line_items'] ?? []), function($li) use ($selected_client_ref) {
      $hours_ok = isset($li['hours']) && (float)$li['hours'] > 0;
      $client_ok = ($selected_client_ref === '') || ((string)($li['client_ref'] ?? '') === $selected_client_ref);
      return $hours_ok && $client_ok;
    }));

    $day_total = 0.0;
    foreach ($visible_items as $li) {
      $day_total += (float)($li['hours'] ?? 0);
    }
    $day_total = round($day_total, 2);

    if ($day_total <= 0) {
      continue;
    }

    $days[] = [
      'date' => $d['date'],
      'day_name' => $d['day_name'],
      'hours' => $day_total,
      'line_items' => $visible_items,
    ];

    $week_total += $day_total;
  }

  $week_total = round($week_total, 2);

  if (!empty($days)) {
    $filtered_weeks[] = [
      'wc' => $w['wc'],
      'days' => $days,
      'total' => $week_total,
    ];
    $filtered_month_total += $week_total;
  }
}

$filtered_month_total = round($filtered_month_total, 2);
?>

<div class="section">
  <div class="section-title"><?= $range_title ?></div>
  <div class="section-hint">
    Invoice #<?= (int)$invoice_id ?> · Paralegal: <strong><?= e($paralegal['full_name'] ?? '') ?></strong><br>
    This summary is based strictly on invoice items for audit consistency.
  </div>

  <div style="margin:10px 0; display:flex; gap:10px; flex-wrap:wrap;">
    <a class="btn btn-sm" href="/e/paralegal_invoice.php?id=<?= (int)$invoice_id ?>">Back to Invoice</a>
    <a class="btn btn-sm" href="/e/paralegal_invoice_download.php?id=<?= (int)$invoice_id ?>&print=1" target="_blank">Print Invoice</a>
  </div>

  <form method="get" style="margin:10px 0 16px; display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
    <input type="hidden" name="id" value="<?= (int)$invoice_id ?>">

    <div>
      <label style="display:block; margin-bottom:6px;">Client Ref</label>
      <select name="client_ref">
        <option value="">All client refs</option>
        <?php foreach ($client_refs as $ref): ?>
          <option value="<?= e($ref) ?>" <?= $selected_client_ref === $ref ? 'selected' : '' ?>>
            <?= e($ref) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button class="btn btn-sm" type="submit">Filter</button>
    <a class="btn btn-sm" href="/e/timesheets_invoice_summary.php?id=<?= (int)$invoice_id ?>">Clear</a>
  </form>

  <?php if (empty($filtered_weeks)): ?>
    <div class="section" style="margin-top:16px;">
      <div class="section-hint">No line items found for the selected client ref.</div>
    </div>
  <?php endif; ?>

  <?php foreach ($filtered_weeks as $w): ?>
    <div class="section" style="margin-top:16px;">
      <div class="section-title">WC <?= e(date('d.m.y', strtotime((string)$w['wc']))) ?></div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Day</th>
              <th>Date</th>
              <th style="text-align:right;">Time (Hours)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($w['days'] as $d): ?>
              <tr>
                <td><?= e($d['day_name']) ?></td>
                <td><?= e(date('d.m.y', strtotime((string)$d['date']))) ?></td>
                <td style="text-align:right;"><?= $fmt_hours($d['hours']) ?></td>
              </tr>

              <?php if (!empty($d['line_items'])): ?>
                <tr>
                  <td colspan="3" style="background:#fafafa; padding:0;">
                    <div style="padding:10px 12px; color:#111827;">
                      <div style="font-weight:600; margin-bottom:8px; color:#111827;">Line items</div>

                      <div class="table-responsive">
                        <table class="table" style="margin:0; background:#fafafa; color:#111827;">
                          <thead>
                            <tr>
                              <th style="color:#111827; border-bottom:1px solid #d1d5db;">Client Ref</th>
                              <th style="color:#111827; border-bottom:1px solid #d1d5db;">Description</th>
                              <th style="text-align:right; color:#111827; border-bottom:1px solid #d1d5db;">Hours</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($d['line_items'] as $li): ?>
                              <tr>
                                <td style="color:#111827; border-bottom:1px solid #e5e7eb;">
                                  <?= e(($li['client_ref'] ?? '') !== '' ? $li['client_ref'] : '—') ?>
                                </td>
                                <td style="color:#111827; border-bottom:1px solid #e5e7eb;">
                                  <?= e(($li['description'] ?? '') !== '' ? $li['description'] : '—') ?>
                                </td>
                                <td style="text-align:right; color:#111827; border-bottom:1px solid #e5e7eb;">
                                  <?= $fmt_hours($li['hours'] ?? 0) ?>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>

            <tr>
              <td colspan="2"><strong>Total</strong></td>
              <td style="text-align:right;"><strong><?= $fmt_hours($w['total']) ?></strong></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="section" style="margin-top:18px;">
    <div class="section-title">Total period</div>
    <div style="font-size:16px;"><strong><?= $fmt_hours($filtered_month_total) ?></strong></div>
  </div>
</div>