<?php

use Phalcon\Mvc\Router;

$router = new Router();

$router->add(
    "/login",
    array(
        'controller' => 'login',
        'action'     => 'index'
    )
);

$router->add(
    "/products/:action",
    array(
        'controller' => 'products',
        'action'     => 1
    )
);
$router->add("/mishi", array("controller"=> "agentguests","action"=> "domainagent"));
$router->add("/history", array("controller"=> "agentguests","action"=> "spreaddomain"));
$router->add("/link/([0-9]{4})", array("controller"=> "advert","action"=> "click","year"=>1));
return $router;