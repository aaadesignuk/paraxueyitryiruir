<?php
// app/views/signup_paralegal.php
?>

<div class="section">
  <div class="section-title">
    <h1>Paralegal sign up</h1>
  </div>

  <form method="post" enctype="multipart/form-data" autocomplete="off">

    <label>Full name</label>
    <div class="field">
      <input type="text" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
    </div>

    <label>Email</label>
    <div class="field">
      <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
    </div>

    <!-- Password: input left, instructions right (vertically centred) -->
<div style="margin-top:14px;">
  <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;margin-bottom:6px;">
    <label style="margin:0;">Password</label>
    <span style="opacity:.85;font-size:13px;">Minimum 8 characters</span>
  </div>
  <div class="field">
    <input type="password" name="password" required minlength="8">
  </div>
</div>



    <!-- Specialism 1 + Sub -->
    <div style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Specialism 1</label>
        <div class="field">
          <select name="specialism" id="p_spec1" required>
            <option value="">-- Select --</option>
            <?php foreach($specialisms as $s): $val=$s['specialism']; ?>
              <option value="<?= e($val) ?>" <?= (($_POST['specialism'] ?? '')===$val)?'selected':'' ?>>
                <?= e($val) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div>
        <label>Sub-specialism 1</label>
        <div class="field">
       <select name="sub_specialism" id="p_sub1" disabled>
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
      </div>
    </div>

    <!-- Specialism 2 + Sub (optional) -->
    <div style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Specialism 2 (optional)</label>
        <div class="field">
          <select name="specialism2" id="p_spec2">
            <option value="">-- Select --</option>
            <?php foreach($specialisms as $s): $val=$s['specialism']; ?>
              <option value="<?= e($val) ?>" <?= (($_POST['specialism2'] ?? '')===$val)?'selected':'' ?>>
                <?= e($val) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div>
        <label>Sub-specialism 2 (optional)</label>
        <div class="field">
          <select name="sub_specialism2" id="p_sub2" disabled>
            <option value="">-- Select --</option>
            <?php foreach(($subs_all ?? []) as $row):
              $sp = $row['specialism'] ?? '';
              $sub = $row['sub_specialism'] ?? '';
              if ($sub === '') continue;
            ?>
              <option value="<?= e($sub) ?>"
                      data-spec="<?= e($sp) ?>"
                      <?= (($_POST['sub_specialism2'] ?? '')===$sub)?'selected':'' ?>>
                <?= e($sub) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Experience type/value -->
    <div style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Experience type</label>
        <div class="field">
          <?php $et = $_POST['experience_type'] ?? 'Years'; ?>
          <select name="experience_type">
            <option value="Days"   <?= ($et==='Days')?'selected':'' ?>>Days</option>
            <option value="Months" <?= ($et==='Months')?'selected':'' ?>>Months</option>
            <option value="Years"  <?= ($et==='Years')?'selected':'' ?>>Years</option>
          </select>
        </div>
      </div>

      <div>
        <label>Experience value</label>
        <div class="field">
          <input type="text" name="experience_value"
                 value="<?= e($_POST['experience_value'] ?? '') ?>"
                 placeholder="e.g. 6">
        </div>
      </div>
    </div>

    <!-- Preferred rate + availability -->
    <div style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Preferred rate (£/hr)</label>
        <div class="field">
          <input type="text" name="preferred_rate"
                 value="<?= e($_POST['preferred_rate'] ?? '') ?>"
                 placeholder="e.g. 25">
        </div>
      </div>

      <div>
        <label>Availability right now</label>
        <div class="field">
          <?php $av = $_POST['is_available'] ?? '1'; ?>
          <select name="is_available">
            <option value="1" <?= ($av==='1')?'selected':'' ?>>Available</option>
            <option value="0" <?= ($av==='0')?'selected':'' ?>>Not available</option>
          </select>
        </div>
      </div>
    
    <!-- Base location (required for domestic travel semantics) -->
    
		
        <div>
          <label>Country</label>
          <div class="field">
            <?php $bc = $_POST['base_country'] ?? ''; ?>
            <select name="base_country" id="base_country" required>
              <option value="">-- Select --</option>
              <?php
                $countries = ['United Kingdom', 'United States', 'Canada', 'Ireland', 'Australia', 'France', 'Germany', 'Netherlands', 'Spain', 'Italy', 'Switzerland', 'United Arab Emirates', 'Singapore', 'Hong Kong', 'India', 'South Africa', 'Other'];
                foreach ($countries as $c):
              ?>
                <option value="<?= e($c) ?>" <?= ($bc===$c)?'selected':'' ?>><?= e($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div id="base_country_other_wrap" style="display:none;">
          <label>Country (other)</label>
          <div class="field">
            <input type="text" name="base_country_other" value="<?= e($_POST['base_country_other'] ?? '') ?>" placeholder="Enter your country">
          </div>
        </div>

        <div id="base_state_wrap" style="display:none;">
          <label>State (US)</label>
          <div class="field">
            <?php $bs = $_POST['base_state'] ?? ''; ?>
            <select name="base_state" id="base_state">
              <option value="">-- Select --</option>
              <?php
                $states = ['Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'District of Columbia', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'];
                foreach ($states as $st):
              ?>
                <option value="<?= e($st) ?>" <?= ($bs===$st)?'selected':'' ?>><?= e($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label>City</label>
          <div class="field">
            <input type="text" name="base_city" id="base_city" required value="<?= e($_POST['base_city'] ?? '') ?>" placeholder="e.g. London">
          </div>
        </div>
      </div>

      <div style="margin-top:12px;display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
          <label>Postcode / ZIP (optional)</label>
          <div class="field">
            <input type="text" name="base_postcode" value="<?= e($_POST['base_postcode'] ?? '') ?>" placeholder="e.g. NW2 6GQ">
          </div>
        </div>
        <div>
          <label>Address line 1 (optional)</label>
          <div class="field">
            <input type="text" name="base_address1" value="<?= e($_POST['base_address1'] ?? '') ?>" placeholder="Street address">
          </div>
        </div>
      </div>

      <div style="margin-top:12px;">
        <label>Address line 2 (optional)</label>
        <div class="field">
          <input type="text" name="base_address2" value="<?= e($_POST['base_address2'] ?? '') ?>" placeholder="Apartment, suite, etc.">
     
			
    </div>

</div>

    <!-- Upload CV: input left, help right (vertically centred) -->
 <div style="margin-top:14px;">
  <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;margin-bottom:6px;">
    <label style="margin:0;">Upload CV (optional)</label>
    <span style="opacity:.85;font-size:13px;">PDF / DOC / DOCX accepted</span>
  </div>
  <div class="field">
    <input type="file" name="cv" accept=".pdf,.doc,.docx">
  </div>
</div>


    <label style="display:flex;align-items:center;gap:10px;margin-top:16px;">
      <input type="checkbox" name="terms" value="1" <?= !empty($_POST['terms'])?'checked':'' ?> required>
      <span>I accept the <a href="/terms-paralegals.html" target="_blank" rel="noopener">Terms &amp; Conditions</a>.
</span>
    </label>

    <label style="display:flex;align-items:center;gap:10px;margin-top:10px;">
      <input type="checkbox" name="authorised_only" value="1" <?= !empty($_POST['authorised_only'])?'checked':'' ?> required>
      <span>I confirm I will only assist authorised lawyers.</span>
    </label>

    <div style="margin-top:16px;display:flex;gap:10px;align-items:center;">
      <button class="btn" type="submit">Create accounts</button>
      <a href="/login.php" style="opacity:.85">Back to login</a>
    </div>

  </form>
</div>


<script>
(function(){
  function hasSubsForSpec(subSelect, specVal){
    return Array.from(subSelect.options).some(o => {
      if (!o.value) return false;
      return (o.getAttribute('data-spec') || '') === specVal;
    });
  }

  function ensureNoneOption(subSelect){
    let none = subSelect.querySelector('option[data-none="1"]');
    if (!none) {
      none = document.createElement('option');
      none.value = '';
      none.textContent = '— None —';
      none.setAttribute('data-none','1');
      subSelect.insertBefore(none, subSelect.options[1] || null);
    }
    return none;
  }

  function wire(specId, subId, optional){
    const spec = document.getElementById(specId);
    const sub  = document.getElementById(subId);
    if (!spec || !sub) return;

    function filter(){
      const v = spec.value || '';

      // Hide options that don't match selected specialism
      Array.from(sub.options).forEach(o => {
        if (!o.value) { o.hidden = false; return; }
        const os = o.getAttribute('data-spec') || '';
        o.hidden = (v && os !== v);
      });

      if (!v) {
        // No specialism selected
        sub.value = '';
        sub.disabled = true;
        sub.required = false;
        return;
      }

      // Specialism chosen: does it have subs?
      const hasSubs = hasSubsForSpec(sub, v);

      if (!hasSubs) {
        // No subs: show "None", disable, not required
        ensureNoneOption(sub);
        sub.value = '';
        sub.disabled = true;
        sub.required = false;
        return;
      }

      // Has subs: enable
      // Remove "None" option if present (optional)
      const noneOpt = sub.querySelector('option[data-none="1"]');
      if (noneOpt) noneOpt.remove();

      sub.disabled = false;
      sub.required = !optional; // required for spec1, optional for spec2

      // If current selection is hidden (from old spec), clear it
      const selected = sub.options[sub.selectedIndex];
      if (selected && selected.hidden) sub.value = '';
    }

    spec.addEventListener('change', filter);
    filter();
  }

  wire('p_spec1','p_sub1', false);
  wire('p_spec2','p_sub2', true);

  function toggleBaseCountry(){
    const c = (document.getElementById('base_country')||{}).value || '';
    const other = document.getElementById('base_country_other_wrap');
    const stw = document.getElementById('base_state_wrap');
    const st = document.getElementById('base_state');
    const city = document.getElementById('base_city');

    if (other) other.style.display = (c === 'Other') ? 'block' : 'none';
    if (stw) stw.style.display = (c === 'United States') ? 'block' : 'none';
    if (st) st.required = (c === 'United States');

    // City required unless US + State selected
    if (city) {
      const cityRequired = !(c === 'United States' && st && st.value);
      city.required = cityRequired;
    }
  }

  const bc = document.getElementById('base_country');
  const st = document.getElementById('base_state');
  if (bc) bc.addEventListener('change', toggleBaseCountry);
  if (st) st.addEventListener('change', toggleBaseCountry);
  toggleBaseCountry();
})();
</script>

