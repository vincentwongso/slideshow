<?php

// web/index.php
require_once __DIR__.'/../vendor/autoload.php';

use InstagramAPI\Instagram;

const USERNAME_ID  = '3858675695';

$app = new Silex\Application();
$app['debug'] = true;


$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'host'      => 'localhost',
        'dbname'    => 'slideshow',
        'user'      => 'root',
        'password'  => 'master5t4r3E!',
    ),
));

/**
 * Display all staging instagram images
 */
$app->get('/presenter', function () use ($app) {
    $sql = "SELECT * FROM slideshow_queue WHERE staging = ?";

    $results = $app['db']->fetchAll($sql, array(1));
    return $app['twig']->render('presenter.twig', array(
        'results' => $results,
    ));
});

/**
 * Fetch new data
 */
$app->get('/fetch', function () use ($app) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, "http://".$_SERVER["HTTP_HOST"]."/scrape.php");
    $data = curl_exec($ch);
    print_r($data);
    curl_close($ch);
    return $app->redirect('/presenter');
});

/**
 * Approve instagram feed
 */
$app->get('/approve/{id}', function ($id) use($app) {
    $app['db']->update('slideshow_queue',
        [
            "staging" => 0
        ],
        [
            "id" => $id
        ]
    );

    return $app->redirect('/presenter');
});

/**
 * slideshow
 */
$app->get('/slideshow', function () use($app) {
    $sql = "SELECT * FROM slideshow_queue WHERE staging = ?";

    $results = $app['db']->fetchAll($sql, array(0));

    return $app['twig']->render('slideshow.twig', array(
        'results' => $results,
    ));
});

/**
 * slideshow update return json
 */
$app->get('/slideshow/update', function () use($app) {
    $sql = "SELECT * FROM slideshow_queue WHERE staging = ?";
    $results = $app['db']->fetchAll($sql, array(0));

    return $app->json($results);
});

$app->get('/slideshow/scrape', function () use ($app, $auth) {
    $instagram = new Instagram('vmwedding1709', 'Rusty123', false, $IGDataPath = 'images');
    $instagram->login();
    /** @var \InstagramAPI\FollowingResponse $followingResponse */
    $followingResponse = $instagram->getUserFollowings(USERNAME_ID);
    $followerResponse = $instagram->getUserFollowers(USERNAME_ID);
    $users = [];
    $followingUsers = $followingResponse->getFollowings();
    $followerUsers = $instagram->getUserFollowers(USERNAME_ID);
    $users = array_merge($followingUsers, $followerUsers);
    /** @var \InstagramAPI\User $user */
    /*foreach ($users as $user) {
        $user->getUsernameId();
    }*/
    /** @var \InstagramAPI\TagFeedResponse $tagFeedResponse */
    $tagFeedResponse = $instagram->tagFeed("vmwedding1709");
    $items = $tagFeedResponse->getItems();
    /** @var \InstagramAPI\Item $item */
    foreach ($items as $item) {
        $imageVersions = $item->getImageVersions();
        print $item->getUser()->getUsername().":";
            print "<img src='".$imageVersions[0]->getUrl()."' /><br />";
        print "<br />";
    }
    print_r($items);exit;
});

$app->get('/slideshow/error_log', function () use ($app) {
    $log = file_get_contents('./error_log');
    print_r($log);exit;
});


$app->run();