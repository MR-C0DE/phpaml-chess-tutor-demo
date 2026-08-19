<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/runtime/autoload.php';

$tests = [
    'home page renders' => static function () use ($root): void {
        $application = new \AML\View\FileApplication($root . '/src/views');
        $result = $application->mount('/');
        if (!$result instanceof \AML\View\PageResult) {
            throw new RuntimeException('The home route did not return a page.');
        }
        if (!str_contains($result->rootHtml(), 'Play a move. Understand the idea.')) {
            throw new RuntimeException('The home page content is missing.');
        }
    },
    'about route renders' => static function () use ($root): void {
        $result = (new \AML\View\FileApplication($root . '/src/views'))->mount('/about');
        if (!$result instanceof \AML\View\PageResult) {
            throw new RuntimeException('The about route did not return a page.');
        }
    },
    'stylesheets are collected' => static function () use ($root): void {
        $styles = (new \AML\View\FileApplication($root . '/src/views'))->styles();
        if (!str_contains($styles, '.tutor-app') || !str_contains($styles, '.site-nav')) {
            throw new RuntimeException('AML View stylesheets were not collected.');
        }
    },
    'unknown routes use the declarative 404 page' => static function () use ($root): void {
        $application = new \AML\View\FileApplication($root . '/src/views');
        try {
            $application->mount('/missing-page');
            throw new RuntimeException('An unknown route should not resolve.');
        } catch (OutOfBoundsException) {
            $html = $application->notFound('/missing-page');
            if (!str_contains($html, 'This page does not exist')) {
                throw new RuntimeException('The declarative 404 page is missing.');
            }
        }
    },
];

$failed = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        fwrite(STDOUT, "✓ AML View: {$name}" . PHP_EOL);
    } catch (Throwable $error) {
        fwrite(STDERR, "✗ AML View: {$name}: {$error->getMessage()}" . PHP_EOL);
        $failed++;
    }
}

exit($failed === 0 ? 0 : 1);
