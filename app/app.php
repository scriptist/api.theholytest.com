<?php

$app = new \Slim\Slim();
$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());

require 'routes.php';

$app->run();

?>
