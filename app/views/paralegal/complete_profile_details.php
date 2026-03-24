<div class="section">
  <div class="section-title">
    <h1>Complete Profile</h1>
    <p>Select the task categories you can support and add your experience (Hours or Years). Then tick any sub-activities you’re comfortable with.</p>
  </div>

  <form method="post" autocomplete="off" style="padding:12px 6px;">
    <div style="margin:10px 0 14px; padding:12px; border:1px solid #e6e6e6; border-radius:10px; opacity:.95;">
      <strong>Tip:</strong> Completing more categories improves matching accuracy.
    </div>

    <?php
      $byCat = [];
      foreach ($task_activities as $a) { $byCat[(int)$a['category_id']][] = $a; }
      $selActsStr = array_map('strval', $selected_activity_ids);
    ?>

    <?php foreach($task_categories as $c): $cid=(int)$c['id']; ?>
      <?php
        $hasExp = isset($catExpMap[$cid]);
        $etype = $hasExp ? ($catExpMap[$cid]['experience_type'] ?? 'Years') : 'Years';
        $eval  = $hasExp ? ($catExpMap[$cid]['experience_value'] ?? '') : '';
      ?>
      <div style="margin:14px 0; padding:14px; border:1px solid #e6e6e6; border-radius:12px;">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
          <div style="font-weight:800;font-size:16px;"><?= e($c['name']) ?></div>
        </div>

        <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-top:10px;">
          <div>
            <label>Experience type</label>
            <div class="field">
              <select name="categories[<?= $cid ?>][experience_type]">
                <option value="Years" <?= ($etype==='Years'?'selected':'') ?>>Years</option>
                <option value="Hours" <?= ($etype==='Hours'?'selected':'') ?>>Hours</option>
              </select>
            </div>
          </div>

          <div>
            <label>Experience value</label>
            <div class="field">
              <input type="number" step="0.01" min="0"
                     name="categories[<?= $cid ?>][experience_value]"
                     value="<?= e($eval) ?>"
                     placeholder="Leave blank if not applicable">
            </div>
          </div>
        </div>

        <?php if (!empty($byCat[$cid])): ?>
          <div style="margin-top:10px; opacity:.9;">
            <div style="font-weight:700; margin-bottom:6px;">Sub-activities (optional)</div>
            <div class="check-grid">
              <?php foreach(($byCat[$cid] ?? []) as $a): ?>
                <label class="check-item">
                  <input type="checkbox" name="activities[]" value="<?= (int)$a['id'] ?>"
                    <?= in_array((string)$a['id'], $selActsStr, true) ? 'checked' : '' ?>>
                  <span><?= e($a['name']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div style="margin-top:14px;display:flex;gap:12px;align-items:center;">
      <button class="btn" type="submit" style="min-width:180px;">Submit profile</button>
      <a class="btn secondary" href="/p/dashboard.php" style="min-width:120px;">Back</a>
    </div>
  </form>
</div>
