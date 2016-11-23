<?php
namespace YesWikiRepo;

use \Exception;

abstract class Controller
{
    protected $repo;

    public function __construct($repo)
    {
        $this->repo = $repo;
    }

    abstract public function run($params);
}
