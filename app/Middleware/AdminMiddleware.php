<?php

namespace App\Middleware;

use App\Exceptions\UnauthorizedException;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminMiddleware extends Middleware
{
    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     * @throws UnauthorizedException
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        if (!$this->database->query('SELECT `id`, `is_admin` FROM `users` WHERE `id` = ? LIMIT 1', [$this->session->get('user_id')])->fetch()->is_admin) {
            $this->session->set('admin', false);
            throw new UnauthorizedException();
        }

        return $next($request, $response);
    }
}
