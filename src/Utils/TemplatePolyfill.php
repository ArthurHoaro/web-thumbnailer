<?php

declare(strict_types=1);

namespace WebThumbnailer\Utils;

class TemplatePolyfill
{
    /** @return \Text_Template|\SebastianBergmann\Template\Template */
    public static function get(string $file)
    {
        if (class_exists('Text_Template')) {
            return new \Text_Template($file);
        }

        return new \SebastianBergmann\Template\Template($file);
    }
}
