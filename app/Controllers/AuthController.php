<?php

use \Symfony\Component\HttpFoundation\Request;
/** @var Silex\Application() $silex */
$silex = $app['controllers_factory'];

// spotify:user:1199928833:playlist:3cEZ0ta9egQ6zSKoDEJfQR
// https://open.spotify.com/user/1199928833/playlist/3cEZ0ta9egQ6zSKoDEJfQR

$silex->get('/spotify/callback',function(Request $request) use ($config,$app) {

	$spotify = new SpotifyWebAPI\Session($config["api"]["spotify"]["client_id"], $config["api"]["spotify"]["client_secret"], $config["callbacks"]["spotify"]);
	$api = new SpotifyWebAPI\SpotifyWebAPI();
	$spotify->requestAccessToken($request->get('code'));
	$token = $accessToken = $spotify->getAccessToken();
	$app['session']->set('isAuthSpotify', true);
	$app['session']->set('authToken', $token);
	$api->setAccessToken($accessToken);
	return $app->redirect('/');


});

$silex->get('/spotify', function () use ($config,$app) {
	$spotify = new SpotifyWebAPI\Session($config["api"]["spotify"]["client_id"], $config["api"]["spotify"]["client_secret"], $config["callbacks"]["spotify"]);
	$scopes = array(
		'playlist-read-private',
		'user-read-private',
		'playlist-read-collaborative'
		);

	$authorizeUrl = $spotify->getAuthorizeUrl(array(
		'scope' => $scopes
		));

	return $app->redirect($authorizeUrl);

});
$silex->get('/google/callback', function (Request $request) use ($config,$app) {

	$OAUTH2_CLIENT_ID = $config["api"]["google"]["client_id"];
	$OAUTH2_CLIENT_SECRET = $config["api"]["google"]["client_secret"];

	$client = new Google_Client();

	$client->setClientId($OAUTH2_CLIENT_ID);
	$client->setClientSecret($OAUTH2_CLIENT_SECRET);
	$client->setScopes('https://www.googleapis.com/auth/youtube');

	$redirect = filter_var($config["callbacks"]["google"],
		FILTER_SANITIZE_URL);

	$client->setRedirectUri($redirect);
	$auth_url = $client->createAuthUrl();
	$youtube = new Google_Service_YouTube($client);

	$tokenSessionKey = 'token-' . $client->prepareScopes();
	
	if ($request->get('code')) {

		if (strval($app["session"]->get('state')) !== strval($request->get('state'))) {
			die('The session state did not match.');
		}

		$client->authenticate($request->get('code'));
		$app["session"]->set('token-session-key',$tokenSessionKey);
		$app["session"]->set($tokenSessionKey,$client->getAccessToken());
		$app['session']->set('isAuthGoogle', true);

		header('Location: ' . $auth_url);
	}

	if ($app["session"]->get($tokenSessionKey)) {
		$client->setAccessToken($app["session"]->get($tokenSessionKey));
	}

	return 1;
});

$silex->get('/google', function () use ($config,$app) {

	$OAUTH2_CLIENT_ID = $config["api"]["google"]["client_id"];
	$OAUTH2_CLIENT_SECRET = $config["api"]["google"]["client_secret"];

	$client = new Google_Client();

	$client->setClientId($OAUTH2_CLIENT_ID);
	$client->setClientSecret($OAUTH2_CLIENT_SECRET);
	$a = $client->setScopes('https://www.googleapis.com/auth/youtube');

	$redirect = filter_var($config["callbacks"]["google"],
		FILTER_SANITIZE_URL);

	$client->setRedirectUri($redirect);
	$auth_url = $client->createAuthUrl();
	return $app->redirect($auth_url);
});

return $silex;

?>