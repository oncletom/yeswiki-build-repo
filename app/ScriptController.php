<?php
namespace YesWikiRepo;

use \Exception;

class ScriptController extends Controller
{
    public function run($params)
    {
        if (isset($params['action'])) {
            $this->repo->load();
            switch ($params['action']) {
                case 'init':
                    $this->repo->init();
                    return;
                case 'update':
                    if (!isset($params['target'])) {
                        throw new Exception("Target not defined", 1);
                    }
                    $this->repo->update($params['target']);
                    return;
                case 'purge':
                    $this->repo->purge();
                    return;
            }
        }
    }
}
