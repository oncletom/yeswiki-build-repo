<?php
namespace YesWikiRepo;

class Package extends Files
{
    public $name;
    public $gitRepo;

    public function __construct($name, $gitRepo)
    {
        $this->name = $name;
        $this->gitRepo = $gitRepo;
    }


    public function make($folder)
    {
        return $this->makeArchive($folder);
    }

    private function makeArchive($folder)
    {
        $filename = $folder . $this->getFilename();

        $clonePath = $this->gitRepo->clone(/*'builds/' . $this->name . '/'*/);

        print("ZIPAGE\n");
        $this->zip($clonePath, $filename);

        $this->makeMD5($filename);

        return $filename;
    }

    private function makeMD5($filename)
    {
        $md5 = md5_file($filename);
        return file_put_contents($filename . '.md5', $md5);
    }

    private function getFilename()
    {
        // TODO make it seriously
        return $this->name . '-'  .date("Y-m-d-1") . '.zip';
    }

}
