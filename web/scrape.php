<?php

// web/index.php
require_once __DIR__.'/../vendor/autoload.php';

use InstagramScraper\Instagram;
use Unirest\Request;

$app = new Silex\Application();
$app['debug'] = true;
Request::verifyPeer(false);


$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->get('/slideshow', function () use ($app) {
    $medias = Instagram::getMedias('vmwedding1709', 150);
    //$medias = Instagram::getMediasByTag('vmwedding1709', 30);
    foreach ($medias as $media) {
        $results[] = [
            "url" => $media->imageHighResolutionUrl
        ];
    }
    return $app['twig']->render('slideshow.twig', array(
        'results' => $results,
    ));
});

$app->get('/slideshow/update', function () use ($app) {
    $medias = Instagram::getMedias('vmwedding1709', 150);
    $results = [];
    foreach ($medias as $media) {
        $results[] = [
            "url" => $media->imageHighResolutionUrl
        ];
    }
    return $app->json($results);
});

$app->get('/slideshow/error_log', function () use ($app) {
    $log = file_get_contents('./error_log');
    print_r($log);exit;
});


$app->run();