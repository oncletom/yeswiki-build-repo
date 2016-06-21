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
        $archive = $this->makeArchive($folder);
        $this->makeMD5($archive);

        return $archive;
    }

    private function makeArchive($folder)
    {
        $filename = $folder . $this->getFilename();

        $clonePath = $this->gitRepo->clone();
        $this->zip($clonePath, $filename, $this->name);
        //Supprime les fichiers temporaires
        $this->delete($clonePath);

        $this->makeMD5($filename);

        return $filename;
    }

    private function makeMD5($filename)
    {
        $md5 = md5_file($filename);
        $md5 .= ' ' . basename($filename);
        return file_put_contents($filename . '.md5', $md5);
    }

    private function getFilename()
    {
        // TODO make it seriously
        return $this->name . '-'  .date("Y-m-d-1") . '.zip';
    }

}
