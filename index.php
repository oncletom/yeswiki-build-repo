<?php
namespace YesWikiRepo;

$loader = require __DIR__ . '/vendor/autoload.php';

// Load command line parameters to $_GET
if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

$configFile = new JsonFile('local.config.json');
$configFile->read();
$repo = new Repository($configFile);

(new Controller($repo))->run($_GET);
