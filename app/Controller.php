<?php
namespace YesWikiRepo;

class Controller
{
    private $repo;

    public function __construct($repo)
    {
        $this->repo = $repo;
    }

    public function run($params)
    {
        if ($this->init() === false) {
            return;
        }

        var_dump($params);
    }

    private function init()
    {
        if ($this->repo->loadRepoConf() === false) {
            print('erreur chargement de la configuration des dépôts.');
            return false;
        }
        return true;
    }

    private function makeAllPackages()
    {
        $this->repo->genRepoTree();
        $this->repo->makeAllPackages();
    }
}
