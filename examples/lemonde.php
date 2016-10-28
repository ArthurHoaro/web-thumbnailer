<?php

require_once '../vendor/autoload.php';
use WebThumbnailer\WebThumbnailer;

$wt = new WebThumbnailer();
$url = 'http://www.lemonde.fr/sciences/article/2016/10/24/mars-enquete-en-cours-sur-le-crash-de-schiaparelli_5019351_1650684.html';
$img = $wt->maxWidth(250)->thumbnail($url);
var_dump($img);
echo '<img src="'. $img .'" /><br>';
