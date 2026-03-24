<?php
// app/views/paralegal/profile_details.php
// expects: $profile, $days, $availability, $skillsByCategory, $selectedSkillIds
?>

<div class="section">
  <div class="section-title">Profile details</div>
  <p style="margin-top:6px;opacity:.85;">Keep your profile accurate for better matching.</p>

  <form method="post" enctype="multipart/form-data" style="margin-top:14px;">

    <div id="base-location"></div>

    <!-- Base location -->
    <div style="margin-top:22px;">
      <div style="font-weight:800;margin-bottom:8px;">Location</div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; max-width:980px;">
        <div>
          <label style="display:block;font-weight:700;margin-bottom:6px;">Country *</label>
          <input class="input" style="width:100%; height:44px;" name="base_country"
            value="<?= e((string)($profile['base_country'] ?? '')) ?>" placeholder="e.g. United Kingdom">
        </div>

        <div>
          <label style="display:block;font-weight:700;margin-bottom:6px;">City *</label>
          <input class="input" style="width:100%; height:44px;" name="base_city"
            value="<?= e((string)($profile['base_city'] ?? '')) ?>" placeholder="e.g. London">
        </div>

        <div>
          <label style="display:block;font-weight:700;margin-bottom:6px;">State (USA only)</label>
          <input class="input" style="width:100%; height:44px;" name="base_state"
            value="<?= e((string)($profile['base_state'] ?? '')) ?>" placeholder="e.g. California">
        </div>

        <div>
          <label style="display:block;font-weight:700;margin-bottom:6px;">Postcode (optional)</label>
          <input class="input" style="width:100%; height:44px;" name="base_postcode"
            value="<?= e((string)($profile['base_postcode'] ?? '')) ?>">
        </div>

        <div style="grid-column:1/-1;">
          <label style="display:block;font-weight:700;margin-bottom:6px;">Address (optional)</label>
          <input class="input" style="width:100%; height:44px;" name="base_address1"
            value="<?= e((string)($profile['base_address1'] ?? '')) ?>" placeholder="Address line 1">
          <div style="height:10px;"></div>
          <input class="input" style="width:100%; height:44px;" name="base_address2"
            value="<?= e((string)($profile['base_address2'] ?? '')) ?>" placeholder="Address line 2">
        </div>
      </div>
    </div>
	  
<!-- Bank details -->
<div style="margin-top:22px; max-width:980px;">
  <div style="font-weight:800;margin-bottom:8px;">Bank details</div>

  <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
    <div>
      <label style="display:block;font-weight:700;margin-bottom:6px;">Bank name</label>
      <input class="input" style="width:100%; height:44px;" name="bank_name"
        value="<?= e((string)($profile['bank_name'] ?? '')) ?>" placeholder="e.g. Barclays">
    </div>

    <div>
      <label style="display:block;font-weight:700;margin-bottom:6px;">Account name</label>
      <input class="input" style="width:100%; height:44px;" name="account_name"
        value="<?= e((string)($profile['account_name'] ?? '')) ?>" placeholder="Must match your name">
      <div style="font-size:12px;opacity:.75;margin-top:6px;">
        Account name must match Paralegal’s name.
      </div>
    </div>

    <div>
      <label style="display:block;font-weight:700;margin-bottom:6px;">Account number</label>
      <input class="input" style="width:100%; height:44px;" name="account_no"
        value="<?= e((string)($profile['account_no'] ?? '')) ?>" placeholder="e.g. 12345678">
    </div>

    <div>
      <label style="display:block;font-weight:700;margin-bottom:6px;">Sort code</label>
      <input class="input" style="width:100%; height:44px;" name="sort_code"
        value="<?= e((string)($profile['sort_code'] ?? '')) ?>" placeholder="e.g. 12-34-56">
    </div>
  </div>
</div>
<!-- /Bank details -->	  
	  

    <!-- Specialism -->
    <div style="margin-top:22px; max-width:980px;">
      <div style="font-weight:800;margin-bottom:8px;">Specialism</div>
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
        <div>
          <label style="display:block;font-weight:700;margin-bottom:6px;">Specialism *</label>
          <select class="input" style="width:100%; height:44px;" name="specialism" id="specialismSelect">
            <option value="">Select…</option>
            <?php $curSp = (string)($profile['specialism'] ?? ''); ?>
            <?php foreach (($specialismOptions ?? []) as $sp): ?>
              <option value="<?= e($sp) ?>" <?= ($curSp === $sp ? 'selected' : '') ?>><?= e($sp) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label style="display:block;font-weight:700;margin-bottom:6px;">Sub-specialism</label>
          <?php $curSub = (string)($profile['sub_specialism'] ?? ''); ?>
          <select class="input" style="width:100%; height:44px;" name="sub_specialism" id="subSpecialismSelect">
            <option value="">Select…</option>
            <?php
              $initialSubs = [];
              if (!empty($curSp) && isset($subsBySpecialism[$curSp]) && is_array($subsBySpecialism[$curSp])) {
                $initialSubs = $subsBySpecialism[$curSp];
              }
            ?>
            <?php foreach ($initialSubs as $ss): ?>
              <option value="<?= e($ss) ?>" <?= ($curSub === $ss ? 'selected' : '') ?>><?= e($ss) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

 
	  
	  <!-- Specialism 2 (optional) -->
<div style="margin-top:14px; display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
  <div>
    <label style="display:block;font-weight:700;margin-bottom:6px;">Specialism 2 (optional)</label>
    <?php $curSp2 = (string)($profile['specialism2'] ?? ''); ?>
    <select class="input" style="width:100%; height:44px;" name="specialism2" id="specialism2Select">
      <option value="">Select…</option>
      <?php foreach (($specialismOptions ?? []) as $sp): ?>
        <option value="<?= e($sp) ?>" <?= ($curSp2 === $sp ? 'selected' : '') ?>><?= e($sp) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label style="display:block;font-weight:700;margin-bottom:6px;">Sub-specialism 2</label>
    <?php $curSub2 = (string)($profile['sub_specialism2'] ?? ''); ?>
    <select class="input" style="width:100%; height:44px;" name="sub_specialism2" id="subSpecialism2Select">
      <option value="">Select…</option>
      <?php
        $initialSubs2 = [];
        if (!empty($curSp2) && isset($subsBySpecialism[$curSp2]) && is_array($subsBySpecialism[$curSp2])) {
          $initialSubs2 = $subsBySpecialism[$curSp2];
        }
      ?>
      <?php foreach ($initialSubs2 as $ss): ?>
        <option value="<?= e($ss) ?>" <?= ($curSub2 === $ss ? 'selected' : '') ?>><?= e($ss) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>
    
    </div>
	  <script>
(function(){
  const subsBySp = <?= json_encode($subsBySpecialism ?? [], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

  function ensureNoneOption(selectEl){
    // Adds a friendly disabled "— None —" option after the placeholder
    let none = selectEl.querySelector('option[data-none="1"]');
    if (!none) {
      none = document.createElement('option');
      none.value = '';
      none.textContent = '— None —';
      none.setAttribute('data-none','1');
      selectEl.appendChild(none);
    }
    return none;
  }

  function wire(spId, subId, isOptionalPair){
    const spSel = document.getElementById(spId);
    const subSel = document.getElementById(subId);
    if (!spSel || !subSel) return;

    function rebuild(){
      const sp = spSel.value || '';
      const subs = Array.isArray(subsBySp[sp]) ? subsBySp[sp] : [];
      const current = subSel.value;

      subSel.innerHTML = '<option value="">Select…</option>';

      if (!sp) {
        // no specialism chosen
        subSel.disabled = true;
        subSel.required = false;
        return;
      }

      if (!subs.length) {
        // specialism has no subs
        ensureNoneOption(subSel);
        subSel.value = '';
        subSel.disabled = true;
        subSel.required = false;
        return;
      }

      // has subs
      subs.forEach(ss => {
        const opt = document.createElement('option');
        opt.value = ss;
        opt.textContent = ss;
        if (current && current === ss) opt.selected = true;
        subSel.appendChild(opt);
      });

      subSel.disabled = false;
      // required only if this specialism has subs; pair1 is required, pair2 enforced server-side only when chosen
      subSel.required = !isOptionalPair;
    }

    spSel.addEventListener('change', function(){
      subSel.value = '';
      rebuild();
    });

    rebuild();
  }

  // Pair 1: specialism required → sub required only when subs exist
  wire('specialismSelect', 'subSpecialismSelect', false);

  // Pair 2: optional pair → we do not force required in browser (server-side enforces when chosen + has subs)
  wire('specialism2Select', 'subSpecialism2Select', true);
})();
</script>

	  

<!-- Task skills -->
<div id="taskskills" style="margin-top:22px;">

  <div style="font-weight:800;margin-bottom:6px;">Task skills</div>
  <p style="opacity:.85; max-width:980px;">
    Select the tasks you can support with. This helps employers invite the right paralegals.
  </p>

  <?php
    $categories = array_keys($skillsByCategory ?? []);
    $activeCat = $categories[0] ?? '';
  ?>

  <!-- Tabs -->
<?php
  $categories = array_keys($skillsByCategory ?? []);
  // Alphabetical
  sort($categories, SORT_NATURAL | SORT_FLAG_CASE);
  $activeCat = $categories[0] ?? '';
?>

<div class="skill-tabs" style="margin-top:12px;">
  <div class="tab-nav">
    <?php foreach ($categories as $i => $cat): ?>
      <button
        type="button"
        class="tab-btn <?= $i===0 ? 'active' : '' ?>"
        data-skill-tab="<?= e($cat) ?>"
      >
        <?= e($cat) ?>
      </button>
    <?php endforeach; ?>
  </div>
</div>


  <!-- Tab content -->
  <div style="margin-top:14px; padding:14px; border:1px solid rgba(255,255,255,.12); border-radius:14px;">

    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
      <div class="muted" id="skillsCountText" style="font-weight:700;">
        Selected: <?= (int)count($selectedSkillIds ?? []) ?> skills
      </div>
      <div style="display:flex; gap:8px;">
        <button type="button" class="btn small secondary" id="skillsSelectAllBtn">Select all</button>
        <button type="button" class="btn small secondary" id="skillsClearBtn">Clear</button>
      </div>
    </div>

    <?php foreach (($skillsByCategory ?? []) as $cat => $items): ?>
      <div data-skill-panel="<?= e($cat) ?>" style="display:none; margin-top:14px;">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
          <?php foreach ($items as $it): ?>
            <?php $checked = in_array((int)$it['id'], ($selectedSkillIds ?? []), true); ?>
            <label style="display:flex; gap:10px; align-items:flex-start; padding:10px 12px; border:1px solid rgba(255,255,255,.10); border-radius:12px;">
              <input
                type="checkbox"
                name="skill_ids[]"
                value="<?= (int)$it['id'] ?>"
                <?= $checked ? 'checked' : '' ?>
                class="skill-checkbox"
                data-skill-cat="<?= e($cat) ?>"
              >
              <span style="font-weight:700;"><?= e($it['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <?php if (empty($items)): ?>
          <div class="muted" style="margin-top:10px;">No skills available in this category.</div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

  </div>
</div>

<script>
(function(){
  const wrap = document.querySelector('#taskskills');
  if(!wrap) return;

  const tabs = wrap.querySelectorAll('.tab-btn');
  const panels = wrap.querySelectorAll('[data-skill-panel]');
  const countText = wrap.querySelector('#skillsCountText');
  const selectAllBtn = wrap.querySelector('#skillsSelectAllBtn');
  const clearBtn = wrap.querySelector('#skillsClearBtn');

  let activeCat = tabs.length ? (tabs[0].dataset.skillTab || null) : null;

  function updateCount(){
    const checked = wrap.querySelectorAll('.skill-checkbox:checked').length;
    if (countText) countText.textContent = 'Selected: ' + checked + ' skills';
  }

  function showTab(cat){
    activeCat = cat;

    panels.forEach(p => {
      p.style.display = (p.dataset.skillPanel === cat) ? 'block' : 'none';
    });

    tabs.forEach(t => {
      t.classList.toggle('active', t.dataset.skillTab === cat);
    });

    updateCount();
  }

  tabs.forEach(t => t.addEventListener('click', () => showTab(t.dataset.skillTab)));

  wrap.querySelectorAll('.skill-checkbox').forEach(cb => cb.addEventListener('change', updateCount));

  if (selectAllBtn){
    selectAllBtn.onclick = () => {
      wrap.querySelectorAll('.skill-checkbox[data-skill-cat="'+activeCat+'"]').forEach(cb => cb.checked = true);
      updateCount();
    };
  }

  if (clearBtn){
    clearBtn.onclick = () => {
      wrap.querySelectorAll('.skill-checkbox[data-skill-cat="'+activeCat+'"]').forEach(cb => cb.checked = false);
      updateCount();
    };
  }

  if (activeCat) showTab(activeCat);
})();
</script>

<!-- /Task skills -->



    <div id="availability"></div>

    <!-- Availability dates -->
    <div style="margin-top:22px; display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
      <div>
        <label style="display:block;font-weight:700;margin-bottom:6px;">Available from</label>
        <input
          id="available_from"
          class="input"
          style="width:100%; height:44px;"
          type="date"
          name="available_from"
          value="<?= e((string)($profile['available_from'] ?? '')) ?>"
        >
      </div>

      <div>
        <label style="display:block;font-weight:700;margin-bottom:6px;">Available until</label>
        <input
          id="available_to"
          class="input"
          style="width:100%; height:44px;"
          type="date"
          name="available_to"
          value="<?= e((string)($profile['available_to'] ?? '')) ?>"
        >
      </div>
    </div>

    <div style="margin-top:10px;">
      <button type="button" class="btn small secondary" onclick="
        document.getElementById('available_from').value='';
        document.getElementById('available_to').value='';
      ">Clear dates</button>
    </div>

    <div style="margin-top:14px; display:flex; gap:24px; flex-wrap:wrap;">
      <label style="display:flex; gap:8px; align-items:center;">
        <input type="checkbox" name="weekend_available" value="1"
          <?= ((int)($profile['weekend_available'] ?? 0)===1)?'checked':'' ?>>
        Weekend available
      </label>

      <label style="display:flex; gap:8px; align-items:center;">
        <input type="checkbox" name="night_available" value="1"
          <?= ((int)($profile['night_available'] ?? 0)===1)?'checked':'' ?>>
        Night work available
      </label>
    </div>

    <!-- Weekly availability -->
    <div style="margin-top:20px;">
      <div style="font-weight:800;margin-bottom:8px;">Weekly availability</div>

      <table class="table" style="width:100%; max-width:980px;">
        <tr>
          <th style="width:160px;">Day</th>
          <th>Start</th>
          <th>End</th>
        </tr>

        <?php foreach ($days as $k => $label): ?>
          <tr>
            <td><?= e($label) ?></td>
            <td>
              <input
                class="input"
                style="height:40px;"
                type="time"
                name="availability[<?= e($k) ?>][start]"
                value="<?= e((string)($availability[$k]['start'] ?? '')) ?>"
              >
            </td>
            <td>
              <input
                class="input"
                style="height:40px;"
                type="time"
                name="availability[<?= e($k) ?>][end]"
                value="<?= e((string)($availability[$k]['end'] ?? '')) ?>"
              >
            </td>
          </tr>
        <?php endforeach; ?>
      </table>

      <div style="font-size:12px;opacity:.75;margin-top:6px;">
        Leave times blank for days you’re not available.
      </div>
    </div>

    <!-- Travel preference -->
    <div style="margin-top:22px;">
      <div id="travel"></div>
      <div style="font-weight:800;margin-bottom:8px;">Travel preference</div>
      <p style="margin-top:6px;opacity:.85; max-width:980px;">
        This helps matching for in-person and cross-border work.
      </p>

      <?php
        $canIntl = ((int)($profile['can_travel_abroad'] ?? 0) === 1);
        $travelAny = ((int)($profile['travel_anywhere'] ?? 0) === 1);
        $travelSel = [];
        $rawTc = (string)($profile['travel_countries'] ?? '');
        if ($rawTc !== '') {
          $dec = json_decode($rawTc, true);
          if (is_array($dec)) $travelSel = array_values(array_filter(array_map('strval', $dec)));
        }
        $travelOptions = [
          'Ireland','France','Germany','Spain','Italy','Netherlands','Belgium','Switzerland',
          'United States','Canada','United Arab Emirates','Singapore','Australia','New Zealand','South Africa'
        ];
      ?>

      <label style="display:flex; gap:10px; align-items:center; margin-top:10px;">
        <input type="checkbox" name="can_travel_abroad" value="1" id="canTravelAbroad" <?= $canIntl ? 'checked' : '' ?>>
        <span style="font-weight:700;">I can travel internationally</span>
      </label>

      <div id="travelIntlBox" style="margin-top:12px; display:<?= $canIntl ? 'block' : 'none' ?>;">
        <label style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
          <input type="checkbox" name="travel_anywhere" value="1" id="travelAnywhere" <?= $travelAny ? 'checked' : '' ?>>
          <span style="font-weight:700;">Travel anywhere</span>
        </label>

        <div class="check-wrap" style="max-width:980px;">
          <div class="check-grid">
            <?php foreach ($travelOptions as $c): ?>
              <?php $checked = in_array($c, $travelSel, true); ?>
              <label class="check-item">
                <input type="checkbox" name="travel_countries[]" value="<?= e($c) ?>" <?= $checked ? 'checked' : '' ?> class="travel-country">
                <span><?= e($c) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="muted" style="margin-top:8px; font-size:12px;">
          If you choose “Travel anywhere”, the country list is optional.
        </div>
      </div>

      <script>
      (function(){
        const cb = document.getElementById('canTravelAbroad');
        const box = document.getElementById('travelIntlBox');
        const any = document.getElementById('travelAnywhere');
        const countries = document.querySelectorAll('.travel-country');
        if (!cb || !box) return;

        function setEnabled(on){
          box.style.display = on ? 'block' : 'none';
          if (!on) {
            if (any) any.checked = false;
            countries.forEach(x => x.checked = false);
          }
        }

        cb.addEventListener('change', () => setEnabled(cb.checked));
      })();
      </script>
    </div>

<!-- Languages + Profile description -->
<div style="margin-top:16px; max-width:980px;">

  <div style="font-weight:800;margin-bottom:8px;">Languages</div>
  <p style="margin-top:6px;opacity:.85;">
    Select all that apply. If you choose <b>Other</b>, add it below.
  </p>

  <?php
    // Client-approved list (fixed)
    $clientLanguages = [
      'Arabic','Dutch','English','French','German','Hindi',
      'Italian','Japanese','Mandarin','Spanish','Portuguese'
    ];

    // Parse existing saved string (best-effort)
    $savedLangRaw = (string)($profile['languages'] ?? '');
    $savedLangRawTrim = trim($savedLangRaw);

    $savedSelected = [];  // exact label matches
    $savedOtherText = '';

    if ($savedLangRawTrim !== '') {
      // If it contains "Other:" extract it
      if (preg_match('/\bOther\s*:\s*(.+)$/i', $savedLangRawTrim, $m)) {
        $savedOtherText = trim($m[1]);
        // Remove the Other: part before splitting
        $savedLangRawTrim = preg_replace('/\bOther\s*:\s*.+$/i', '', $savedLangRawTrim);
      }

      $parts = array_values(array_filter(array_map('trim', explode(',', $savedLangRawTrim))));
      $lookup = [];
      foreach ($parts as $p) $lookup[strtolower($p)] = true;

      foreach ($clientLanguages as $l) {
        if (isset($lookup[strtolower($l)])) $savedSelected[] = $l;
      }
    }

    $otherChecked = ($savedOtherText !== '');
  ?>

  <!-- Hidden field so current backend still receives "languages" -->
  <input type="hidden" name="languages" id="languagesHidden" value="<?= e((string)($profile['languages'] ?? '')) ?>">

  <div class="check-wrap" style="max-width:980px;">
    <div class="check-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
      <?php foreach ($clientLanguages as $lang): ?>
        <?php $checked = in_array($lang, $savedSelected, true); ?>
        <label class="check-item">
          <input type="checkbox" class="lang-checkbox" value="<?= e($lang) ?>" <?= $checked ? 'checked' : '' ?>>
          <span><?= e($lang) ?></span>
        </label>
      <?php endforeach; ?>

      <label class="check-item">
        <input type="checkbox" class="lang-other-toggle" value="Other" <?= $otherChecked ? 'checked' : '' ?>>
        <span>Other</span>
      </label>
    </div>
  </div>

  <div id="otherLangBox" style="margin-top:10px; display:<?= $otherChecked ? 'block' : 'none' ?>;">
    <label style="display:block;font-weight:700;margin-bottom:6px;">Other language(s)</label>
    <input
      class="input"
      style="width:100%; height:44px;"
      id="otherLangInput"
      type="text"
      value="<?= e($savedOtherText) ?>"
      placeholder="e.g. Yoruba, Swahili"
    >
    <div style="font-size:12px; opacity:.75; margin-top:6px;">
      Use commas if listing more than one.
    </div>
  </div>

  <script>
  (function(){
    const hidden = document.getElementById('languagesHidden');
    const boxes = Array.from(document.querySelectorAll('.lang-checkbox'));
    const otherToggle = document.querySelector('.lang-other-toggle');
    const otherBox = document.getElementById('otherLangBox');
    const otherInput = document.getElementById('otherLangInput');

    if (!hidden || !otherToggle || !otherBox) return;

    function rebuildHidden(){
      const selected = boxes.filter(b => b.checked).map(b => b.value);
      const otherOn = !!otherToggle.checked;
      const otherVal = otherInput ? (otherInput.value || '').trim() : '';

      let out = selected.join(', ');
      if (otherOn) {
        if (otherVal !== '') {
          out = (out ? (out + ', ') : '') + 'Other: ' + otherVal;
        } else {
          out = (out ? (out + ', ') : '') + 'Other';
        }
      }
      hidden.value = out;
    }

    function setOtherVisible(on){
      otherBox.style.display = on ? 'block' : 'none';
      if (!on && otherInput) otherInput.value = '';
      rebuildHidden();
    }

    boxes.forEach(b => b.addEventListener('change', rebuildHidden));
    otherToggle.addEventListener('change', () => setOtherVisible(otherToggle.checked));
    if (otherInput) otherInput.addEventListener('input', rebuildHidden);

    rebuildHidden();
  })();
  </script>

  <!-- Profile description (front-end only for now) -->
  <div style="margin-top:18px;">
    <div style="font-weight:800;margin-bottom:8px;">Profile description</div>
    <p style="margin-top:6px;opacity:.85;">
      Briefly describe your strengths, typical tasks you support with, industries, and tools.
    </p>

    <textarea
      class="input"
      name="profile_summary"
      rows="5"
      style="width:100%; padding-top:10px; height:auto; min-height:120px;"
      placeholder="Example: Litigation support paralegal with strong drafting, disclosure and bundle preparation experience. Comfortable with tight deadlines, e-filing platforms and case management systems."
    ></textarea>
  </div>

</div>

    <!-- Documents -->
    <div style="margin-top:22px;">
      <div id="documents"></div>
      <div style="font-weight:800;margin-bottom:8px;">
        Documents (required before you can receive an Assignment)
      </div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
        <div>
          <label style="display:block;font-weight:700;margin-bottom:6px;">CV</label>
          <input class="input" style="height:44px;" type="file" name="cv_file">
          <?php if (!empty($profile['cv_path'])): ?>
            <div style="margin-top:6px;font-size:12px;">
              <a href="<?= e($profile['cv_path']) ?>" target="_blank">View current</a>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <label style="display:block;font-weight:700;margin-bottom:6px;">Passport / ID</label>
          <input class="input" style="height:44px;" type="file" name="id_file">
          <?php if (!empty($profile['id_doc_path'])): ?>
            <div style="margin-top:6px;font-size:12px;">
              <a href="<?= e($profile['id_doc_path']) ?>" target="_blank">View current</a>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <label style="display:block;font-weight:700;margin-bottom:6px;">E-visa (if applicable)</label>
          <input class="input" style="height:44px;" type="file" name="visa_file">
          <?php if (!empty($profile['visa_path'])): ?>
            <div style="margin-top:6px;font-size:12px;">
              <a href="<?= e($profile['visa_path']) ?>" target="_blank">View current</a>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <label style="display:block;font-weight:700;margin-bottom:6px;">
            Utility bill evidence (if requested)
          </label>
          <input class="input" style="height:44px;" type="file" name="utility_file">
          <?php if (!empty($profile['utility_bill_path'])): ?>
            <div style="margin-top:6px;font-size:12px;">
              <a href="<?= e($profile['utility_bill_path']) ?>" target="_blank">View current</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

	  
	  
	  
	  <style>
/* Make the whole Task skills area full width */
#taskskills,
#taskskills .skill-tabs,
#taskskills .tab-nav{
  width:100%;
  max-width:100%;
}

/* Skill tabs: 2 per row */
#taskskills .tab-nav{
  display:grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap:10px;
  margin-bottom:12px;
}
@media (max-width: 640px){
  #taskskills .tab-nav{ grid-template-columns: 1fr; }
}
#taskskills .tab-btn{
  width:100% !important;
  display:flex !important;
  align-items:center;
  justify-content:center;
  padding:10px 14px;
  border:1px solid rgba(255,255,255,.14);
  background:rgba(255,255,255,.05);
  border-radius:14px;
  cursor:pointer;
  font-size:14px;
  font-weight:700;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
#taskskills .tab-btn.active{
  background:#1f7a4d;
  border-color:#1f7a4d;
  color:#fff;
}
</style>
	  
	  
	  
    <!-- Buttons -->
    <div style="margin-top:26px; display:flex; gap:12px; align-items:center;">
      <button class="btn" type="submit" style="min-width:160px;">
        Submit profile
      </button>

      <a class="btn secondary" href="/p/dashboard.php" style="min-width:120px;">
        Back
      </a>
    </div>

  </form>
</div>
