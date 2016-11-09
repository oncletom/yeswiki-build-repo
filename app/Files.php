<?php
namespace YesWikiRepo;

class Files
{
    protected function tmpdir($prefix = "")
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);

        if (is_file($path)) {
            unlink($path);
        }

        mkdir($path);
        return $path;
    }

    protected function delete($path)
    {
        if (is_file($path)) {
            if (unlink($path)) {
                return true;
            }
            return false;
        }
        if (is_dir($path)) {
            return $this->deleteFolder($path);
        }
    }

    protected function copy($src, $des)
    {
        if (is_file($des) or is_dir($des) or is_link($des)) {
            $this->delete($des);
        }
        if (is_file($src)) {
            return copy($src, $des);
        }
        if (is_dir($src)) {
            if (!mkdir($des)) {
                return false;
            }
            return $this->copyFolder($src, $des);
        }
        return false;
    }

    protected function isWritable($path)
    {
        // la destination n'existe pas et droits d'écriture sur le repertoire
        // de destination
        if (!file_exists($path) and is_writable(dirname($path))) {
            return true;
        }

        if (is_file($path)) {
            return is_writable($path);
        }

        if (is_dir($path)) {
            return $this->isWritableFolder($path);
        }

        // TODO Gérer les liens
        return false;
    }

    protected function download($sourceUrl, $prefix = "")
    {
        $downloadedFile = tempnam(sys_get_temp_dir(), $prefix);
        file_put_contents($downloadedFile, fopen($sourceUrl, 'r'));
        return $downloadedFile;
    }

    private function isWritableFolder($path)
    {
        $file2ignore = array('.', '..');
        if ($res = opendir($path)) {
            while (($file = readdir($res)) !== false) {
                if (!in_array($file, $file2ignore)) {
                    if (!$this->isWritable($path . '/' . $file)) {
                        // TODO remonter les fichiers/dossier qui posent
                        // problèmes
                        return false;
                    }
                }
            }
            closedir($res);
        }
        return true;
    }

    private function deleteFolder($path)
    {
        $file2ignore = array('.', '..');
        if ($res = opendir($path)) {
            while (($file = readdir($res)) !== false) {
                if (!in_array($file, $file2ignore)) {
                    $this->delete($path . '/' . $file);
                }
            }
            closedir($res);
        }
        rmdir($path);
        return true;
    }

    private function copyFolder($srcPath, $desPath)
    {
        $file2ignore = array('.', '..');
        if ($res = opendir($srcPath)) {
            while (($file = readdir($res)) !== false) {
                if (!in_array($file, $file2ignore)) {
                    $this->copy($srcPath . '/' . $file, $desPath . '/' . $file);
                }
            }
            closedir($res);
        }
        return true;
    }

    protected function zip($srcPath, $destZipFile, $zipPath = null)
    {
        $zip = new \ZipArchive;
        $zip->open($destZipFile, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);
        $this->zipFolder($srcPath, $zip, $zipPath, $srcPath);
        $zip->close();
        return $destZipFile;
    }

    private function zipFolder($srcPath, $zip, $zipPath, $originPath)
    {
        $localName = str_replace($originPath, $zipPath, $srcPath);

        if (is_file($srcPath)) {
            $zip->addFile($srcPath, $localName);
            return;
        }

        if (is_dir($srcPath)) {
            $file2ignore = array('.', '..', '.git');
            if ($res = opendir($srcPath)) {
                while (($file = readdir($res)) !== false) {
                    if (!in_array($file, $file2ignore)) {
                        $fullpath = $srcPath . '/' . $file;
                        $this->zipFolder($fullpath, $zip, $zipPath, $originPath);
                    }
                }
                closedir($res);
            }
            return;
        }
    }
}
