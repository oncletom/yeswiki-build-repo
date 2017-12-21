<?php
namespace YesWikiRepo;

use \Exception;

class WebhookController extends Controller
{
    public function run($params)
    {
        $this->repo->load();
        $this->repo->updateHook(
            $this->getRepository($params) . '/',
            $this->getBranch($params)
        );
    }

    private function getBranch($params)
    {
        $explodedRef = explode('/', $params['ref']);
        return end($explodedRef);
    }

    private function getRepository($params)
    {
        if (isset($params['repository']['html_url'])) {
            return $params['repository']['html_url'];
        }
        throw new Exception("Bad hook format.", 1);
    }
}
