<?php
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function redirect($p){header('Location: '.$p);exit;}
function flash($m,$t='info'){$_SESSION['flash']=['message'=>$m,'type'=>$t];}
function flash_get(){if(empty($_SESSION['flash']))return null;$f=$_SESSION['flash'];unset($_SESSION['flash']);return $f;}
function render($view,$data=[]){$vf=__DIR__."/views/$view.php";if(!file_exists($vf)){http_response_code(500);echo "View missing";exit;}extract($data);$content_view=$vf;include __DIR__.'/views/layout.php';}
function notify($uid,$msg){db_query("INSERT INTO notifications (user_id,message,is_read,created_at) VALUES (?,?,0,NOW())",[(int)$uid,(string)$msg]);}
function setting_get($key, $default = null) {
  $row = db_fetch_one("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
  return $row ? $row['setting_value'] : $default;
}

function setting_set($key, $value) {
  db_query("
    INSERT INTO settings (setting_key, setting_value)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
  ", [$key, $value]);
}

function csrf_field(): string {
  $token = csrf_token(); // must exist
  return '<input type="hidden" name="csrf_token" value="'.e($token).'">';
}

function role_label($r){return $r===ROLE_ADMIN?'admin':($r===ROLE_EMPLOYER?'employer':($r===ROLE_PARALEGAL?'paralegal':'unknown'));}

function auth_user_id(){
  $u = auth_user();
  return $u ? (int)$u['user_id'] : 0;
}

// --- Date formatting helpers (UK) ---
function uk_date($value): string {
  if ($value === null || $value === '') return '';
  $ts = strtotime((string)$value);
  if ($ts === false) return (string)$value;
  return date('d/m/Y', $ts);
}

function uk_datetime($value): string {
  if ($value === null || $value === '') return '';
  $ts = strtotime((string)$value);
  if ($ts === false) return (string)$value;
  return date('d/m/Y H:i', $ts);
}

// --- Simple pagination helper (for views) ---
function pagination_meta(int $total, int $page, int $per_page): array {
  $per_page = max(1, $per_page);
  $total_pages = max(1, (int)ceil($total / $per_page));
  $page = min(max(1, $page), $total_pages);
  $offset = ($page - 1) * $per_page;
  return [
    'total' => $total,
    'page' => $page,
    'per_page' => $per_page,
    'total_pages' => $total_pages,
    'offset' => $offset,
    'has_prev' => $page > 1,
    'has_next' => $page < $total_pages,
  ];
}

// --- Schema helpers ---
// Used to keep pages working across environments where migrations may not yet be applied.
function db_has_column(string $table, string $column): bool {
  static $cache = [];
  $key = strtolower($table).'.'.strtolower($column);
  if (array_key_exists($key, $cache)) return (bool)$cache[$key];
  try {
    $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $rows = db_fetch_all("SHOW COLUMNS FROM `{$tableSafe}`");
    foreach ($rows as $r) {
      if (strtolower((string)$r['Field']) === strtolower($column)) {
        return $cache[$key] = true;
      }
    }
  } catch (Throwable $e) {
    // If SHOW COLUMNS fails (permissions etc.), assume missing.
  }
  return $cache[$key] = false;
}

// Checks whether a table exists in the current database.
function db_has_table(string $table): bool {
  static $cache = [];
  $key = strtolower($table);
  if (array_key_exists($key, $cache)) return (bool)$cache[$key];
  try {
    $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    // MySQL returns a single column with the table name when it matches.
    $row = db_fetch_one("SHOW TABLES LIKE ?", [$tableSafe]);
    return $cache[$key] = (bool)$row;
  } catch (Throwable $e) {
    return $cache[$key] = false;
  }
}