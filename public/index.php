<?php

require_once '../app/Config/config.php';
require_once '../vendor/autoload.php';

$app = new Silex\Application();

$app->register(new Silex\Provider\SessionServiceProvider());

$loader = new Twig_Loader_Filesystem($config['dirs']['views']);
$twig = new Twig_Environment($loader, array('debug' => true));

$twig->addExtension(new Twig_Extension_Debug());

$app["debug"] = true;	

$app->mount('/', include $config['dirs']['controllers'].'/HomeController.php');
$app->mount('/auth', include $config['dirs']['controllers'].'/AuthController.php');
$app->mount('/playlist', include $config['dirs']['controllers'].'/PlaylistController.php');

$app->run();
