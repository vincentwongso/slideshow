<?php

// web/index.php
require_once __DIR__.'/../vendor/autoload.php';

use Instagram\Auth;
use Instagram\Instagram;


$authConfig = array(
    'client_id'         => 'd30c2a2047cb4137bd57517c838d0142',
    'client_secret'     => '9e047e0e60b5408d8b4973ba0dd97843',
    'redirect_uri'      => 'http://'.$_SERVER["SERVER_NAME"].':'.$_SERVER["SERVER_PORT"].'/slideshow',
    'scope'             => array( 'likes', 'comments', 'relationships', 'public_content', 'follower_list' )
);

$auth = new Auth($authConfig);

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));



$app->get('/', function () use ($app, $auth) {
    return $auth->authorize();
});

$app->get('/slideshow', function () use ($app, $auth) {
    session_start();
    $_SESSION['instagram_access_token'] = $auth->getAccessToken( $_GET['code'] );
    $instagram = new Instagram();
    $instagram->setAccessToken( $_SESSION['instagram_access_token'] );
    $currentUser = $instagram->getCurrentUser();

    $tag = $instagram->getTag( 'vmwedding1709' );
    $results = $tag->getMedia();
    $followers = $currentUser->getFollows();
    foreach ($followers as $follower) {
        $medias = $follower->getMedia();
        print_r($medias);
    }
    return $app['twig']->render('slideshow.twig', array(
        'results' => $results,
    ));
});

$app->get('/slideshow/update', function () use ($app, $auth) {
    session_start();
    $instagram = new Instagram();
    $instagram->setAccessToken( $_SESSION['instagram_access_token'] );
    $tag = $instagram->getTag( 'vmwedding1709' );
    $medias = $tag->getMedia();
    $results = [];
    foreach ($medias as $media) {
        $image = $media->getStandardResImage();
        $results[] = [
            "url" => $image->url,
            "width" => $image->width,
            "height" => $image->height
        ];
    }
    return $app->json($results);
});

$app->run();