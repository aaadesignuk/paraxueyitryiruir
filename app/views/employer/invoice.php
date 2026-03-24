<div class="section">
  <div class="section-title">
    <h1>Commission Invoice #<?= (int)$inv['invoice_id'] ?></h1>
    <p>Period: <?= e(uk_date($inv['period_start'] ?? '')) ?> to <?= e(uk_date($inv['period_end'] ?? '')) ?></p>
  </div>

  <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
    <a class="btn" href="/e/commission_invoice_download.php?id=<?= (int)$inv['invoice_id'] ?>&print=1" target="_blank">
      Download invoice
    </a>

    <button class="btn" type="button"
      onclick="(function(){
        var w = window.open('/e/commission_invoice_download.php?id=<?= (int)$inv['invoice_id'] ?>&print=1', '_blank');
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

    <a class="btn" href="/e/billing.php">← Back to billing</a>
  </div>

  <div style="margin-bottom:16px;">
    <strong>Total gross value:</strong> £<?= number_format((float)$inv['gross_amount'], 2) ?><br>
    <strong>Commission (<?= number_format((float)$inv['commission_rate'], 2) ?>%):</strong>
    £<?= number_format((float)$inv['commission_amount'], 2) ?><br>
    <strong>Status:</strong> <?= e($inv['status']) ?>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Client ref</th>
        <th>Job</th>
        <th>Paralegal</th>
             <th>Hours</th>
        <th>Rate</th>
        <th>Line</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($items as $it): ?>
        <tr>
          <td><?= e(uk_date($it['work_date'] ?? '')) ?></td>
          <td><?= e($it['client_ref'] ?? '') ?></td>
          <td><?= e($it['job_title'] ?? '') ?></td>
          <td><?= e($it['paralegal_name']) ?></td>
           <td><?= number_format((float)$it['hours_worked'], 2) ?></td>
          <td>£<?= number_format((float)$it['hourly_rate'], 2) ?></td>
          <td>£<?= number_format((float)$it['line_amount'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p style="margin-top:16px;opacity:.8;">
    Please pay this commission invoice directly to Paralete using the payment instructions provided in your agreement.
  </p>
</div>
