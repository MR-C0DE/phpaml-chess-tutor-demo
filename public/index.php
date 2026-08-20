<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Isolate Tutor from other PHPAML applications running on different localhost ports.
// Browser cookies are scoped by host, not by port.
if (session_status() === PHP_SESSION_NONE) {
    session_name('TUTORSESSID');
}

if (PHP_SAPI === 'cli-server' && $requestPath !== '/' && is_file(__DIR__ . $requestPath)) {
    return false;
}

if ($requestPath === '/_aml/chess-tutor.js') {
    header('Content-Type: application/javascript; charset=UTF-8');
    header('Cache-Control: no-cache');
    readfile($root . '/src/views/assets/scripts/chess-tutor.js');
    return;
}

if ($requestPath === '/_aml/chess-core.js') {
    header('Content-Type: application/javascript; charset=UTF-8');
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($root . '/src/views/assets/vendor/chess.js');
    return;
}

if ($requestPath === '/_aml/stockfish.js') {
    header('Content-Type: application/javascript; charset=UTF-8');
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($root . '/src/views/assets/vendor/stockfish/stockfish-18-lite-single.js');
    return;
}

if ($requestPath === '/_aml/stockfish.wasm') {
    header('Content-Type: application/wasm');
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($root . '/src/views/assets/vendor/stockfish/stockfish-18-lite-single.wasm');
    return;
}

if (PHP_SAPI === 'cli-server' && $requestPath === '/_aml/live-reload') {
    $fingerprint = [];
    foreach ([$root . '/src', $root . '/configs', $root . '/database', __DIR__] as $watchedRoot) {
        if (!is_dir($watchedRoot)) { continue; }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($watchedRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['php', 'css', 'js', 'html', 'json', 'svg'], true)) {
                $fingerprint[] = $file->getPathname() . ':' . $file->getMTime() . ':' . $file->getSize();
            }
        }
    }
    sort($fingerprint);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode(['version' => sha1(implode('|', $fingerprint))], JSON_THROW_ON_ERROR);
    return;
}

$moduleAutoloader = $root . '/runtime/autoload.php';
if (is_file($moduleAutoloader)) {
    require_once $moduleAutoloader;
} else {
    $frameworkAutoloader = $root . '/runtime/framework/Autoloader.php';
    if (!is_file($frameworkAutoloader)) {
        http_response_code(500);
        exit('Application indisponible.');
    }
    require_once $frameworkAutoloader;
    \PHPAML\Autoloader::register(['PHPAML\\' => $root . '/runtime/framework', 'App\\' => $root . '/src']);
}

\PHPAML\Config\Env::load($root . '/.env');
// AML View integration
$viewApp = new \AML\View\FileApplication($root . '/src/views');
$config = require $root . '/configs/app.php';
$application = new \PHPAML\WebApplication($config);
if (!preg_match('#^/api(?:/|$)#', $requestPath)) {
    $request = \PHPAML\Http\Request::capture();
    $response = $application->handle($request, static function (\PHPAML\Http\Request $viewRequest) use ($application, $viewApp, $requestPath): \PHPAML\Http\Response {
        if ($requestPath === '/_aml/styles.css') {
            return new \PHPAML\Http\Response($viewApp->styles(), 200, [
                'Content-Type' => 'text/css; charset=utf-8',
                'Cache-Control' => 'no-cache',
            ]);
        }
        $status = 200;
        try {
            $result = $viewApp->mount($requestPath);
        } catch (OutOfBoundsException) {
            $status = 404;
            $result = $viewApp->notFound($requestPath);
        } catch (Throwable $error) {
            $status = 500;
            $result = $viewApp->error($requestPath, $error);
        }
        $session = $application->container()->get(\PHPAML\Session\Session::class);
        $head = $result instanceof \AML\View\PageResult ? $viewApp->head($requestPath) : '';
        $body = $result instanceof \AML\View\PageResult ? $result->rootHtml() : (string) $result;
        $cspNonce = \PHPAML\Security\CspNonce::from($viewRequest);
        $liveReloadMeta = PHP_SAPI === 'cli-server' ? '<meta name="aml-live-reload" content="/_aml/live-reload">' : '';
        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . $session->csrfMeta() . $liveReloadMeta . $head
            . '<link rel="icon" href="/favicon.svg"><link rel="stylesheet" href="/_aml/styles.css">'
            . '</head><body>' . $body . \AML\Engine\EngineRuntime::script($cspNonce)
            . '<script src="/_aml/chess-tutor.js" type="module"></script></body></html>';
        return \PHPAML\Http\Response::html($html, $status);
    });
    $response->send();
    return;
}
$config = require $root . '/configs/app.php';
(new \PHPAML\WebApplication($config))->run();
