<?php

declare(strict_types=1);

namespace WebThumbnailer\Utils;

use WebThumbnailer\TestCase;

class FinderUtilsTest extends TestCase
{
    /**
     * Test buildRegex() function.
     */
    public function testBuildRegex(): void
    {
        $regex = 'regex*';
        $flags = 'im';
        $formatted = '{regex*}';
        $res = FinderUtils::buildRegex($regex, $flags);
        $this->assertEquals($formatted . $flags, $res);
        $res = FinderUtils::buildRegex($regex, '');
        $this->assertEquals($formatted, $res);
        $res = FinderUtils::buildRegex('', $flags);
        $this->assertEquals('{}' . $flags, $res);
    }

    /**
     * Test checkMandatoryRules() with valid data.
     */
    public function testCheckMandatoryRulesSimple(): void
    {
        $this->assertTrue(FinderUtils::checkMandatoryRules([], []));
        $this->assertTrue(FinderUtils::checkMandatoryRules(['data'], []));
        $this->assertTrue(FinderUtils::checkMandatoryRules(['data' => 'value'], ['data']));
        $this->assertTrue(FinderUtils::checkMandatoryRules(['data' => false], ['data']));
        $this->assertTrue(FinderUtils::checkMandatoryRules(['data' => '', 'other' => 'value'], ['data']));
    }

    /**
     * Test checkMandatoryRules() with valid data and nested mandatory rules.
     */
    public function testCheckMandatoryRulesNested(): void
    {
        $rules = [
            'foo' => 'bar',
            'foobar' => [
                'nested' => 'rule',
            ]
        ];
        $mandatory = [
            'foo',
            'foobar' => ['nested']
        ];
        $this->assertTrue(FinderUtils::checkMandatoryRules($rules, $mandatory));
    }

    /**
     * Test checkMandatoryRules() with invalid data.
     */
    public function testCheckMandatoryRulesInvalidSimple(): void
    {
        $this->assertFalse(FinderUtils::checkMandatoryRules([], ['rule']));
        $this->assertFalse(FinderUtils::checkMandatoryRules(['rule' => ''], ['rule', 'other']));
        $this->assertFalse(FinderUtils::checkMandatoryRules(['other' => 'value'], ['rule']));
    }

    /**
     * Test checkMandatoryRules() with invalid data and nested mandatory rules.
     */
    public function testCheckMandatoryRulesInvalidNested(): void
    {
        $rules = [
            'foo' => 'bar',
            'foobar' => [
                'nested' => [
                    'missing' => 'rule',
                ]
            ]
        ];
        $mandatory = [
            'foo',
            'foobar' => ['nested' => ['nope']]
        ];
        $this->assertFalse(FinderUtils::checkMandatoryRules($rules, $mandatory));
    }
}
