<?php
declare(strict_types=1);
/* MM-DS · biblioteca común de bin/mm-ds-*. PHP >= 8.1 sin Composer. */

const MMDS_EXCLUDE_DIRS = ['.git'];
const MMDS_EXCLUDE_FILES = ['.DS_Store', 'manifest.json'];

function mmds_root(): string { return dirname(__DIR__); }

function mmds_sha(string $file): string { return 'sha256:' . hash_file('sha256', $file); }

/** Archivos del paquete (rutas relativas con /), ordenados, sin manifest.json, .git ni .DS_Store. */
function mmds_package_files(string $root): array {
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        fn(SplFileInfo $f) => !($f->isDir() && in_array($f->getFilename(), MMDS_EXCLUDE_DIRS, true))
    ));
    foreach ($it as $f) {
        if (!$f->isFile() || in_array($f->getFilename(), MMDS_EXCLUDE_FILES, true)) continue;
        $out[] = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
    }
    sort($out, SORT_STRING);
    return $out;
}

function mmds_load_manifest(string $root): array {
    $f = $root . '/manifest.json';
    if (!is_file($f)) { fwrite(STDERR, "✗ Falta manifest.json (ejecuta bin/mm-ds-manifest).\n"); exit(1); }
    $m = json_decode((string)file_get_contents($f), true);
    if (!is_array($m) || !isset($m['version'], $m['files'])) { fwrite(STDERR, "✗ manifest.json inválido.\n"); exit(1); }
    return $m;
}

/** Ruta de un archivo del paquete dentro de la app, o null si es solo del paquete / no aplica a esa app. */
function mmds_target(array $m, string $rel, string $appKey): ?string {
    if (isset($m['onlyFor'][$rel]) && !in_array($appKey, $m['onlyFor'][$rel], true)) return null;
    if (!empty($m['apps'][$appKey]['tailwind']) && empty($m['apps'][$appKey]['linkedCss'])) {
        foreach ($m['tailwindTargets'] ?? [] as [$from, $to]) {
            if (str_starts_with($rel, $from)) return $to . substr($rel, strlen($from));
        }
    }
    foreach ($m['targets'] as [$from, $to]) {
        if (str_starts_with($rel, $from)) return $to . substr($rel, strlen($from));
    }
    return null;
}

/** @return array{0:string,1:string} [clave de app, ruta absoluta] */
function mmds_resolve_app(array $m, string $arg, string $root): array {
    $sistemas = dirname($root);
    foreach ($m['apps'] as $key => $cfg) {
        if (strcasecmp($arg, $key) === 0 || strcasecmp(rtrim($arg, '/'), $cfg['dir']) === 0) {
            return [$key, $sistemas . '/' . $cfg['dir']];
        }
    }
    // «<app>=<ruta>»: una carpeta con otro nombre (p. ej. un worktree .worktrees/mm-ds-a1) que es esa app.
    if (($eq = strpos($arg, '=')) !== false) {
        $k = substr($arg, 0, $eq);
        $real = realpath(substr($arg, $eq + 1));
        foreach ($m['apps'] as $key => $cfg) {
            if (strcasecmp($k, $key) === 0 && $real && is_dir($real)) return [$key, $real];
        }
    }
    $real = realpath($arg);
    if ($real && is_dir($real)) {
        foreach ($m['apps'] as $key => $cfg) if (basename($real) === $cfg['dir']) return [$key, $real];
    }
    fwrite(STDERR, "✗ App desconocida «{$arg}». Usa: " . implode(' | ', array_keys($m['apps'])) . " (o la ruta de la carpeta).\n");
    exit(1);
}

function mmds_color_free(string $src): string {
    $src = preg_replace('~/\*.*?\*/~s', '', $src);       // comentarios CSS
    $src = preg_replace('~\{#.*?#\}~s', '', $src);       // comentarios Twig
    return preg_replace('~url\([^)]*\)~', 'url()', $src); // data URIs
}
