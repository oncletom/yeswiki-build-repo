<?php
namespace YesWikiRepo;

$loader = require __DIR__ . '/vendor/autoload.php';

try {
    $repo = new Repository('local.config.json');
} catch (Exception $e) {
    print("Configuration file error.");
    exit;
}

(new Controller($repo))->run();
