<?php

/**
 * One-off badge sweep: unread counters switch from orange to sun, so orange
 * means "do this" (buttons) and sun means "look here" (unread / attention).
 *
 *     php tools/badge-sweep.php
 */
$root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';

$map = [
    'rounded-full bg-orange-500 px-1 text-[10px] font-bold text-blue-950' => 'rounded-full bg-sun px-1 text-[10px] font-bold text-[#0a2550]',
    'bg-orange-500 text-blue-950 text-xs rounded-full px-2 py-0.5' => 'bg-sun text-[#0a2550] text-xs rounded-full px-2 py-0.5',
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
