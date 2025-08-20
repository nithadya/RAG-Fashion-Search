<?php
// Scans the repository for TODO/FIXME/BUG comments and adds them to bugs.json
$root = __DIR__ . '/../';
$ignore = ['.git', 'assets/uploads', 'node_modules', 'vendor', 'tests'];
$patterns = ['/TODO/i', '/FIXME/i', '/BUG[:\s]/i'];
$bugsFile = __DIR__ . '/../bugs.json';

function shouldIgnore($path, $ignore)
{
    foreach ($ignore as $i) {
        if (strpos($path, DIRECTORY_SEPARATOR . $i . DIRECTORY_SEPARATOR) !== false || substr($path, -strlen($i)) === $i) {
            return true;
        }
    }
    return false;
}

function scanFile($file, $patterns)
{
    $found = [];
    $lines = file($file);
    foreach ($lines as $num => $line) {
        foreach ($patterns as $pat) {
            if (preg_match($pat, $line, $m)) {
                $found[] = [
                    'line' => $num + 1,
                    'text' => trim($line),
                    'match' => $m[0]
                ];
                break;
            }
        }
    }
    return $found;
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$bugs = json_decode(@file_get_contents($bugsFile) ?: '[]', true);
if (!is_array($bugs)) $bugs = [];
$maxId = 0;
foreach ($bugs as $b) {
    if (isset($b['id']) && $b['id'] > $maxId) $maxId = $b['id'];
}

foreach ($iterator as $fileInfo) {
    if ($fileInfo->isDir()) continue;
    $path = $fileInfo->getPathname();
    $rel = str_replace($root, '', $path);
    if (shouldIgnore($rel, $ignore)) continue;
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array($ext, ['php', 'js', 'css', 'html', 'htm', 'py'])) continue;

    $hits = scanFile($path, $patterns);
    foreach ($hits as $h) {
        // Check if already recorded
        $exists = false;
        foreach ($bugs as $b) {
            if ($b['file'] === $rel && $b['line'] === $h['line'] && $b['status'] === 'open') {
                $exists = true;
                break;
            }
        }
        if ($exists) continue;
        $maxId++;
        $bugs[] = [
            'id' => $maxId,
            'title' => substr($h['text'], 0, 140),
            'file' => $rel,
            'line' => $h['line'],
            'snippet' => $h['text'],
            'status' => 'open',
            'created_at' => date('c'),
            'closed_at' => null,
            'closed_by_commit' => null
        ];
    }
}

file_put_contents($bugsFile, json_encode($bugs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Scan complete. Found " . count($bugs) . " total bugs (open+closed)\n";
