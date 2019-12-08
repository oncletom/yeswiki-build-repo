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
    public function build($srcFile, $destDir, $pkgName, $pkgInfos)
    {
        //Télécharger l'archive dans un repertoire temporaire
        $tmpArchiveFile = $this->download($srcFile);
        // Pas de changement : on arrete tout !
        $srcFileMd5 = md5_file($tmpArchiveFile);
        if (isset($pkgInfos["md5SourceArchive"])
            and $pkgInfos["md5SourceArchive"] === $srcFileMd5) {
            throw new Exception("Source archive did not change.", 1);
        }

        $pkgInfos["md5SourceArchive"] = $srcFileMd5;

        // récupère la date de dernière modification
        $timestamp = $this->getBuildTimestamp($tmpArchiveFile);
        $pkgInfos['version'] = $this->formatTimestamp($timestamp);

        // renome le dossier racine de l'archive
        $this->renameRootFolder($tmpArchiveFile, $pkgName);

        // Extrait l'archive
        $extractedArchiveDir = $this->extract($tmpArchiveFile);
        unlink($tmpArchiveFile);

        // traitement des données (composer, etc.)
        // TODO uniquement pour les extension...
        $this->composer($extractedArchiveDir);

        $version = 1;
        if (isset($pkgInfos['file'])) {
            $version = $this->extractReleaseVersion($pkgInfos['file']) + 1;
        }
        $pkgInfos['version'] .= '-' . $version;

        if ($pkgInfos['repository'] == 'https://github.com/YesWiki/yeswiki') {
            $yeswikiVersion = str_replace('yeswiki-', '', $pkgName);
            // ajout des tools suplémentaires
            if (!empty($pkgInfos['extra-tools'])) {
                foreach ($pkgInfos['extra-tools'] as $nametool => $tool) {
                    $toolArchiveFile = $this->download($tool['archive']);
                    $extractedtool = $this->extract($toolArchiveFile);
                    rename(
                        $extractedtool.'/yeswiki-extension-'.$nametool.'-'.$tool['branch'],
                        $extractedArchiveDir.'/'.$yeswikiVersion.'/tools/'.$nametool
                    );
                    unlink($toolArchiveFile);
                }
            }
            // ajout des themes suplémentaires
            if (!empty($pkgInfos['extra-themes'])) {
                foreach ($pkgInfos['extra-themes'] as $nametheme => $theme) {
                    $themeArchiveFile = $this->download($theme['archive']);
                    $extractedtheme = $this->extract($themeArchiveFile);
                    rename(
                        $extractedtheme.'/yeswiki-theme-'.$nametheme.'-'.$theme['branch'],
                        $extractedArchiveDir.'/'.$yeswikiVersion.'/themes/'.$nametheme
                    );
                    unlink($themeArchiveFile);
                }
            }

            // change YesWiki version in the files
            $file = file_get_contents($extractedArchiveDir.'/'.$yeswikiVersion.'/includes/constants.php');
            $file = preg_replace('/define\("YESWIKI_VERSION", .*\);/Ui', 'define("YESWIKI_VERSION", \''.$yeswikiVersion.'\');', $file);
            $file = preg_replace('/define\("YESWIKI_RELEASE", .*\);/Ui', 'define("YESWIKI_RELEASE", \''.$this->formatTimestamp($timestamp).'-'.$version.'\');', $file);
            file_put_contents($extractedArchiveDir.'/'.$yeswikiVersion.'/includes/constants.php', $file);
        }

        // Construire l'archive finale
        $pkgInfos['file'] = $this->getFilename(
            $pkgName,
            $timestamp,
            $version
        );
        $archiveFile = $destDir . $pkgInfos['file'];

        $this->buildArchive($extractedArchiveDir, $archiveFile);
        (new File($extractedArchiveDir))->delete($extractedArchiveDir);

        // Générer le hash du fichier
        $this->makeMD5($archiveFile);

        return $pkgInfos;
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
        if ($zip->open($archiveFile) !== true) {
            throw new Exception("can't open archive : $archiveFile", 1);
        }

        $oldName = substr($zip->getNameIndex(0), 0, -1);
        $namePlusDate =  explode('-', $packageName, 2)[1];
        $newName = preg_replace('/-\d*-\d*-\d*-\d*$/', '', $namePlusDate);

        $index = 0;
        while ($filename = $zip->getNameIndex($index)) {
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
    private function getFilename($pkgName, $timestamp, $version)
    {
        // TODO Totalement foireux c'est moche et completement sujet a des bugs
        $filename = $pkgName . '-'
            . $this->formatTimestamp($timestamp) . '-'
            . $version . '.zip';
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

    private function extractReleaseVersion($filename)
    {
        // Supprime l'extension
        $filename = substr($filename, 0, (iconv_strlen('.zip') * -1));
        $explodedFilename = explode('-', $filename);
        return end($explodedFilename);
    }
}
