<?php

namespace WebThumbnailer\Utils;

use PHPUnit\Framework\TestCase;

/**
 * Class FinderUtilsTest
 *
 * @package WebThumbnailer\utils
 */
class FinderUtilsTest extends TestCase
{
    /**
     * Test buildRegex() function.
     */
    public function testBuildRegex()
    {
        $regex = 'regex*';
        $flags = 'im';
        $formatted = '{regex*}';
        $res = FinderUtils::buildRegex($regex, $flags);
        $this->assertEquals($formatted . $flags, $res);
        $res = FinderUtils::buildRegex($regex, false);
        $this->assertEquals($formatted, $res);
        $res = FinderUtils::buildRegex(false, $flags);
        $this->assertEquals('{}'. $flags, $res);
    }

    /**
     * Test checkMandatoryRules() with valid data.
     */
    public function testCheckMandatoryRulesSimple()
    {
        $this->assertTrue(FinderUtils::checkMandatoryRules([], []));
        $this->assertTrue(FinderUtils::checkMandatoryRules(null, []));
        $this->assertTrue(FinderUtils::checkMandatoryRules(['data'], []));
        $this->assertTrue(FinderUtils::checkMandatoryRules(['data' => 'value'], ['data']));
        $this->assertTrue(FinderUtils::checkMandatoryRules(['data' => false], ['data']));
        $this->assertTrue(FinderUtils::checkMandatoryRules(['data' => '', 'other' => 'value'], ['data']));
    }

    /**
     * Test checkMandatoryRules() with valid data and nested mandatory rules.
     */
    public function testCheckMandatoryRulesNested()
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
    public function testCheckMandatoryRulesInvalidSimple()
    {
        $this->assertFalse(FinderUtils::checkMandatoryRules([], ['rule']));
        $this->assertFalse(FinderUtils::checkMandatoryRules(null, ['rule']));
        $this->assertFalse(FinderUtils::checkMandatoryRules(['rule' => ''], ['rule', 'other']));
        $this->assertFalse(FinderUtils::checkMandatoryRules(['other' => 'value'], ['rule']));
    }

    /**
     * Test checkMandatoryRules() with invalid data and nested mandatory rules.
     */
    public function testCheckMandatoryRulesInvalidNested()
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
