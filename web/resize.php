<?php


$dir = getcwd().'/slideshow/static';
$files = scandir($dir);
$results = array_values(array_filter($files, function($file) {
    return ($file != '.' && $file != '..');
}));

foreach ($results as $result) {
    $dir = getcwd().'/slideshow/static';
    $destinationDir = getcwd().'/slideshow/static/resized';
    resizeImage($result, 1920, 1080, $dir, $destinationDir);
}


function resizeImage($image_name,$new_width,$new_height,$uploadDir,$moveToDir)
{
    $path = $uploadDir . '/' . $image_name;

    $mime = getimagesize($path);
    $width = $mime[0];
    $height = $mime[1];
    if ($width > $height) {
        $new_height = round($new_width/$width * $height);
    } else if ($height > $width) {
        $new_width = round($new_height/$height * $width);
    } else {
        $new_height = round($new_width/$width * $height);
    }

    if($mime['mime']=='image/png'){ $src_img = imagecreatefrompng($path); }
    if($mime['mime']=='image/jpg'){ $src_img = imagecreatefromjpeg($path); }
    if($mime['mime']=='image/jpeg'){ $src_img = imagecreatefromjpeg($path); }
    if($mime['mime']=='image/pjpeg'){ $src_img = imagecreatefromjpeg($path); }

    $old_x          =   imageSX($src_img);
    $old_y          =   imageSY($src_img);

    $thumb_w    =   $new_width;
    $thumb_h    =   $new_height;
    $dst_img        =   ImageCreateTrueColor($thumb_w,$thumb_h);

    imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);


    // New save location
    $new_thumb_loc = $moveToDir."/" . $image_name;

    if($mime['mime']=='image/png'){ $result = imagepng($dst_img,$new_thumb_loc,8); }
    if($mime['mime']=='image/jpg'){ $result = imagejpeg($dst_img,$new_thumb_loc,80); }
    if($mime['mime']=='image/jpeg'){ $result = imagejpeg($dst_img,$new_thumb_loc,80); }
    if($mime['mime']=='image/pjpeg'){ $result = imagejpeg($dst_img,$new_thumb_loc,80); }

    imagedestroy($dst_img);
    imagedestroy($src_img);

    return $result;
}