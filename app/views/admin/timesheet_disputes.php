<div class="section">
  <div class="section-title">Timesheet Disputes</div>
  <div class="section-hint">Admin must resolve disputes by the 3rd of the month so invoices can move forward.</div>

  <div style="display:flex; gap:10px; margin:12px 0; flex-wrap:wrap;">
    <a class="btn small <?= (($show ?? 'open') === 'open') ? '' : 'secondary' ?>" href="/a/timesheet_disputes.php?show=open" style="width:auto;">Open</a>
    <a class="btn small <?= (($show ?? '') === 'resolved') ? '' : 'secondary' ?>" href="/a/timesheet_disputes.php?show=resolved" style="width:auto;">Resolved</a>
    <a class="btn small <?= (($show ?? '') === 'all') ? '' : 'secondary' ?>" href="/a/timesheet_disputes.php?show=all" style="width:auto;">All</a>
  </div>

  <div class="table-wrap">
    <table class="table">
      <tr>
        <th>ID</th>
        <th>Employer</th>
        <th>Paralegal</th>
        <th>Date</th>
        <th>Original hours</th>
        <th>Payable hours</th>
        <th>Status</th>
        <th></th>
      </tr>
      <?php if (!$disputes): ?>
        <tr><td colspan="8">No disputes.</td></tr>
      <?php endif; ?>
      <?php foreach ($disputes as $d): ?>
        <tr>
          <td><?= e($d['dispute_id']) ?></td>
          <td><?= e($d['employer_name']) ?></td>
          <td><?= e($d['paralegal_name']) ?></td>
          <td><?= e(uk_date($d['work_date'])) ?></td>
          <td><?= number_format((float)$d['hours_worked'], 2) ?></td>
          <td><?= ($d['payable_hours'] !== null && $d['payable_hours'] !== '') ? number_format((float)$d['payable_hours'], 2) : '—' ?></td>
          <td><?= e($d['status']) ?></td>
          <td style="text-align:right;"><a class="btn micro" href="/a/timesheet_dispute_resolve.php?id=<?= (int)$d['dispute_id'] ?>"><?= (($d['status'] ?? '') === 'Resolved') ? 'View' : 'Resolve' ?></a></td>
        </tr>
        <tr>
          <td colspan="8" style="opacity:.85;"><strong>Appeal:</strong> <?= e($d['dispute_preview']) ?><?= (mb_strlen((string)$d['dispute_preview']) >= 220 ? '…' : '') ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
