<?php
namespace YesWikiRepo;

class Repository
{
    public $localConf;
    public $repoConf;

    public $packages;

    public function __construct($confFile)
    {
        $fileContent = file_get_contents($confFile);
        $this->localConf = json_decode($fileContent, true);

        $this->packages = array();
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
                mkdir($folder);
            }

            // Core package
            $name = 'yeswiki-' . $version;
            $this->packages[$version][$name] = new Package(
                $name,
                new GitRepo ($infos['git-repo'], $infos['branch'])
            );

            // Extensions
            foreach ($infos['extensions'] as $extName => $extInfos) {
                $name = 'extension-' . $extName;
                $this->packages[$version][$name] = new Package(
                    $name,
                    new GitRepo ($extInfos['git-repo'], $extInfos['branch'])
                );
            }

            // Themes
            foreach ($infos['themes'] as $themeName => $themeInfos) {
                $name = 'theme-' . $themeName;
                $this->packages[$version][$name] = new Package(
                    $name,
                    new GitRepo ($themeInfos['git-repo'], $themeInfos['branch'])
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
            $data = array();
            foreach ($packages as $name => $package) {
                // No branch in name for core package.
                if (substr($name, 0, 7) === 'yeswiki') {
                    $name = "yeswiki";
                }
                $data[$name] = $package->getinfos();
            }
            file_put_contents(
                $this->localConf['repo-path'] . $version . '/packages.json',
                json_encode($data, JSON_PRETTY_PRINT)
            );
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

    /**
     * Purge old data
     * @return [type] [description]
     */
    public function purge()
    {
        foreach ($this->packages as $version => $packages) {
            foreach ($packages as $package) {
                $package->make($this->localConf['repo-path'] . $version . '/');
            }
        }
    }


}
