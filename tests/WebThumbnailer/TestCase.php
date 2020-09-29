<?php

declare(strict_types=1);

namespace WebThumbnailer;

class TestCase extends \PHPUnit\Framework\TestCase
{
    public function expectExceptionMessageRegExp(string $regularExpression): void
    {
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches($regularExpression);
        } else {
            parent::expectExceptionMessageRegExp($regularExpression);
        }
    }
}
