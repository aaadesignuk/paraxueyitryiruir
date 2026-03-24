<style>
.notifications-page-head{ display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.notifications-list{ display:grid; gap:12px; margin-top:14px; }
.notifications-card{ display:block; padding:14px 16px; border-radius:16px; text-decoration:none; color:inherit; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08); }
.notifications-card.unread{ border-color:rgba(255,255,255,.18); background:rgba(255,255,255,.08); }
.notifications-card:hover{ background:rgba(255,255,255,.09); }
.notifications-meta{ opacity:.72; font-size:12px; margin-top:6px; }
.unread-pill{ display:inline-block; padding:6px 10px; border-radius:999px; border:1px solid rgba(255,255,255,.14); background:rgba(255,255,255,.06); font-weight:800; }
</style>

<div class="section" style="margin-top:0;">
  <div class="notifications-page-head">
    <div>
      <div class="section-title">Notifications</div>
      <div class="section-hint">Latest alerts from jobs, placements and timesheet activity.</div>
    </div>
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
      <div class="unread-pill"><?= (int)$unread_count ?> unread</div>
      <?php if ((int)$unread_count > 0): ?>
        <a class="btn micro" href="/notifications.php?mark_all=1">Mark all read</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="section">
  <div class="notifications-list">
    <?php if (empty($notifications)): ?>
      <div class="notifications-card">No notifications yet.</div>
    <?php else: ?>
      <?php foreach ($notifications as $n): ?>
        <a class="notifications-card <?= !empty($n['is_read']) ? '' : 'unread' ?>" href="/notification_go.php?id=<?= (int)$n['notification_id'] ?>">
          <div><?= e($n['message']) ?></div>
          <div class="notifications-meta"><?= e(!empty($n['created_at']) ? uk_datetime($n['created_at']) : '-') ?></div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
