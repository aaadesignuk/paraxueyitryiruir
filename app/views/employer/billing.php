<div class="section">
  <div class="section-title">
    <h1>Billing</h1>
    <p>Paralegal invoices (pay paralegal directly) + commission invoices (pay Paralete).</p>
  </div>

  <h2 style="margin-top:16px;">Paralegal Invoices</h2>
  <table class="table">
    <thead>
      <tr>
        <th>Paralegal</th>
        <th>Period</th>
        <th>Hours</th>
        <th>Total</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if(empty($paralegal_invoices)): ?>
        <tr><td colspan="6">No paralegal invoices yet.</td></tr>
      <?php endif; ?>

      <?php foreach($paralegal_invoices as $inv): ?>
        <tr>
          <td><?= e($inv['paralegal_name']) ?></td>
          <td><?= e(uk_date($inv['period_start'] ?? '')) ?> to <?= e(uk_date($inv['period_end'] ?? '')) ?></td>
          <td><?= number_format((float)$inv['total_hours'], 2) ?></td>
          <td>£<?= number_format((float)$inv['gross_amount'], 2) ?></td>
          <td><?= e($inv['status']) ?></td>

          <td style="max-width:320px;">
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center; justify-content:flex-start;">
              <a class="btn"
                 href="/e/paralegal_invoice.php?id=<?= (int)$inv['invoice_id'] ?>"
                 style="width:auto; display:inline-flex; align-items:center; justify-content:center; white-space:nowrap;">
                View invoice
              </a>

              <?php if (!empty($inv['commission_invoice_id'])): ?>
                <a class="btn"
                   href="/e/invoice.php?id=<?= (int)$inv['commission_invoice_id'] ?>"
                   style="width:auto; display:inline-flex; align-items:center; justify-content:center; white-space:nowrap;">
                  Commission
                </a>
              <?php endif; ?>
            </div>
          </td>

        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2 style="margin-top:22px;">Commission Invoices</h2>
  <table class="table">
    <thead>
      <tr>
        <th>Period</th>
        <th>Gross Value</th>
        <th>Commission %</th>
        <th>Commission Due</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if(empty($commission_invoices)): ?>
        <tr><td colspan="6">No commission invoices yet.</td></tr>
      <?php endif; ?>

      <?php foreach($commission_invoices as $inv): ?>
        <tr>
          <td><?= e(uk_date($inv['period_start'] ?? '')) ?> to <?= e(uk_date($inv['period_end'] ?? '')) ?></td>
          <td>£<?= number_format((float)$inv['gross_amount'], 2) ?></td>
          <td><?= number_format((float)$inv['commission_rate'], 2) ?>%</td>
          <td><strong>£<?= number_format((float)$inv['commission_amount'], 2) ?></strong></td>
          <td><?= e($inv['status']) ?></td>

          <td style="max-width:220px;">
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
              <a class="btn"
                 href="/e/invoice.php?id=<?= (int)$inv['invoice_id'] ?>"
                 style="width:auto; display:inline-flex; align-items:center; justify-content:center; white-space:nowrap;">
                View invoice
              </a>
            </div>
          </td>

        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
