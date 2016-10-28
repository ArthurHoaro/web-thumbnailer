<?php

require_once '../vendor/autoload.php';
use WebThumbnailer\WebThumbnailer;

$wt = new WebThumbnailer();
$url = 'http://imgur.com/36hCDyY';
$img = $wt->debug(true)
         ->noCache(true)
         ->maxWidth(199)
         ->maxHeight(199)
         ->crop(true)
         ->thumbnail($url);
var_dump($img);
echo '<img src="'. $img .'" /><br>';

//$wt = new WebThumbnailer();
//$img = $wt->thumbnail($url, [WebThumbnailer::MAX_WIDTH => 500]);
//echo '<img src="'. $img .'" />';