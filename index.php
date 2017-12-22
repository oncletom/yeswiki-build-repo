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

// WebHook
$request = new HttpRequest($_SERVER, $_POST);
if ($request->isHook()) {
    (new WebhookController($repo))->run($request->getContent());
    exit;
}

// Command line
if (isset($argv)) {
    $params = array();
    parse_str(implode('&', array_slice($argv, 1)), $params);
    (new ScriptController($repo))->run($params);
    exit;
} elseif(isset($_GET['action'])) {
    (new ScriptController($repo))->run($_GET);
    exit;
}

// Oups...
throw new \Exception("Bad request.", 1);
