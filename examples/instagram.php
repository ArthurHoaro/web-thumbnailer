<?php

require_once '../vendor/autoload.php';
use WebThumbnailer\WebThumbnailer;

$wt = new WebThumbnailer();
$url = 'https://www.instagram.com/p/BL3FRY7F0DJ/';
$img = $wt
    ->noCache(true)
    ->maxWidth(225)
    ->maxHeight(225)
    ->crop(true)
    ->thumbnail($url);
var_dump($img);
echo '<img src="'. $img .'" /><br>';
