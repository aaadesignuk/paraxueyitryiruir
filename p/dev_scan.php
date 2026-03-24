<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_role([ROLE_ADMIN]); // or your own check

$root = realpath(__DIR__ . '/..'); // project root
$patterns = [
  '/\bj\.mode\b/i',
  '/\bj\.urgency\b/i',
  '/\bjobs\.mode\b/i',
  '/\bjobs\.urgency\b/i',
];

$rii = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$hits = [];

foreach ($rii as $file) {
  /** @var SplFileInfo $file */
  if (!$file->isFile()) continue;

  $path = $file->getPathname();

  // skip big/vendor folders
  if (strpos($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR) !== false) continue;
  if (strpos($path, DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR) !== false) continue;
  if (strpos($path, DIRECTORY_SEPARATOR.'.git'.DIRECTORY_SEPARATOR) !== false) continue;

  // scan only code-like files
  if (!preg_match('/\.(php|phtml|inc|html|js|css|sql)$/i', $path)) continue;

  $lines = @file($path);
  if (!$lines) continue;

  foreach ($lines as $i => $line) {
    foreach ($patterns as $rx) {
      if (preg_match($rx, $line)) {
        $hits[] = $path . ':' . ($i+1) . ': ' . rtrim($line);
        break;
      }
    }
  }
}

header('Content-Type: text/plain; charset=utf-8');

if (!$hits) {
  echo "No matches found.\n";
  exit;
}

echo "Matches:\n\n";
foreach ($hits as $h) echo $h . "\n";
echo "ROOT: ".$root."\n";
