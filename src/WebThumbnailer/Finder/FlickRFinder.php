<?php

declare(strict_types=1);

namespace WebThumbnailer\Finder;

/**
 * Finder for flickr.com. Works with the homepage, profiles and picture page.
 *
 * Apply size parameter on thumb URL.
 *
 * @see QueryRegexFinder for more info.
 */
class FlickRFinder extends QueryRegexFinder
{
    /**
     * FlickR image permalinks are suffixed by a size character.
     * This finder will replace it according to user size settings.
     *
     * @inheritdoc
     */
    public function find()
    {
        $thumb = parent::find();
        if (empty($thumb)) {
            return false;
        }

        $size = $this->getOptionValue('size');
        // One size is actually no suffix...
        $size = ! empty($size) ? '_' . $size : '';
        $thumb = preg_replace('#(.*)_\w(\.\w+)$#i', '$1' . $size . '$2', $thumb);

        return $thumb ?? false;
    }
}
