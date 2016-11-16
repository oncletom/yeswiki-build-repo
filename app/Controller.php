<?php
namespace YesWikiRepo;

use \Exception;

class Controller
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
                case 'clear':
                    $this->repo->clear();
                    return;
            }
        }
    }
}
