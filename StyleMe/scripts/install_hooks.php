<?php
// Install git hooks for this repository.
// Preferred method: set `core.hooksPath` to the repo-local `.githooks` folder so hooks are used without copying.
// Fallback: copy files into `.git/hooks` (legacy behavior).
$root = __DIR__ . '/../';
$hookDir = $root . '.git/hooks';
$source = $root . '.githooks';
if (!is_dir($source)) {
    echo "No .githooks directory found.\n";
    exit(1);
}

// Try to set core.hooksPath (preferred, one-time)
$setHooks = null;
exec('git rev-parse --git-dir 2>NUL', $out, $code);
if ($code === 0) {
    // We are in a git repo
    exec('git config core.hooksPath ' . escapeshellarg('.githooks') . ' 2>&1', $o2, $c2);
    if ($c2 === 0) {
        echo "Configured git to use .githooks via core.hooksPath.\n";
        $setHooks = true;
    }
}

if ($setHooks !== true) {
    // Fallback: copy hooks into .git/hooks
    if (!is_dir($hookDir)) {
        echo "No .git/hooks directory found. Are you in a git repo?\n";
        exit(1);
    }
    $files = scandir($source);
    $copied = 0;
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $src = $source . DIRECTORY_SEPARATOR . $f;
        $dst = $hookDir . DIRECTORY_SEPARATOR . $f;
        if (!copy($src, $dst)) {
            echo "Failed to copy $f\n";
            continue;
        }
        @chmod($dst, 0755);
        $copied++;
    }
    echo "Installed $copied hooks by copying into .git/hooks.\n";
}
