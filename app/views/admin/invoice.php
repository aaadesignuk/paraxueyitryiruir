<div class="section">
  <div class="section-title">
    <h1>Invoice #<?= (int)$inv['invoice_id'] ?></h1>
    <p>
      <strong>Employer:</strong> <?= e($employer['full_name'] ?? ('User #'.(int)$inv['employer_id'])) ?>
      &nbsp;•&nbsp;
      <strong>Paralegal:</strong> <?= e($paralegal['full_name'] ?? ('User #'.(int)$inv['paralegal_id'])) ?>
      &nbsp;•&nbsp;
      <strong>Period:</strong> <?= e(uk_date($inv['period_start'] ?? '')) ?> to <?= e(uk_date($inv['period_end'] ?? '')) ?>
      &nbsp;•&nbsp;
      <strong>Status:</strong> <?= e($inv['status']) ?>
    </p>
  </div>

  <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
    <a class="btn" href="/a/invoice_download.php?id=<?= (int)$inv['invoice_id'] ?>&print=1" target="_blank">Download invoice</a>

    <button class="btn" type="button"
      onclick="(function(){
        var w = window.open('/a/invoice_download.php?id=<?= (int)$inv['invoice_id'] ?>&print=1', '_blank');
        if(!w) return;
        var tries = 0;
        var t = setInterval(function(){
          tries++;
          try {
            if (w.document && w.document.readyState === 'complete') {
              clearInterval(t);
              w.focus();
              w.print();
            }
          } catch(e) {}
          if (tries > 40) clearInterval(t);
        }, 250);
      })();">
      Print / Save as PDF
    </button>

    <a class="btn" href="/a/billing.php">← Back to billing</a>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Job</th>
        <th>Hours</th>
        <th>Rate</th>
        <th>Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($items as $it): ?>
        <tr>
          <td><?= e($it['job_title']) ?></td>
          <td><?= number_format((float)$it['hours'], 2) ?></td>
          <td>£<?= number_format((float)$it['hourly_rate'], 2) ?></td>
          <td>£<?= number_format((float)$it['amount'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th>Total</th>
        <th><?= number_format((float)$inv['total_hours'], 2) ?></th>
        <th></th>
        <th>£<?= number_format((float)$inv['gross_amount'], 2) ?></th>
      </tr>
    </tfoot>
  </table>
</div>
