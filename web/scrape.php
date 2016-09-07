<?php
// web/scrape.php
require_once __DIR__.'/../vendor/autoload.php';
use InstagramAPI\Instagram;

const USERNAME_ID  = '3858675695';

$app = new Silex\Application();
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'host'      => 'localhost',
        'dbname'    => 'slideshow',
        'user'      => 'root',
        'password'  => 'master5t4r3E!',
    ),
));

$instagram = new Instagram('vmwedding1709', 'Rusty123', false, $IGDataPath = 'images');
$instagram->login();
/** @var \InstagramAPI\TagFeedResponse $tagFeedResponse */
$tagFeedResponse = $instagram->tagFeed("vmwedding1709");
$items = $tagFeedResponse->getItems();
/** @var \InstagramAPI\Item $item */
foreach ($items as $item) {
    if (!imageExist($app, $item->getPk())) {
        print "inserting: ".$item->getPk()."\n";
        insertImage($app, $item);
    }
}

function imageExist($app, $instagramId) {
    $sql = "SELECT * FROM slideshow_queue WHERE instagram_item_id = ?";
    $results = $app['db']->fetchAssoc($sql, array($instagramId));
    if ($results) {
        return true;
    } else {
        return false;
    }
}

/**
 * Insert new image to queue
 * @param $app Silex\Application
 * @param $item \InstagramAPI\Item
 */
function insertImage($app, $item) {
    $imageVersions = $item->getImageVersions();
    $mainImage = saveImage($imageVersions[1]);
    $thumbImage = saveImage($imageVersions[5], true);
    $date = new \DateTime();
    $date->setTimestamp((int)$item->getTakenAt());
    if ($mainImage != "") {
        $app['db']->insert('slideshow_queue',
            [
                'image_url' => $mainImage,
                'thumb_url' => $thumbImage,
                'instagram_item_id' => $item->getPk(),
                'username' => $item->getUser()->getUsername(),
                'taken_at' => $date
            ],
            [
                PDO::PARAM_STR,
                PDO::PARAM_STR,
                PDO::PARAM_STR,
                PDO::PARAM_STR,
                'datetime',
            ]
            );
    }
}

/**
 * @param $image \InstagramAPI\HdProfilePicUrlInfo
 * @return string
 */
function saveImage($image, $isThumb = false) {
    $imageName = "";
    $linkArray = explode('/',$image->getUrl());
    $imageName = end($linkArray);
    $imageName = array_shift(explode("?", $imageName));
    if ($isThumb) {
        $imageName = "thumb/".$imageName;
    }
    $img = '/slideshow/instagram/'.$imageName;
    print "saving ".$img."\n";
    file_put_contents(getcwd().$img, file_get_contents($image->getUrl()));
    return $img;
}