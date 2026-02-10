<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/verify_audit_log.php <path-to-audit_ledger.log>\n");
    exit(1);
}

$path = $argv[1];
if (!is_file($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(1);
}

$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) {
    fwrite(STDERR, "Failed to read file.\n");
    exit(1);
}

$prevHash = null;
$lineNumber = 0;
$started = false;
foreach ($lines as $line) {
    $lineNumber++;
    $decoded = json_decode($line, true);
    if (!is_array($decoded)) {
        fwrite(STDERR, "Invalid JSON at line {$lineNumber}.\n");
        exit(1);
    }

    $storedPrev = $decoded['prev_hash'] ?? null;
    $storedHash = $decoded['hash'] ?? null;

    if (!$started) {
        if (!$storedHash) {
            continue; // skip legacy unchained entries
        }
        $started = true;
    }

    if ($storedPrev !== $prevHash) {
        fwrite(STDERR, "Hash chain mismatch at line {$lineNumber}.\n");
        exit(1);
    }

    $copy = $decoded;
    unset($copy['hash']);
    $reEncoded = json_encode($copy, JSON_UNESCAPED_SLASHES);
    $computed = hash('sha256', $reEncoded);

    if ($storedHash !== $computed) {
        fwrite(STDERR, "Hash mismatch at line {$lineNumber}.\n");
        exit(1);
    }

    $prevHash = $storedHash;
}

if (!$started) {
    fwrite(STDOUT, "No hashed entries found. Nothing to verify.\n");
    exit(0);
}

fwrite(STDOUT, "Audit log verified: OK (verified hashed entries)\n");
