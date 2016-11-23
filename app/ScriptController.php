<?php
namespace YesWikiRepo;

use \Exception;

class ScriptController
{
    private $repo;

    public function __construct($repo)
    {
        $this->repo = $repo;
    }

    public function run($params)
    {
        if (isset($params['action'])) {
            $this->repo->load();
            switch ($params['action']) {
                case 'init':
                    try {
                        $this->repo->init();
                    } catch (Exception $e) {
                        print($e->getMessage());
                    }
                    return;
                case 'update':
                    if (!isset($params['target'])) {
                        print("Target not defined");
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
