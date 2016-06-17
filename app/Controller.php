<?php
namespace YesWikiRepo;

class Controller
{
    private $repo;

    public function __construct($repo)
    {
        $this->repo = $repo;
    }

    public function run()
    {
        if ($this->init() === false) {
            return;
        }

        $this->repo->makeAllPackages();

    }

    private function init()
    {
        if ($this->repo->loadRepoConf() === false) {
            print('erreur chargement de la configuration des dépôts.');
            return false;
        }
        $this->repo->genRepo();
        return true;
    }
}
