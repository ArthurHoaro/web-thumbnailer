<?php

declare(strict_types=1);

namespace WebThumbnailer\Application\WebAccess;

/**
 * Get a local file content.
 */
class WebAccessLocal implements WebAccess
{
    /** @inheritdoc */
    public function getContent(
        string $url,
        ?int $timeout = null,
        ?int $maxBytes = null,
        ?callable $dlCallback = null,
        ?string &$dlContent = null
    ): array {
        return [['200'], file_get_contents($url)];
    }
}
