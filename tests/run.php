<?php

declare(strict_types=1);

use PHPAML\Autoloader;
use PHPAML\Container;
use PHPAML\Http\Request;
use PHPAML\Http\Response;
use PHPAML\WebApplication;
use PHPAML\Routing\Router;
use PHPAML\Middleware\MiddlewareInterface;
use PHPAML\Validation\Validator;

$root = dirname(__DIR__);
$installedAutoloader = $root . '/runtime/autoload.php';
if (is_file($installedAutoloader)) {
    require_once $installedAutoloader;
} else {
    require_once $root . '/runtime/framework/Autoloader.php';
    Autoloader::register([
        'PHPAML\\' => $root . '/runtime/framework',
        'App\\' => $root . '/app',
    ]);
}

$tests = [];

function test(string $name, Closure $test): void
{
    global $tests;
    $tests[$name] = $test;
}

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

interface TestContract
{
}

final class TestService implements TestContract
{
}

final class RouteTestController
{
    public function show(Request $request): Response
    {
        return Response::json(['id' => $request->attribute('id')]);
    }
}

final class RouteTestMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Closure $next): Response
    {
        return $next($request)->withHeader('X-Test-Middleware', 'active');
    }
}

test('Request normalise la méthode, le chemin et les entrées', function (): void {
    $request = new Request('post', '/contact/', ['page' => '1'], ['name' => 'AML']);
    expect($request->method() === 'POST', 'La méthode HTTP est incorrecte.');
    expect($request->path() === '/contact', 'Le chemin est incorrect.');
    expect($request->input('name') === 'AML', 'Le corps de la requête est inaccessible.');
});

test('Response JSON définit le contenu et le type attendus', function (): void {
    $response = Response::json(['ok' => true], 201);
    expect($response->status() === 201, 'Le statut est incorrect.');
    expect($response->content() === '{"ok":true}', 'Le JSON est incorrect.');
    expect(str_contains($response->headers()['Content-Type'], 'application/json'), 'Le type JSON manque.');
});

test('Response construit une redirection', function (): void {
    $response = Response::redirect('/connexion', 303);
    expect($response->status() === 303, 'Le statut de redirection est incorrect.');
    expect($response->headers()['Location'] === '/connexion', 'La destination est incorrecte.');
});

test('Container construit automatiquement une classe sans dépendance', function (): void {
    $service = (new Container())->get(stdClass::class);
    expect($service instanceof stdClass, 'Le conteneur ne construit pas le service.');
});

test('Container associe une interface et conserve un singleton', function (): void {
    $container = new Container();
    $container->singleton(TestContract::class, TestService::class);
    $first = $container->get(TestContract::class);
    $second = $container->get(TestContract::class);
    expect($first instanceof TestService, 'L’interface n’est pas résolue.');
    expect($first === $second, 'Le singleton n’est pas conservé.');
});

test('Router gère paramètres, noms et middleware par route', function (): void {
    $router = new Router(new Container());
    $router->add('GET', '/users/{id}', [RouteTestController::class, 'show'], [RouteTestMiddleware::class], 'users.show');
    $response = $router->dispatch(new Request('GET', '/users/42'));
    expect($response->content() === '{"id":"42"}', 'Le paramètre dynamique est incorrect.');
    expect($response->headers()['X-Test-Middleware'] === 'active', 'Le middleware de route manque.');
    expect($router->url('users.show', ['id' => 7]) === '/users/7', 'La route nommée est incorrecte.');
});

test('Router gère les groupes et retourne 405', function (): void {
    $router = new Router(new Container());
    $router->group('/api', [
        'GET /users/{id}' => [RouteTestController::class, 'show'],
    ]);
    expect($router->dispatch(new Request('GET', '/api/users/8'))->status() === 200, 'Le groupe de routes est incorrect.');
    $response = $router->dispatch(new Request('POST', '/api/users/8'));
    expect($response->status() === 405, 'La mauvaise méthode doit retourner 405.');
    expect($response->headers()['Allow'] === 'GET', 'L’en-tête Allow est incorrect.');
});

test('Validator applique les règles courantes', function (): void {
    $validator = new Validator();
    expect($validator->validate(['email' => 'test@example.com'], ['email' => ['required', 'email']]), 'Une adresse valide est refusée.');
    expect(!$validator->validate(['email' => 'invalide'], ['email' => ['email']]), 'Une adresse invalide est acceptée.');
    expect(isset($validator->errors()['email']), 'L’erreur de validation manque.');
});

test('AML View rend la page Tutor Chess', function () use ($root): void {
    $viewApp = new \AML\View\FileApplication($root . '/src/views');
    $page = $viewApp->mount('/');
    $html = $page instanceof \AML\View\PageResult ? $page->rootHtml() : (string) $page;
    expect(str_contains($html, 'Play a move. Understand the idea.'), 'La page Tutor Chess est absente.');
    expect(str_contains($html, 'chess-board'), "L'échiquier AML View est absent.");
});

test('WebApplication rend la route API principale', function (): void {
    $config = require dirname(__DIR__) . '/configs/app.php';
    $response = (new WebApplication($config))->handle(new Request('GET', '/api/health'));
    expect($response->status() === 200, 'La route API principale ne retourne pas 200.');
    expect(str_contains($response->content(), '"status":"ok"'), 'La réponse de santé est incorrecte.');
    expect(($response->headers()['X-Frame-Options'] ?? null) === 'DENY', 'Les en-têtes de sécurité manquent.');
});

test('WebApplication retourne une réponse 404', function (): void {
    $config = require dirname(__DIR__) . '/configs/app.php';
    $response = (new WebApplication($config))->handle(new Request('GET', '/inconnue'));
    expect($response->status() === 404, 'Une route inconnue doit retourner 404.');
});

test('WebApplication protège les méthodes d’écriture avec CSRF par défaut', function (): void {
    $config = require dirname(__DIR__) . '/configs/app.php';
    $config['routes']['POST /protected'] = [RouteTestController::class, 'show'];
    $response = (new WebApplication($config))->handle(new Request('POST', '/protected'));
    expect($response->status() === 419, 'Une requête POST sans jeton CSRF doit être refusée.');
});

$passed = 0;
$failed = 0;

foreach ($tests as $name => $testCase) {
    try {
        $testCase();
        fwrite(STDOUT, "✓ {$name}" . PHP_EOL);
        $passed++;
    } catch (Throwable $error) {
        fwrite(STDERR, "✗ {$name}: {$error->getMessage()}" . PHP_EOL);
        $failed++;
    }
}

fwrite(STDOUT, PHP_EOL . "{$passed} réussi(s), {$failed} échec(s)." . PHP_EOL);
exit($failed === 0 ? 0 : 1);
