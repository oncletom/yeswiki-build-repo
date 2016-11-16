<?php
namespace YesWikiRepo;

use \Files\File;
use \Exception;
use \ZipArchive;

class PackageBuilder
{
    private $composerFile;

    public function __construct($composerFile)
    {
        $this->composerFile = $composerFile;
    }

    /**
     * Build a package
     * @param  string $source Source file's address for package.
     * @return array          Infos about package.
     */

    /**
     * [build description]
     * @param  string $srcFile      Source archive address
     * @param  string $destDir      Directory where to put package
     * @param  string $packageName  Package's name
     * @param  array  $packageInfos previous version information.
     * @return [type]               updated informations
     */
    public function build($srcFile, $destDir, $packageName, $packageInfos)
    {
        //Télécharger l'archive dans un repertoire temporaire
        $tmpArchiveFile = $this->download($srcFile);

        // Pas de changement : on arrete tout !
        $srcFileMd5 = md5_file($tmpArchiveFile);
        if (isset($packageInfos["md5SourceArchive"])
            and $packageInfos["md5SourceArchive"] === $srcFileMd5) {
            throw new Exception("Source archive don't change.", 1);
        }

        $packageInfos["md5SourceArchive"] = $srcFileMd5;

        // récupère la date de dernière modification
        $timestamp = $this->getBuildTimestamp($tmpArchiveFile);
        $packageInfos['version'] = $this->formatTimestamp($timestamp);

        // renome le dossier racine de l'archive
        $this->renameRootFolder($tmpArchiveFile, $packageName);

        // Extrait l'archive
        $extractedArchiveDir = $this->extract($tmpArchiveFile);
        unlink($tmpArchiveFile);

        // traitement des données (composer, etc.)
        // TODO uniquement pour les extension...
        $this->composer($extractedArchiveDir);

        // Construire l'archive finale
        $packageInfos['file'] = $this->getFilename(
            $packageName,
            $timestamp,
            $destDir
        );
        $archiveFile = $destDir . $packageInfos['file'];
        $this->buildArchive($extractedArchiveDir, $archiveFile);
        (new File($extractedArchiveDir))->delete($extractedArchiveDir);

        // Générer le hash du fichier
        $this->makeMD5($archiveFile);

        return $packageInfos;
    }

    /**
     * Download file to temporary filename
     * @param  string $sourceUrl Address where file to download is.
     * @param  string $prefix    Prefix for temporary filename
     * @return string            path to downloaded file.
     */
    private function download($sourceUrl, $prefix = "")
    {
        $downloadedFile = tempnam(sys_get_temp_dir(), $prefix);
        file_put_contents($downloadedFile, fopen($sourceUrl, 'r'));
        return $downloadedFile;
    }

    /**
     * Load last file modification from archive
     * @param  [type] $archiveFile [description]
     * @return [type]          [description]
     */
    private function getBuildTimestamp($archiveFile)
    {
        $zip = new ZipArchive;
        $zip->open($archiveFile);
        $fileInfos = $zip->statIndex(0, ZipArchive::FL_UNCHANGED);
        $zip->close();
        return $fileInfos['mtime'];
    }

    /**
     * Rename root folder in ZipArchive
     * @param  string $archiveFile path to ZipArchive file
     * @return void
     */
    private function renameRootFolder($archiveFile, $packageName)
    {
        $zip = new ZipArchive;
        if ($zip->open($archiveFile) !== TRUE) {
            throw new Exception("can't open archive : $archiveFile", 1);
        }

        // TODO Bug potentiel avec les extension ayant plusieur tirets dans le
        // nom
        $oldName = substr($zip->getNameIndex(0), 0, -1);
        $newName = explode('-', $packageName)[1];

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

    /**
     * Extrait l'archive dans un repertoire temporaire et retourne le chemin
     * vers ce repertoire
     * @param  string $archive path to archive file
     * @return string          Dossier où a été extrait l'archive
     */
    private function extract($archive)
    {
        $tmpDir = $this->tmpdir();
        $zip = new ZipArchive;
        $zip->open($archive);
        $zip->extractTo($tmpDir);
        $zip->close();
        return $tmpDir;
    }

    /**
     * Make a temporary directory
     * @param  string $prefix prefix to temporary directory name
     * @return [type]         path to created directory;
     */
    protected function tmpdir($prefix = "")
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);

        if (is_file($path)) {
            unlink($path);
        }

        mkdir($path);
        return $path;
    }

    /**
     * Execute composer in every sub folder containing an "composer.json" file
     * @param  string $path Directory to scan
     * @return void
     */
    private function composer($path)
    {
        $command = $this->composerFile
            . " install --no-dev --optimize-autoloader --working-dir=";
        $dirList = new \RecursiveDirectoryIterator(
            $path,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );
        $fileList = new \RecursiveIteratorIterator($dirList);
        foreach ($fileList as $file) {
            if (basename($file) === "composer.json") {
                exec($command . "\"" . dirname($file) . "\"");
            }
        }
    }

    /**
     * Build finale Archive
     * @param  string $sourceDir   Source Directory
     * @param  string $archiveFile Archive file name
     * @return string              path to maked archive
     */
    private function buildArchive($sourceDir, $archiveFile)
    {
        $zip = new \ZipArchive;
        $zip->open($archiveFile, \ZipArchive::CREATE);

        $dirlist = new \RecursiveDirectoryIterator(
            $sourceDir,
            \RecursiveDirectoryIterator::SKIP_DOTS
        );
        $filelist = new \RecursiveIteratorIterator($dirlist);
        foreach ($filelist as $file) {
            $internalFile = str_replace($sourceDir . '/', "", $file);
            $zip->addFile($file, $internalFile);
        }
        $zip->close();
    }

    /**
     * Generate final archive filename with path
     * @param  [type] $destDir [description]
     * @return [type]         [description]
     */
    private function getFilename($packageName, $timestamp, $destDir)
    {
        // TODO Totalement foireux c'est moche et completement sujet a des bugs
        $version = 1;
        $firstPartFilename = $packageName . '-' . $this->formatTimestamp($timestamp) . '-';
        $filename = $firstPartFilename . $version . '.zip';
        while (file_exists($destDir . $filename)) {
            $version++;
            $filename = $firstPartFilename . $version . '.zip';
        }
        return $filename;
    }

    private function formatTimestamp($timestamp)
    {
        return date("Y-m-d", $timestamp);
    }

    private function makeMD5($filename)
    {
        $md5 = md5_file($filename);
        $md5 .= ' ' . basename($filename);
        return file_put_contents($filename . '.md5', $md5);
    }
}
