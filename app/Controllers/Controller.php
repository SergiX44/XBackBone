<?php

namespace App\Controllers;

use App\Database\DB;
use App\Web\Lang;
use App\Web\Session;
use App\Web\View;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

/**
 * @property Session|null session
 * @property View view
 * @property DB|null database
 * @property Logger|null logger
 * @property Filesystem|null storage
 * @property Lang lang
 * @property array config
 */
abstract class Controller
{
    /** @var Container */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $name
     *
     * @throws DependencyException
     * @throws NotFoundException
     *
     * @return mixed|null
     */
    public function __get($name)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
    }

    /**
     * @param $id
     *
     * @return int
     */
    protected function getUsedSpaceByUser($id): int
    {
        $medias = $this->database->query('SELECT `uploads`.`storage_path` FROM `uploads` WHERE `user_id` = ?', $id);

        $totalSize = 0;

        $filesystem = $this->storage;
        foreach ($medias as $media) {
            try {
                $totalSize += $filesystem->getSize($media->storage_path);
            } catch (FileNotFoundException $e) {
                $this->logger->error('Error calculating file size', ['exception' => $e]);
            }
        }

        return $totalSize;
    }

    /**
     * @param Request $request
     * @param $id
     * @param bool $authorize
     *
     * @throws HttpNotFoundException
     * @throws HttpUnauthorizedException
     *
     * @return mixed
     */
    protected function getUser(Request $request, $id, $authorize = false)
    {
        $user = $this->database->query('SELECT * FROM `users` WHERE `id` = ? LIMIT 1', $id)->fetch();

        if (!$user) {
            throw new HttpNotFoundException($request);
        }

        if ($authorize && $user->id !== $this->session->get('user_id') && !$this->session->get('admin', false)) {
            throw new HttpUnauthorizedException($request);
        }

        return $user;
    }
}
