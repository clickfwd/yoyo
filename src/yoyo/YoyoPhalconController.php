<?php

namespace Clickfwd\Yoyo;

use Phalcon\Mvc\Controller;
use Clickfwd\Yoyo\Request;
use Clickfwd\Yoyo\Yoyo;

class YoyoPhalconController extends Controller
{
    public function handleAction()
    {
        $this->view->disable();
        /** @var Yoyo $yoyo */
        $yoyo = $this->di->get('yoyo');
        $yoyoRequest = new Request();
        $yoyoRequest->mock($_REQUEST, $_SERVER);
        $yoyo->bindRequest($yoyoRequest);
        $this->response->setContent($yoyo->update());
        return $this->response;
    }
}