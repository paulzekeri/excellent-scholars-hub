<?php

/**
 * One-off palette sweep (Tier 1 of the LMS colour pass).
 *
 * Replaces Tailwind's yellow/amber "attention" utilities with the brand sun
 * tokens (see the tailwind config in layouts/app.blade.php) and lifts the
 * lowest-contrast grey meta text one step. Run once with:
 *
 *     php tools/sun-sweep.php
 *
 * The file is kept in the repo as a record of what the sweep did; it is safe to
 * re-run (it reports zero changes once the mapping has been applied).
 */

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';

$map = [
    // Fill: sun tints and the solid sun highlight.
    'bg-yellow-50' => 'bg-sun-light',
    'bg-yellow-100' => 'bg-sun-light',
    'bg-yellow-200' => 'bg-sun-edge',
    'bg-yellow-400' => 'bg-sun',
    'bg-amber-50' => 'bg-sun-light',
    'bg-amber-100' => 'bg-sun-light',

    // Text: one readable ink for every sun surface (5.4:1 on the tint, 5.6:1 on white).
    'text-yellow-500' => 'text-sun-ink',
    'text-yellow-600' => 'text-sun-ink',
    'text-yellow-700' => 'text-sun-ink',
    'text-yellow-800' => 'text-sun-ink',
    'text-yellow-900' => 'text-sun-ink',
    'text-amber-600' => 'text-sun-ink',
    'text-amber-800' => 'text-sun-ink',
    'text-amber-900' => 'text-sun-ink',

    // Borders.
    'border-yellow-200' => 'border-sun-edge',
    'border-yellow-300' => 'border-sun-edge',
    'border-yellow-400' => 'border-sun',
    'border-yellow-500' => 'border-sun',
    'border-amber-200' => 'border-sun-edge',
    'border-amber-400' => 'border-sun',
    'border-amber-500' => 'border-sun',

    // Meta text one step darker so small copy clears 4.5:1 on white.
    'text-gray-400' => 'text-gray-500',
];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$changed = 0;
$total = 0;

foreach ($files as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $before = file_get_contents($path);
    $after = strtr($before, $map);

    if ($after !== $before) {
        file_put_contents($path, $after);
        $changed++;
        $total += count($map);
    }
}

echo "Files changed: {$changed}\n";
