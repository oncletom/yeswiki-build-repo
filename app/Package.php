<?php
namespace YesWikiRepo;

class Package extends Files
{
    public $name;
    public $gitRepo;
    public $documentation;
    public $description;

    private $filename = "";
    private $version = "0000-00-00-0";

    public function __construct($name, $gitRepo, $description, $documentation)
    {
        $this->name = $name;
        $this->gitRepo = $gitRepo;
        $this->description = $description;
        $this->documentation = $documentation;
    }

    /**
     * Generate
     * @param  string $folder path where to put archive
     * @return [type]         [description]
     */
    public function make($folder)
    {
        $archive = $this->makeArchive($folder);
        $this->makeMD5($archive);

        return $archive;
    }

    public function getInfos()
    {
        return array(
            "version" => $this->version,
            "file" => $this->filename,
            "documentation" => $this->documentation,
            "description" => $this->description,
        );
    }

    private function makeArchive($folder)
    {
        $filename = $this->defineFilename($folder);

        $clonePath = $this->gitRepo->clone();
        $this->zip($clonePath, $folder . $filename, $this->name);
        //Supprime les fichiers temporaires
        $this->delete($clonePath);

        $this->lastFile = $filename;

        return $folder . $filename;
    }

    private function makeMD5($filename)
    {
        $md5 = md5_file($filename);
        $md5 .= ' ' . basename($filename);

        return file_put_contents($filename . '.md5', $md5);
    }

    private function defineFilename($folder)
    {
        if ($this->filename === "") {
            $version = 1;

            $filename = $folder . $this->name . date("-Y-m-d-")
                                . $version . '.zip';

            while (file_exists($filename)) {
                $version++;
                $filename = $folder . $this->name . date("-Y-m-d-")
                                    . $version . '.zip';
            }

            $this->version = date("Y-m-d-") . $version;
            $this->filename = basename($filename);
        }
        return $this->filename;
    }
}
