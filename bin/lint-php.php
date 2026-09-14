<?php
/**
 * Vérifie la syntaxe de tous les fichiers PHP versionnés par le projet.
 */

$project_root = dirname(__DIR__);
$iterator     = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $project_root,
        FilesystemIterator::SKIP_DOTS
    )
);
$has_errors = false;

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || 'php' !== strtolower($file->getExtension())) {
        continue;
    }

    $path = $file->getPathname();

    if (
        false !== strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
        || false !== strpos($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)
    ) {
        continue;
    }

    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path);
    passthru($command, $exit_code);

    if (0 !== $exit_code) {
        $has_errors = true;
    }
}

exit($has_errors ? 1 : 0);

