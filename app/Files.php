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

    protected function download($sourceUrl)
    {
        $this->downloadedFile = tempnam(sys_get_temp_dir(), $this::PREFIX_FILENAME);
        file_put_contents($this->downloadedFile, fopen($sourceUrl, 'r'));
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

    protected function zip($srcPath, $destZipFile)
    {
        print("Dans ZIP\n");
        $zip = new \ZipArchive;
        $zip->open($destZipFile, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);

        var_dump($srcPath);
        var_dump($destZipFile);

        $this->zipFolder($srcPath, $zip);
        $zip->close();
        return $destZipFile;
    }

    private function zipFolder($srcPath, $zip)
    {
        if (is_file($srcPath)) {
            print("C'est un fichier : " . $srcPath . "\n\n");
            $zip->addFile($srcPath);
            return;
        }

        if (is_dir($srcPath)) {
            print("C'est un dossier : " . $srcPath . "\n\n");
            $file2ignore = array('.', '..', '.git');
            $zip->addEmptyDir($srcPath);
            if ($res = opendir($srcPath)) {
                while (($file = readdir($res)) !== false) {
                    if (!in_array($file, $file2ignore)) {
                        $fullpath = $srcPath . '/' . $file;
                        print("Ajoute : " . $fullpath . "\n\n");
                        $this->zipFolder($fullpath, $zip);
                    }
                }
                closedir($res);
            }
            return;
        }

        print("NE DEVRAIS JAMAIS ARRIVER !! " . $srcPath . "\n\n");
    }
}
