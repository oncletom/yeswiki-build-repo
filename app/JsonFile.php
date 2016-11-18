<?php
namespace YesWikiRepo;

use \Exception;

class JsonFile extends Collection
{
    private $file;

    public function __construct($file)
    {
        parent::__construct();
        $this->file = $file;
    }

    public function read()
    {
        $indexFileContent = file_get_contents($this->file);
        if ($indexFileContent === false) {
            throw new Exception("Error loading json file : " . $this->file, 1);
        }
        $this->elements = json_decode($indexFileContent, true);
        if ($this->elements === null) {
            throw new Exception("Error Processing json file" . $this->file, 1);
        }
    }

    public function write()
    {
        $fileContent = json_encode($this->elements, JSON_PRETTY_PRINT);
        if (file_put_contents($this->file, $fileContent) === false ) {
            throw new Exception("Error writing json file : " . $this->file, 1);
        }
    }

    public function fileExist()
    {
        if (is_file($this->file)) {
            return true;
        }
        return false;
    }
}
