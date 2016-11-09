<?php
namespace YesWikiRepo;

$loader = require __DIR__ . '/vendor/autoload.php';

// Load command line parameters to $_GET
if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

try {
    $repo = new Repository('local.config.json');
} catch (\Exception $e) {
    print("Configuration file error.");
    exit;
}

(new Controller($repo))->run($_GET);
