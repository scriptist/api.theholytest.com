<?php

require 'bible-books.php';

$app = new \Slim\Slim();
$app->add(new \CorsSlim\CorsSlim());
$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());

require 'routes.php';

$app->run();

?>
