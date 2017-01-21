<?php

/** @var Silex\Application() $silex */
$silex = $app['controllers_factory'];

$silex->get('/', function () use ($twig,$app) {
	
	return $twig->render('home/home.html.twig',[
		'isLoggedSpotify' => $app["session"]->get('isAuthSpotify'),
		'isLoggedGoogle'  => $app["session"]->get('isAuthGoogle'),
		]);
});

return $silex;

?>