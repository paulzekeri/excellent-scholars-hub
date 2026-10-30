<?php

/**
 * One-off table sweep (Tier 2, item 7 of the LMS colour pass).
 *
 * Gives every data table the brand header: pale-blue `#EAF2FB` band with navy
 * semibold column labels instead of the default gray-50 strip. Run with:
 *
 *     php tools/table-sweep.php
 */

$root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';

$map = [
    '<thead class="bg-gray-50">' => '<thead class="bg-[#EAF2FB]">',
    'text-xs font-medium text-gray-500 uppercase' => 'text-xs font-semibold text-[#0a2550] uppercase',
    'text-xs font-semibold text-gray-500 uppercase' => 'text-xs font-semibold text-[#0a2550] uppercase',
];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$changed = 0;

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
    }
}

echo "Files changed: {$changed}\n";
