<?php
namespace YesWikiRepo;

class Package extends Files
{
    const DEFAULT_VERSION = "0000-00-00-0";

    public $name;
    public $archive;
    public $documentation;
    public $description;

    private $composerPath;

    private $filename = null;
    private $version = self::DEFAULT_VERSION;

    public function __construct(
        $name,
        $archive,
        $description,
        $documentation,
        $composerPath
    )
    {
        $this->name = $name;
        $this->archive = $archive;
        $this->description = $description;
        $this->documentation = $documentation;
        $this->composerPath = $composerPath;
    }

    /**
     * Add archive to repository
     * @param  string $folder path where to put archive
     * @return [type]         [description]
     */
    public function make($folder)
    {
        //Télécharger l'archive dans un repertoire temporaire
        $tmpArchivePath = $this->download($this->archive, $this->name . '_');

        // récupère la date de dernière modification
        $timestamp = $this->getBuildTimestamp($tmpArchivePath);

        $this->renameRootFolder($tmpArchivePath);
        $pathExtractedArchive = $this->extract($tmpArchivePath);
        unlink($tmpArchivePath);

        // traitement des données (composer, etc.)
        $this->composer($pathExtractedArchive);

        // Construire l'archive finale
        $archive = $folder . $this->getFilename($timestamp, $folder);
        $this->buildArchive($pathExtractedArchive, $archive);
        $this->delete($pathExtractedArchive);

        // Générer le hash du fichier
        $this->makeMD5($archive);

        return $archive;
    }

    /**
     * Send back package's informations
     * @return [type] [description]
     */
    public function getInfos()
    {
        return array(
            "version" => $this->version,
            "file" => $this->filename,
            "documentation" => $this->documentation,
            "description" => $this->description,
        );
    }

    /**
     * Extrait l'archive dans un repertoire temporaire et retourne le chemin
     * vers ce repertoire
     * @param  string $archive Chemin vers l'archive a extraire
     * @return string          Dossier où a été extrait l'archive
     */
    private function extract($archive)
    {
        $tmpDir = $this->tmpdir($this->name . '_');
        $zip = new \ZipArchive;
        $zip->open($archive);
        $zip->extractTo($tmpDir);
        $zip->close();
        return $tmpDir;
    }

    private function getBuildTimestamp($archive)
    {
        $zip = new \ZipArchive;
        $zip->open($archive);
        $fileInfos = $zip->statIndex(0, \ZipArchive::FL_UNCHANGED);
        $zip->close();
        return $fileInfos['mtime'];
    }

    /**
     * Construit l'archive finale
     * @param  string $tmpArchivePath     chemin de l'archive téléchargée depuis
     *                                    le dépot
     * @param  string $finalArchiveFolder Dossier dans lequel déposé l'archive
     *                                    finale
     * @return string                     Le chemin vers l'archive générée
     */
    private function buildArchive($sourcePath, $archivePath)
    {
        $zip = new \ZipArchive;
        $zip->open($archivePath, \ZipArchive::CREATE);

        $dirlist = new \RecursiveDirectoryIterator(
            $sourcePath,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );
        $filelist = new \RecursiveIteratorIterator($dirlist);
        foreach ($filelist as $file) {
            $internalFile = str_replace($sourcePath . '/', "", $file);
            $zip->addFile($file, $internalFile);
        }

        $zip->close();
    }

    /**
     * Change le nom du dossier racine de l'archive
     * @param  [type] $zip [description]
     * @return [type]      [description]
     */
    private function renameRootFolder($archive)
    {
        $zip = new \ZipArchive;
        if ($zip->open($archive) !== TRUE) {
            throw new Exception("can't open archive : $archive", 1);
        }

        $oldName = substr($zip->getNameIndex(0), 0, -1);
        $newName = explode('-', $this->name)[1];

        $index = 0;
        while($filename = $zip->getNameIndex($index)){
            $zip->renameIndex(
                $index,
                str_replace($oldName, $newName, $filename)
            );
            $index++;
        }
        $zip->close();
    }

    private function makeMD5($filename)
    {
        $md5 = md5_file($filename);
        $md5 .= ' ' . basename($filename);

        return file_put_contents($filename . '.md5', $md5);
    }

    /**
     * Execute composer dans tous les dossier ou un fichier "composer.json" est
     * présent.
     * @param  string $path Dossier a passer en revue.
     * @return string       [description]
     */
    private function composer($path)
    {
        $command = $this->composerPath
            . " install --no-dev --optimize-autoloader --working-dir=";
        $dirList = new \RecursiveDirectoryIterator(
            $path,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );
        $fileList = new \RecursiveIteratorIterator($dirList);
        foreach ($fileList as $file) {
            if (basename($file) === "composer.json") {
                print("Execute composer dans : " . dirname($file) . "\n");
                exec($command . "\"" . dirname($file) . "\"");
            }
        }
    }

    /**
     * Generate final archive filename with path
     * @param  [type] $folder [description]
     * @return [type]         [description]
     */
    private function getFilename($timestamp, $folder)
    {
        if (is_null($this->filename)) {
            $version = 1;
            $firstPartFilename = $this->name . date("-Y-m-d-", $timestamp);
            $filename = $firstPartFilename . $version . '.zip';
            while (file_exists($folder . $filename)) {
                $version++;
                $filename = $firstPartFilename . $version . '.zip';
            }

            $this->version = date("Y-m-d-", $timestamp) . $version;
            $this->filename = $filename;
        }
        return $this->filename;
    }
}
