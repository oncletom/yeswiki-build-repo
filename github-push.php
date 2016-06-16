<?php

/*
 * Endpoint for Github Webhook URLs
 *
 * see: https://help.github.com/articles/post-receive-hooks
 *
 */

header('Content-Type: text/html; charset=utf-8');

function startsWith($haystack, $needle)
{
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}


function run()
{
    global $rawInput;

    // read config.json
    $config_filename = 'config.json';
    if (!file_exists($config_filename)) {
        throw new Exception("Fichier de configuration non trouvé : ".$config_filename);
    }
    $config = json_decode(file_get_contents($config_filename), true);

    //TODO : sécurité : vérifier qu'on vient de github.com (il y a un password possible)
    $postBody = $_POST['payload'];
    $payload = json_decode($postBody);

    if ($payload) {
        if (isset($config['email'])) {
            $headers = 'From: '.$config['email']['from']."\r\n";
            // $headers .= 'CC: ' . $payload->pusher->email . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        foreach ($config['endpoints'] as $endpoint) {
            // check if the push came from the right repository and branch
            if ($payload->repository->url == 'https://github.com/' . $endpoint['repo']
              && ($payload->ref == 'refs/heads/' . $endpoint['branch']
              or (startsWith($payload->ref, 'refs/tags/') and $payload->base_ref == 'refs/heads/' . $endpoint['branch']))) {
                $run_update_command = false;
                $options = '';
                if ($endpoint['repo'] == 'YesWiki/yeswiki') {
                    $run_update_command = true;
                } elseif ($endpoint['repo'] == 'YesWiki/yeswiki-themes'
                    or $endpoint['repo'] == 'YesWiki/yeswiki-external-extensions') {
                    // pour les themes et tools, on regarde les changements pour chaque dossier
                    // et on ne met a jour que ces dossiers
                    $dir_to_update = array();
                    foreach ($payload->commits as $commit) {
                        foreach ($commit->added as $added) {
                            $added = explode('/', $added);
                            if (count($added) > 1 and !in_array($added[0], $dir_to_update)) {
                                $dir_to_update[] = $added[0];
                            }
                        }
                        foreach ($commit->modified as $modified) {
                            $modified = explode('/', $modified);
                            if (count($modified) > 1 and !in_array($modified[0], $dir_to_update)) {
                                $dir_to_update[] = $modified[0];
                            }
                        }
                    }
                    if (count($dir_to_update) > 0) {
                        $options = ' -f '.implode(',', $dir_to_update);
                        $run_update_command = true;
                    }
                }

                if ($run_update_command) {
                    echo $endpoint['run'].$options;
                    // execute update script, and record its output
                    ob_start();
                    passthru('export TERM=xterm-256color; export PATH=/usr/local/bin:/usr/bin:/bin; '.$endpoint['run'].$options);
                    $output = ob_get_contents();
                    ob_end_clean();

                    // prepare and send the notification email
                    if (isset($config['email'])) {
                        // send mail to someone, and the github user who pushed the commit
                        $body = '<p>L\'utilisateur <a href="https://github.com/'
                        . $payload->pusher->name .'">@' . $payload->pusher->name . '</a>'
                        . ' a pushé sur ' . $payload->repository->url
                        . ' et donc, ' . $endpoint['action']
                        . '.</p>';

                        $body .= '<p>Voici la liste des changements :</p>';
                        $body .= '<ul>';
                        foreach ($payload->commits as $commit) {
                            $body .= '<li>'.$commit->message.'<br />';
                            $body .= '<small style="color:#999">Ajouts: <b>'.count($commit->added)
                            .'</b> &nbsp; Modifications: <b>'.count($commit->modified)
                            .'</b> &nbsp; Suppressions: <b>'.count($commit->removed)
                            .'</b> &nbsp; <a href="' . $commit->url
                            . '">Plus d\'infos</a></small></li>';
                        }
                        $body .= '</ul>';
                        $body .= '<p>Log du script :</p><pre>';
                        $body .= $output. '</pre>';
                        $body .= '<p>Bisous, <br/>Github Webhook</p>';

                        mail($config['email']['to'], $endpoint['action'], $body, $headers);
                    }
                }

                return true;
            }
        }
    } else {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $log = ' - No errors';
                break;
            case JSON_ERROR_DEPTH:
                $log = ' - Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $log = ' - Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $log = ' - Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $log = ' - Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $log = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $log = ' - Unknown error';
                break;
        }
        echo $log;
    }

}

try {
    if (!isset($_POST['payload'])) {
        echo "YesWiki repository generator works fine !";
    } else {
        run();
    }
} catch (Exception $e) {
    $msg = $e->getMessage();
    mail($config['email']['to-errors'], $msg, ''.$e);
}
