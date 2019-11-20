<?php

use App\Exception\Handlers\AppErrorHandler;
use App\Exception\Handlers\Renderers\HtmlErrorRenderer;
use App\Factories\ViewFactory;
use App\Middleware\InjectMiddleware;
use App\Middleware\LangMiddleware;
use App\Middleware\RememberMiddleware;
use App\Web\View;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use function DI\factory;
use function DI\get;
use function DI\value;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

if (!file_exists('config.php') && is_dir('install/')) {
    header('Location: ./install/');
    exit();
} else {
    if (!file_exists('config.php') && !is_dir('install/')) {
        exit('Cannot find the config file.');
    }
}

// Load the config
$config = array_replace_recursive([
    'app_name'    => 'XBackBone',
    'base_url'    => isset($_SERVER['HTTPS']) ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'],
    'debug'       => false,
    'maintenance' => false,
    'db'          => [
        'connection' => 'sqlite',
        'dsn'        => BASE_DIR.'resources/database/xbackbone.db',
        'username'   => null,
        'password'   => null,
    ],
    'storage' => [
        'driver' => 'local',
        'path'   => realpath(__DIR__.'/').DIRECTORY_SEPARATOR.'storage',
    ],
], require BASE_DIR.'config.php');

$builder = new ContainerBuilder();

if (!$config['debug']) {
    $builder->enableCompilation(BASE_DIR.'/resources/cache/di/');
    $builder->writeProxiesToFile(true, BASE_DIR.'/resources/cache/proxies');
}

$builder->addDefinitions([
    'config'    => value($config),
    View::class => factory(function (Container $container) {
        return ViewFactory::createAppInstance($container);
    }),
    'view' => get(View::class),
]);

$builder->addDefinitions(__DIR__.'/container.php');

$app = Bridge::create($builder->build());
$app->setBasePath(parse_url($config['base_url'], PHP_URL_PATH) ?: '');

if (!$config['debug']) {
    $app->getRouteCollector()->setCacheFile(BASE_DIR.'resources/cache/routes.cache.php');
}

$app->add(InjectMiddleware::class);
$app->add(LangMiddleware::class);
$app->add(RememberMiddleware::class);

// Permanently redirect paths with a trailing slash to their non-trailing counterpart
$app->add(function (Request $request, RequestHandler $handler) use (&$app, &$config) {
    $uri = $request->getUri();
    $path = $uri->getPath();

    if ($path !== $app->getBasePath().'/' && substr($path, -1) === '/') {
        // permanently redirect paths with a trailing slash
        // to their non-trailing counterpart
        $uri = $uri->withPath(substr($path, 0, -1));

        if ($request->getMethod() === 'GET') {
            return $app->getResponseFactory()
                ->createResponse(301)
                ->withHeader('Location', (string) $uri);
        } else {
            $request = $request->withUri($uri);
        }
    }

    return $handler->handle($request);
});

$app->addRoutingMiddleware();

// Configure the error handler
$errorHandler = new AppErrorHandler($app->getCallableResolver(), $app->getResponseFactory());
$errorHandler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware($config['debug'], true, true);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Load the application routes
require BASE_DIR.'app/routes.php';

return $app;
