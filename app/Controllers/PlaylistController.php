<?php

use \Symfony\Component\HttpFoundation\Request;
/** @var Silex\Application() $silex */
$silex = $app['controllers_factory'];

// spotify:user:1199928833:playlist:3cEZ0ta9egQ6zSKoDEJfQR
// https://open.spotify.com/user/1199928833/playlist/3cEZ0ta9egQ6zSKoDEJfQR

$silex->get('/spotify/{user}/{id}', function ($user,$id) use ($app,$config) {
	
	$playlist = [];
	$playlist["tracks"] = [];
	

	$spotify = new SpotifyWebAPI\Session($config["api"]["spotify"]["client_id"], $config["api"]["spotify"]["client_secret"], $config["callbacks"]["spotify"]);
	$api = new SpotifyWebAPI\SpotifyWebAPI();

	if ($app['session']->get('authToken')) {
		$app['session']->set('spotify_playlist', []);

		$api->setAccessToken($app['session']->get('authToken'));
		
		$playlistInfo = $api->getUserPlaylist($user, $id);
		$playlist["name"] = $playlistInfo->name;


		$total = 0;
		$offset = 0;
		for (;;) {
			$i = 0;
			$playlistTracks = $api->getUserPlaylistTracks($user, $id,['limit' => 100,'offset' => $offset]);
			foreach ($playlistTracks->items as $track) {

				$i++;
				$total++;
				$artists = [];
				$track = $track->track;
				echo json_encode($track);

				foreach ($track->artists as $artist) {
					array_push($artists,$artist->name);
				}

				array_push($playlist["tracks"],
					['artist' => $artists,
					'name' => $track->name,
					'duration' => $track->duration_ms]);

				echo '<a href="' . $track->external_urls->spotify . '">' . $track->album->artists[1]->name . " - " . $track->name . '</a> <br>';
			}
			if ($i < 100) {
				break;
			}
			$offset = $offset + 100;
		}
		$app['session']->set('spotify_playlist', $playlist);
	} else {
		return 0;
		die();
	}
	return 'count';
});


$silex->get('/search/youtube', function () use ($app,$config) {
	$app['session']->set('youtube_playlist', []);
	$youtubePlaylist = [];
	$youtubePlaylist["tracks"]= [];

	$client = new Google_Client();
	$client->setDeveloperKey($config["api"]["google"]["developer_key"]);
	$youtube = new Google_Service_YouTube($client);

	$spotifyPlaylist = $app["session"]->get('spotify_playlist');
	$youtubePlaylist["name"] = $spotifyPlaylist["name"];
	foreach ($spotifyPlaylist["tracks"] as $track) {
		$title = "";	
		foreach ($track["artist"] as $name) {
			$title .= " " . $name;
		}

		$searchResponse = $youtube->search->listSearch('id,snippet', array(
			'q' =>  $title . " " . $track["name"],
			'maxResults' => 1,
			));	
		
		foreach ($searchResponse["items"] as $video) {
			if ($video['id']['kind'] == "youtube#video") {
				//echo $title . " " . $track["name"] . " = " . $video['snippet']['title']. " ID " . $video['id']['videoId'] . " <br> " ;
				array_push($youtubePlaylist["tracks"],[
					'title' => $video['snippet']['title'],
					'id' => $video['id']['videoId']]);
			}
		}
	}
	$app['session']->set('youtube_playlist', $youtubePlaylist);
	return true;

});	

$silex->get('/create/youtube', function () use ($app,$config) {

	$sessionKey = $app["session"]->get('token-session-key');
	$accessToken = $app["session"]->get($sessionKey);

	$OAUTH2_CLIENT_ID = $config["api"]["google"]["client_id"];
	$OAUTH2_CLIENT_SECRET = $config["api"]["google"]["client_secret"];

	$client = new Google_Client();

	$client->setClientId($OAUTH2_CLIENT_ID);
	$client->setClientSecret($OAUTH2_CLIENT_SECRET);
	$client->setScopes('https://www.googleapis.com/auth/youtube');

	$youtube = new Google_Service_YouTube($client);

	$client->setAccessToken($accessToken);
	
	$playlist = $app["session"]->get('youtube_playlist');

	$playlistSnippet = new Google_Service_YouTube_PlaylistSnippet();
	$playlistSnippet->setTitle($playlist["name"]);
	$playlistSnippet->setDescription("Playlist created by Spotify to Youtube Playlist Conveter");

    // Opsiyonel olucak
	$playlistStatus = new Google_Service_YouTube_PlaylistStatus();
	$playlistStatus->setPrivacyStatus('public');

	$youTubePlaylist = new Google_Service_YouTube_Playlist();
	$youTubePlaylist->setSnippet($playlistSnippet);
	$youTubePlaylist->setStatus($playlistStatus);

	$playlistResponse = $youtube->playlists->insert('snippet,status',$youTubePlaylist, array());
	$playlistId = $playlistResponse['id'];
	
	foreach ($playlist["tracks"] as $track) {
		$resourceId = new Google_Service_YouTube_ResourceId();
		$resourceId->setVideoId($track["id"]);
		$resourceId->setKind('youtube#video');

		$playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
		$playlistItemSnippet->setTitle($track["title"]);
		$playlistItemSnippet->setPlaylistId($playlistId);
		$playlistItemSnippet->setResourceId($resourceId);

		$playlistItem = new Google_Service_YouTube_PlaylistItem();
		$playlistItem->setSnippet($playlistItemSnippet);
		$playlistItemResponse = $youtube->playlistItems->insert(
			'snippet,contentDetails', $playlistItem, array());

	}
});	

return $silex;

?>