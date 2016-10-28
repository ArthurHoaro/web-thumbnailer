<?php

require_once 'src/autoload.php';
/*
$img = \WebThumbnailer\WebThumbnailer::thumbnail(
        'https://www.youtube.com/watch?v=0SNjgIJFHCk',
        array(
            \WebThumbnailer\WebThumbnailer::MAX_WIDTH => 640
        )
    );
echo $img . PHP_EOL . '<br>';
echo '<img src="'. $img .'" /><br>';

$img = \WebThumbnailer\WebThumbnailer::thumbnail(
        'https://www.youtube.com/watch?v=Rnogd83Hyfs',
        array(
            \WebThumbnailer\WebThumbnailer::MAX_WIDTH => 640
        )
    );
echo $img . PHP_EOL . '<br>';
echo '<img src="'. $img .'" /><br>';

$img = \WebThumbnailer\WebThumbnailer::thumbnail(
        'http://youtu.be/Rnogd83Hyfs',
        array(
            \WebThumbnailer\WebThumbnailer::MAX_WIDTH => 320
        )
    );
echo $img . PHP_EOL . '<br>';
echo '<img src="'. $img .'" /><br>';

$img = \WebThumbnailer\WebThumbnailer::thumbnail(
        'https://i.imgur.com/KKQs5O9.jpg',
        array(
            \WebThumbnailer\WebThumbnailer::SIZE_MEDIUM
        )
    );
echo $img . PHP_EOL . '<br>';
echo '<img src="'. $img .'" /><br>';
*/
$img = \WebThumbnailer\WebThumbnailer::thumbnail(
    'http://imgur.com/gallery/YGu4a',
    array(
        \WebThumbnailer\WebThumbnailer::SIZE_MEDIUM
    )
);
echo $img . PHP_EOL . '<br>';
echo '<img src="'. $img .'" /><br>';