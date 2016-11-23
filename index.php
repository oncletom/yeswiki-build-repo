<?php
namespace YesWikiRepo;

$loader = require __DIR__ . '/vendor/autoload.php';

set_exception_handler(function($e) {
	header('HTTP/1.1 500 Internal Server Error');
	echo htmlSpecialChars($e->getMessage());
	die();
});

openlog('[YesWikiRepo] ', LOG_CONS|LOG_PERROR, LOG_SYSLOG);

$configFile = new JsonFile('local.config.json');
$configFile->read();
$repo = new Repository($configFile);

if (isset($_SERVER['HTTP_CONTENT_TYPE'])
    and isset($_SERVER['HTTP_X_GITHUB_EVENT'])) {
    $params = json_decode(file_get_contents('php://input'), true);
    if ($params === false) {
        throw new \Exception("Error reading webhook", 1);

    }
    (new WebhookController($repo))->run($params);
}

// Load command line parameters to $_GET
if (isset($argv)) {
    $params = array();
    parse_str(implode('&', array_slice($argv, 1)), $params);
    (new ScriptController($repo))->run($params);
}

throw new \Exception("Bad request.", 1);
