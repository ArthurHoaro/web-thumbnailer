<?php

require_once '../vendor/autoload.php';
use WebThumbnailer\WebThumbnailer;

$wt = new WebThumbnailer();
$url = 'https://www.flickr.com/photos/peste76/29903845474/in/explore-2016-10-24/';
$img = $wt->thumbnail($url);
var_dump($img);
echo '<img src="'. $img .'" /><br>';
