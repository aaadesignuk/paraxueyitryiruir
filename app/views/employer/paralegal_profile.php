<div class="section">
  <div class="section-title">Paralegal Profile</div>
  <div class="muted-line" style="margin-top:4px;"><strong><?= e($u['full_name'] ?? '-') ?></strong> • <?= e($u['email'] ?? '') ?></div>

  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-top:14px;">
    <div style="padding:12px; border:1px solid rgba(255,255,255,.08); border-radius:14px; background:rgba(255,255,255,.03);">
      <div style="opacity:.75">Specialism</div>
      <div style="font-weight:600; margin-top:4px;"><?= e($p['specialism'] ?? '-') ?></div>
    </div>

    <div style="padding:12px; border:1px solid rgba(255,255,255,.08); border-radius:14px; background:rgba(255,255,255,.03);">
      <div style="opacity:.75">Experience</div>
      <div style="font-weight:600; margin-top:4px;">
        <?php
          $et = $p['experience_type'] ?? 'None';
          $ev = $p['experience_value'] ?? null;
          echo e($et === 'Years' ? ($ev ? $ev.' years' : '-') : ($et === 'Hours' ? ($ev ? $ev.' hours' : '-') : '-'));
        ?>
      </div>
    </div>

    <div style="padding:12px; border:1px solid rgba(255,255,255,.08); border-radius:14px; background:rgba(255,255,255,.03);">
      <div style="opacity:.75">Rates</div>
      <div style="margin-top:6px; line-height:1.6;">
        <div><strong>Standard:</strong> <?= isset($p['preferred_rate']) ? '£'.e($p['preferred_rate']).'/hr' : '-' ?></div>
        <div style="opacity:.9;"><strong>Urgent:</strong> <?= !empty($p['preferred_rate_urgent']) ? '£'.e($p['preferred_rate_urgent']).'/hr' : '—' ?></div>
        <div style="opacity:.9;"><strong>Overnight:</strong> <?= !empty($p['preferred_rate_overnight']) ? '£'.e($p['preferred_rate_overnight']).'/hr' : '—' ?></div>
        <div style="opacity:.9;"><strong>Specialist:</strong> <?= !empty($p['preferred_rate_specialist']) ? '£'.e($p['preferred_rate_specialist']).'/hr' : '—' ?></div>
      </div>
    </div>

    <div style="padding:12px; border:1px solid rgba(255,255,255,.08); border-radius:14px; background:rgba(255,255,255,.03);">
      <div style="opacity:.75">Location preference</div>
      <div style="font-weight:600; margin-top:4px;"><?= e($p['location_preference'] ?? '-') ?></div>
    </div>
  </div>

  <?php if(!empty($p['skills'])): ?>
    <div style="margin-top:14px; padding:12px; border:1px solid rgba(255,255,255,.08); border-radius:14px; background:rgba(255,255,255,.03);">
      <div style="opacity:.75">Skills</div>
      <div style="margin-top:6px; white-space:pre-wrap;"><?= e($p['skills']) ?></div>
    </div>
  <?php endif; ?>
</div>

<div class="section">
  <div class="section-title">Task categories & expertise</div>
  <div class="section-hint">Self-declared experience levels by category.</div>

  <table class="table" style="margin-top:10px;">
    <tr><th>Category</th><th>Experience</th></tr>
    <?php if(empty($cats)): ?>
      <tr><td colspan="2">No category experience added yet.</td></tr>
    <?php endif; ?>
    <?php foreach($cats as $c): ?>
      <tr>
        <td><?= e($c['category_name'] ?? '-') ?></td>
        <td>
          <?php
            $t = $c['experience_type'] ?? 'None';
            $v = $c['experience_value'] ?? null;
            echo e($t === 'Years' ? ($v ? $v.' years' : '-') : ($t === 'Hours' ? ($v ? $v.' hours' : '-') : ($t === 'Count' ? ($v ? $v.' matters' : '-') : '-')));
          ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <div style="margin-top:12px;">
    <a href="javascript:history.back()" style="opacity:.85;">← Back</a>
  </div>
</div>
