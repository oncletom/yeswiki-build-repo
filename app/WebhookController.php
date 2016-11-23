<?php
namespace YesWikiRepo;

use \Exception;

class WebhookController extends Controller
{
    public function run($params)
    {
        print('Webhook detected !');
        var_dump($params);
    }
}
