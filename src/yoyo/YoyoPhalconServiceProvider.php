<?php

namespace Clickfwd\Yoyo;

use Clickfwd\Yoyo\ViewProviders\PhalconViewProvider;
use Phalcon\Di\DiInterface;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Mvc\View\Simple as SimpleView;

class YoyoPhalconServiceProvider implements ServiceProviderInterface
{
    private $yoyoConfig = [];

    private $viewExtention = null;

    public function setYoyoConfig($yoyoConfig)
    {
        $this->yoyoConfig = $yoyoConfig;

        return $this;
    }

    public function setViewExtention($viewExtention)
    {
        $this->viewExtention = $viewExtention;

        return $this;
    }

    public function register(DiInterface $di): void
    {
        $di->setShared('yoyo', function () use ($di) {
            $yoyo = new Yoyo();
            $yoyoConfig = $this->yoyoConfig ?? [
                'url' => '/yoyo',
                'namespace' => 'App\Components\\',
                'scriptsPath' => 'js/',
            ];

            $yoyo->configure($yoyoConfig);
            $viewExtention = $this->viewExtention ?? null;

            $yoyo->container()->singleton('yoyo.view.default', function () use ($di, $viewExtention) {
                $view = $di->get('view');
                $simpleView = new SimpleView();
                $simpleView->setViewsDir($view->getViewsDir());
                /** @var PhalconViewProvider $viewProvider */
                $viewProvider = new PhalconViewProvider($simpleView);
                if($viewExtention){
                    $viewProvider->setViewExtention($this->viewExtention);
                }

                return $viewProvider;
            });

            return $yoyo;
        });
    }
}