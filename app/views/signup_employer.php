<?php
// app/views/signup_employer.php
?>

<div class="section">
  <div class="section-title">
    <h1>Employer sign up</h1>
    <p>Create an employer account to post jobs and manage timesheets.</p>
  </div>

  <?php if (function_exists('flash_render')) { flash_render(); } ?>

  <form method="post" autocomplete="off">

    <label>Full name</label>
    <div class="field">
      <input type="text" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
    </div>

    <label>Email</label>
    <div class="field">
      <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
    </div>

    <label>Tel Number</label>
    <div class="field">
      <input type="text" name="mobile" value="<?= e($_POST['mobile'] ?? '') ?>" placeholder="">
    </div>

    <label>Firm name</label>
    <div class="field">
      <input type="text" name="firm_name" required value="<?= e($_POST['firm_name'] ?? '') ?>">
    </div>

    <label>Location (optional)</label>
    <div class="field">
      <input type="text" name="location"
             value="<?= e($_POST['location'] ?? '') ?>"
             placeholder="e.g. London, Remote, Hybrid">
    </div>

    <label>Specialism</label>
    <div class="field">
      <select name="specialism" id="specialism" required>
        <option value="">-- Select --</option>
        <?php foreach($specialisms as $s): $val=$s['specialism']; ?>
          <option value="<?= e($val) ?>" <?= (($_POST['specialism'] ?? '')===$val)?'selected':'' ?>><?= e($val) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <label>Sub-specialism</label>
    <div class="field">
      <select name="sub_specialism" id="sub_specialism" disabled>
        <option value="">-- Select --</option>
        <?php foreach(($subs_all ?? []) as $row):
          $sp = $row['specialism'] ?? '';
          $sub = $row['sub_specialism'] ?? '';
          if ($sub === '') continue;
        ?>
          <option value="<?= e($sub) ?>"
                  data-spec="<?= e($sp) ?>"
                  <?= (($_POST['sub_specialism'] ?? '')===$sub)?'selected':'' ?>>
            <?= e($sub) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <label style="display:flex;align-items:center;gap:10px;margin-top:12px;">
      <input type="checkbox" name="terms" value="1" <?= !empty($_POST['terms'])?'checked':'' ?> required>
      <span>I agree to the <a href="/terms-employers.html" target="_blank" style="text-decoration:underline;">Terms &amp; Conditions</a> and non-circumvention obligations.</span>
    </label>

    <div style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Password</label>
        <div class="field">
          <input type="password" name="password" required minlength="8">
        </div>
      </div>
      <div>
        <label>Confirm password</label>
        <div class="field">
          <input type="password" name="password_confirm" required minlength="8">
        </div>
      </div>
    </div>

    <div style="margin-top:16px;display:flex;gap:10px;align-items:center;">
      <button class="btn" type="submit">Create account</button>
      <a href="/login.php" style="opacity:.85">Back to login</a>
    </div>

  </form>
</div>

<script>
(function(){
  const spec = document.getElementById('specialism');
  const sub  = document.getElementById('sub_specialism');

  function filterSubs(){
    const v = spec ? (spec.value || '') : '';
    if (!sub) return;

    Array.from(sub.options).forEach(o => {
      if (!o.value) { o.hidden = false; return; }
      const os = o.getAttribute('data-spec') || '';
      o.hidden = (v && os !== v);
    });

    sub.disabled = !v;

    const selected = sub.options[sub.selectedIndex];
    if (selected && selected.hidden) sub.value = '';
    if (sub.disabled) sub.value = '';
  }

  if (spec && sub){
    spec.addEventListener('change', filterSubs);
    filterSubs();
  }
})();
</script>
