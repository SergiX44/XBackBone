<?php

namespace App\Controllers;

use League\Flysystem\FileNotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminController extends Controller
{

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws FileNotFoundException
     */
    public function system(Request $request, Response $response): Response
    {
        $usersCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `users`')->fetch()->count;
        $mediasCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads`')->fetch()->count;
        $orphanFilesCount = $this->database->query('SELECT COUNT(*) AS `count` FROM `uploads` WHERE `user_id` IS NULL')->fetch()->count;

        $medias = $this->database->query('SELECT `uploads`.`storage_path` FROM `uploads`')->fetchAll();

        $totalSize = 0;

        $filesystem = $this->storage;
        foreach ($medias as $media) {
            $totalSize += $filesystem->getSize($media->storage_path);
        }

        return $this->view->render($response, 'dashboard/system.twig', [
            'usersCount' => $usersCount,
            'mediasCount' => $mediasCount,
            'orphanFilesCount' => $orphanFilesCount,
            'totalSize' => humanFileSize($totalSize),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'installed_lang' => $this->lang->getList(),
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function deleteOrphanFiles(Request $request, Response $response): Response
    {
        $orphans = $this->database->query('SELECT * FROM `uploads` WHERE `user_id` IS NULL')->fetchAll();

        $filesystem = $this->storage;
        $deleted = 0;

        foreach ($orphans as $orphan) {
            try {
                $filesystem->delete($orphan->storage_path);
                $deleted++;
            } catch (FileNotFoundException $e) {
            }
        }

        $this->database->query('DELETE FROM `uploads` WHERE `user_id` IS NULL');

        $this->session->alert(lang('deleted_orphans', [$deleted]));

        return redirect($response, 'system');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function applyLang(Request $request, Response $response): Response
    {
        $config = require BASE_DIR . 'config.php';

        if ($request->getParam('lang') !== 'auto') {
            $config['lang'] = $request->getParam('lang');
        } else {
            unset($config['lang']);
        }

        file_put_contents(BASE_DIR . 'config.php', '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';');

        $this->session->alert(lang('lang_set', [$request->getParam('lang')]));

        return redirect($response, 'system');
    }
}
