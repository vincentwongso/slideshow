<?php

// web/index.php
require_once __DIR__.'/../vendor/autoload.php';

use InstagramAPI\Instagram;

const USERNAME_ID  = '3858675695';

$app = new Silex\Application();
$app['debug'] = false;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'host'      => 'localhost',
        'dbname'    => 'nlineta3_slideshow',
        'user'      => 'nlineta3_slide',
        'password'  => 'slide5t4r3E!',
    ),
));


$config = ["use_staging" => false];
$app['slideshow_config'] = $config;


$app->get('/', function () use ($app) {
    print '';exit;
});

/**
 * Display all staging instagram images
 */
$app->get('/presenter', function () use ($app) {
    $sql = "SELECT * FROM slideshow_queue WHERE shown = 0 and banned = 0 order by taken_at asc";

    $results = $app['db']->fetchAll($sql);
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
 * Approve instagram feed
 */
$app->get('/ban/{id}', function ($id) use($app) {
    $app['db']->update('slideshow_queue',
        [
            "banned" => 1
        ],
        [
            "id" => $id
        ]
    );

    return $app->redirect('/presenter');
});

/**
 * Reset shown
 *
 */
$app->get('/reset', function () use($app) {
    $sql = 'UPDATE slideshow_queue SET shown = 0 ';

    $app['db']->executeUpdate($sql);

    return $app->redirect('/presenter');
});

/**
 * slideshow
 */
$app->get('/present', function () use($app) {
    $results = getInstagramImages($app);
    return $app['twig']->render('slideshow.twig', array(
        'results' => $results,
    ));
});

/**
 * slideshow update return json
 */
$app->get('/slideshow/update', function () use($app) {
    $results = getInstagramImages($app);

    return $app->json($results);
});


function getInstagramImages($app) {
    $sql = "SELECT * FROM slideshow_queue ";
    $sql .= " WHERE shown = 0 AND banned = 0";
    if ($app["slideshow_config"]["use_staging"]) {
        $sql .= " AND staging = 0 ";
    }
    $sql .= " ORDER BY taken_at ASC LIMIT 12";

    $results = $app['db']->fetchAll($sql);
    if (count($results) > 0) {
        markImagesAsShown($app, $results);
    }

    if (count($results) < 12) {
        $results = array_merge($results, getRandomStaticImages((12 - count($results))));
    }
    return $results;
}

function getRandomStaticImages($total) {
    $dir    = getcwd().'/slideshow/static/resized';
    $files = scandir($dir);
    $results = array_values(array_filter($files, function($file) {
        return ($file != '.' && $file != '..');
    }));
    shuffle($results);
    $results = array_slice($results, 0, $total);
    $results = array_map(function($value) {
        return ["image_url" => "/slideshow/static/resized/" . $value];
    }, $results);

    return $results;
}

function markImagesAsShown($app, $results) {
    $ids = array_column($results, 'id');
    $sql = 'UPDATE slideshow_queue SET shown = 1 WHERE id IN ('.implode(",", array_values($ids)).')';

    $app['db']->executeUpdate($sql);
}

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
