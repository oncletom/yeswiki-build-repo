<?php
namespace YesWikiRepo;

class Repository
{
    public $localConf;
    public $repoConf;
    public $packages;

    public function __construct($configFile)
    {
        $this->packages = array();
        $this->localConf = $configFile;
    }

    public function loadRepoConf()
    {
        $fileContent = file_get_contents($this->localConf['config-address']);
        if ($fileContent === false) {
            return false;
        }
        $this->repoConf = json_decode($fileContent, true);
    }

    /**
     * Create folder if needed
     * @return [type] [description]
     */
    public function genRepoTree()
    {
        foreach ($this->repoConf as $version => $infos) {
            $folder = $this->localConf['repo-path'] . $version . '/';
            if (!is_dir($folder)) {
                mkdir($folder, 0755, true);
            }

            // Core package
            $name = 'yeswiki-' . $version;
            $this->packages[$version][$name] = new Package(
                $name,
                $infos['archive'],
                $infos['description'],
                $infos['documentation'],
                $this->localConf['composer-bin']
            );

            // Extensions
            foreach ($infos['extensions'] as $extName => $extInfos) {
                $name = 'extension-' . $extName;
                $this->packages[$version][$name] = new Package(
                    $name,
                    $extInfos['archive'],
                    $extInfos['description'],
                    $extInfos['documentation'],
                    $this->localConf['composer-bin']
                );
            }

            // Themes
            foreach ($infos['themes'] as $themeName => $themeInfos) {
                $name = 'theme-' . $themeName;
                $this->packages[$version][$name] = new Package(
                    $name,
                    $themeInfos['archive'],
                    $themeInfos['description'],
                    $themeInfos['documentation'],
                    $this->localConf['composer-bin']
                );
            }
        }
    }

    /**
     * return the json file describing the repository
     * @return [type] [description]
     */
    public function makeIndex()
    {
        foreach ($this->packages as $version => $packages) {
            $indexFile = new JsonFile(
                $this->localConf['repo-path'] . $version . '/packages.json'
            );
            foreach ($packages as $name => $package) {
                // No branch in name for core package.
                if (substr($name, 0, 7) === 'yeswiki') {
                    $name = "yeswiki";
                }
                $indexFile[$name] = $package->getinfos();
            }
            $indexFile->write();
        }
    }

    public function makeAllPackages()
    {
        foreach ($this->packages as $version => $packages) {
            foreach ($packages as $package) {
                $package->make($this->localConf['repo-path'] . $version . '/');
            }
        }
    }
}
