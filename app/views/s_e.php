<div class="section">
  <div class="section-title">
    <h1>Employer sign up</h1>
    <p>Create an employer account to post jobs and manage timesheets.</p>
  </div>

  <form method="post" autocomplete="off">
    <label>Full name</label>
    <div class="field">
      <input type="text" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
    </div>

    <label>Email</label>
    <div class="field">
      <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
    </div>

    <label>Mobile (optional)</label>
    <div class="field">
      <input type="text" name="mobile" value="<?= e($_POST['mobile'] ?? '') ?>" placeholder="Coming soon">
    </div>

    <label>Firm name</label>
    <div class="field">
      <input type="text" name="firm_name" required value="<?= e($_POST['firm_name'] ?? '') ?>">
    </div>

    <label>Area of law (optional)</label>
    <div class="field">
      <select name="area_of_law">
        <option value="">-- Select --</option>
        <?php foreach($specialisms as $s): $val=$s['specialism']; ?>
          <option value="<?= e($val) ?>" <?= (($_POST['area_of_law'] ?? '')===$val)?'selected':'' ?>><?= e($val) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- ✅ NEW: Location -->
    <label>Location (optional)</label>
    <div class="field">
      <input type="text" name="location"
             value="<?= e($_POST['location'] ?? '') ?>"
             placeholder="e.g. London, Remote, Hybrid">
    </div>

  

    <label style="display:flex;align-items:center;gap:10px;margin-top:12px;">
      <input type="checkbox" name="terms" value="1" <?= !empty($_POST['terms'])?'checked':'' ?> required>
      <span>I agree to the Terms & Conditions and non-circumvention obligations.</span>
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
