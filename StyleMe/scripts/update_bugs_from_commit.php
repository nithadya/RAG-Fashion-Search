<?php
// Usage: php update_bugs_from_commit.php <commit-sha>
$root = __DIR__ . '/../';
$bugsFile = __DIR__ . '/../bugs.json';
$commit = $argv[1] ?? trim(shell_exec('git rev-parse HEAD'));
if (!$commit) {
    fwrite(STDERR, "No commit SHA provided and git rev-parse failed\n");
    exit(1);
}
$msg = trim(shell_exec('git log -1 --pretty=%B ' . escapeshellarg($commit)));
if ($msg === '') {
    fwrite(STDERR, "Could not read commit message for $commit\n");
    exit(1);
}

$body = $msg;
$matches = [];
// Look for patterns like: Fixes #123 or fixes #123
preg_match_all('/\b[Ff]ixes?\s+#(\d+)\b/', $body, $matches);
if (empty($matches[1])) {
    echo "No bug references found in commit message.\n";
    exit(0);
}

$ids = array_unique($matches[1]);
$bugs = json_decode(@file_get_contents($bugsFile) ?: '[]', true);
if (!is_array($bugs)) {
    fwrite(STDERR, "Invalid bugs.json\n");
    exit(1);
}
$now = date('c');
$updated = 0;
foreach ($bugs as &$b) {
    if (in_array((string)$b['id'], $ids) && $b['status'] !== 'closed') {
        $b['status'] = 'closed';
        $b['closed_at'] = $now;
        $b['closed_by_commit'] = $commit;
        $updated++;
    }
}
unset($b);
if ($updated > 0) {
    file_put_contents($bugsFile, json_encode($bugs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Updated $updated bug(s) as closed.\n";
} else {
    echo "No matching open bugs to close.\n";
}
