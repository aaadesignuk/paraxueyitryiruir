<style>
/* ===== Task tabs: 2 per row ===== */
.task-tabs .tab-nav{
  display:grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap:10px;
  margin-bottom:12px;
}
@media (max-width: 640px){
  .task-tabs .tab-nav{ grid-template-columns: 1fr; }
}
.task-tabs .tab-btn{
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
.task-tabs .tab-btn.active{
  background:#1f7a4d;
  border-color:#1f7a4d;
  color:#fff;
}
.task-tabs .tab-content{ display:none; }
.task-tabs .tab-content.active{ display:block; }
</style>

<div class="section">
  <div class="section-title">Create Job</div>
  <div class="section-hint">
    Define scope and let Paralete match you with the right paralegal.
  </div>

  <form method="post">

    <!-- ===================== -->
    <!-- JOB DETAILS -->
    <!-- ===================== -->

   <div class="grid-2">
  <div>

     <label>Title</label>
<textarea name="title" required rows="1" maxlength="255" style="width:100%; resize:vertical; min-height:44px;">
	<?= e($_POST['title'] ?? '') ?></textarea>

 
  </div>

  <div>
    <label>Client Reference</label>
    <div class="field">
      <input
        name="client_ref"
        maxlength="100"
        value="<?= e($_POST['client_ref'] ?? '') ?>"
        placeholder="e.g. ABC/1234"
      >
    </div>
  </div>
</div>


    <div class="grid-2">
      <div>
        <label>Specialism</label>
        <div class="field">
          <select name="specialism" id="specialism" required>
            <option value="">Select</option>
            <?php foreach($specialisms as $s): ?>
              <option value="<?= e($s) ?>"><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div>
        <label>Sub-specialism</label>
        <div class="field">
          <select name="sub_specialism" id="sub_specialism">
            <option value="">Select</option>
          </select>
        </div>
      </div>
    </div>

    <div class="grid-2">
      <div>
        <label>Engagement Type</label>
        <div class="field">
          <select name="job_type" id="job_type">
            <option value="Hours">Hours</option>
            <option value="Days">Days</option>
          </select>
        </div>
      </div>

      <div id="hours_wrap">
        <label>Hours Required</label>
        <div class="field">
          <input type="number" step="0.25" min="0" name="hours_required">
        </div>
      </div>

      <div id="days_wrap" style="display:none;">
        <label>Days Required</label>
        <div class="field">
          <input type="number" step="0.5" min="0" name="days_required">
        </div>
      </div>
    </div>

    <div class="grid-2">
      <div>
        <label>Offered Rate (£/hr)</label>
        <div class="field">
          <input type="number" step="0.01" name="rate_amount">
        </div>
      </div>

      <div>
        <label>Deadline (optional)</label>
        <div class="field">
          <input type="date" name="deadline">
        </div>
      </div>
    </div>

    <!-- ===================== -->
    <!-- WORK LOCATION -->
    <!-- ===================== -->

    <h3 style="margin-top:20px;">Work Location</h3>

    <div class="grid-2" style="margin-top:10px;">
      <div>
        <label>Work Mode</label>
        <div class="field">
          <select name="work_mode" id="work_mode" required>
            <option value="">Select</option>
            <option value="Remote">Remote</option>
            <option value="On-site">On-site</option>
            <option value="Hybrid">Hybrid</option>
          </select>
        </div>
      </div>

      <div id="country_wrap" style="display:none;">
        <label>Country</label>
        <div class="field">
          <select name="job_country" id="job_country">
            <option value="">Select country</option>
            <option>United Kingdom</option>
            <option>United States</option>
            <option>Italy</option>
            <option>France</option>
            <option>Germany</option>
            <option>Spain</option>
            <option>UAE</option>
            <option>India</option>
            <option>Singapore</option>
            <option>Australia</option>
            <option>Canada</option>
            <option>Switzerland</option>
            <option>Netherlands</option>
            <option>Ireland</option>
            <option>Other</option>
          </select>
        </div>
      </div>

      <div id="city_wrap" style="display:none;">
        <label>City / State</label>
        <div class="field">
          <input name="job_city">
        </div>
      </div>
    </div>

    <div id="other_country_wrap" style="display:none;">
      <label>Other country</label>
      <div class="field">
        <input name="job_country_other">
      </div>
    </div>

    <!-- ===================== -->
    <!-- TRAVEL -->
    <!-- ===================== -->

    <h3 style="margin-top:20px;">Travel</h3>

    <div class="checkbox-row" style="margin-top:8px;">
      <label class="checkbox">
        <input type="checkbox" id="travel_required" name="travel_required" value="1">
        <span>Travel required</span>
      </label>
    </div>

    <div id="travel_fields" style="display:none; margin-top:10px;">
      <div class="grid-3">
        <div>
          <label>Destination Country</label>
          <div class="field">
            <input name="travel_country">
          </div>
        </div>

        <div>
          <label>Destination City</label>
          <div class="field">
            <input name="travel_city">
          </div>
        </div>

        <div>
          <label>Travel Days</label>
          <div class="field">
            <input type="number" min="1" name="travel_days">
          </div>
        </div>

        <div>
          <label>Travel Budget (£)</label>
          <div class="field">
            <input type="number" step="0.01" name="travel_budget">
          </div>
        </div>
      </div>
    </div>

    <!-- ===================== -->
    <!-- TASK REQUIREMENTS -->
    <!-- ===================== -->

    <h3 style="margin-top:20px;">Task Requirements</h3>
    <p style="opacity:.8;">Select tasks needed (used for matching).</p>

    <?php
      $byCat = [];
      foreach (($task_activities ?? []) as $a) {
        $cid = (int)$a['category_id'];
        $byCat[$cid][] = $a;
      }

      $task_categories_sorted = $task_categories ?? [];
      usort($task_categories_sorted, function($a,$b){
        return strcasecmp($a['name'], $b['name']);
      });
    ?>

    <div class="task-tabs">

      <div class="tab-nav">
        <?php foreach($task_categories_sorted as $i => $c): ?>
          <button type="button"
                  class="tab-btn <?= $i===0?'active':'' ?>"
                  data-tab="tab<?= (int)$c['id'] ?>">
            <?= e($c['name']) ?>
          </button>
        <?php endforeach; ?>
      </div>

      <?php foreach($task_categories_sorted as $i => $c): ?>
        <div class="tab-content <?= $i===0?'active':'' ?>"
             id="tab<?= (int)$c['id'] ?>">
          <div class="check-grid">
            <?php foreach(($byCat[$c['id']] ?? []) as $a): ?>
              <label class="check-item">
                <input type="checkbox" name="activities[]" value="<?= (int)$a['id'] ?>">
                <span><?= e($a['name']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

    </div>

    <label style="margin-top:20px;">Description</label>
    <textarea name="description"></textarea>

    <div style="margin-top:14px;">
      <button class="btn" type="submit">Create Job</button>
    </div>

  </form>
</div>

<script>
// Specialism load
async function loadSubs(){
  const sp = document.getElementById('specialism').value;
  const sub = document.getElementById('sub_specialism');
  sub.innerHTML = '<option>Loading…</option>';
  const res = await fetch('/e/specialisms_api.php?specialism=' + encodeURIComponent(sp));
  const data = await res.json();
  sub.innerHTML = '<option value="">Select</option>';
  (data.sub_specialisms || []).forEach(v => {
    const o = document.createElement('option');
    o.value = v;
    o.textContent = v;
    sub.appendChild(o);
  });
}

function toggleScope(){
  const t = document.getElementById('job_type').value;
  document.getElementById('hours_wrap').style.display = (t==='Hours') ? '' : 'none';
  document.getElementById('days_wrap').style.display = (t==='Days') ? '' : 'none';
}

function toggleLocation(){
  const m = document.getElementById('work_mode').value;
  const show = (m==='On-site' || m==='Hybrid');
  document.getElementById('country_wrap').style.display = show ? '' : 'none';
  document.getElementById('city_wrap').style.display = show ? '' : 'none';
}

function toggleOtherCountry(){
  const c = document.getElementById('job_country').value;
  document.getElementById('other_country_wrap').style.display = (c==='Other') ? '' : 'none';
}

function toggleTravel(){
  document.getElementById('travel_fields').style.display =
    document.getElementById('travel_required').checked ? '' : 'none';
}

document.getElementById('specialism').addEventListener('change', loadSubs);
document.getElementById('job_type').addEventListener('change', toggleScope);
document.getElementById('work_mode').addEventListener('change', toggleLocation);
document.getElementById('job_country').addEventListener('change', toggleOtherCountry);
document.getElementById('travel_required').addEventListener('change', toggleTravel);

// Scoped tabs
document.querySelectorAll('.task-tabs').forEach(wrap => {
  const btns = wrap.querySelectorAll('.tab-btn');
  const panels = wrap.querySelectorAll('.tab-content');

  btns.forEach(btn => {
    btn.addEventListener('click', function(){
      btns.forEach(b => b.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));
      this.classList.add('active');
      wrap.querySelector('#' + this.dataset.tab).classList.add('active');
    });
  });
});

toggleScope();
toggleLocation();
toggleTravel();
</script>
