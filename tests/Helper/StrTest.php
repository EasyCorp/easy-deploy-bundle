<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Tests;

use EasyCorp\Bundle\EasyDeployBundle\Helper\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    /** @dataProvider startsWithProvider */
    public function test_starts_with(string $haystack, string $needle, bool $expectedResult)
    {
        $this->assertSame($expectedResult, Str::startsWith($haystack, $needle));
    }

    /** @dataProvider endsWithProvider */
    public function test_ends_with(string $haystack, string $needle, bool $expectedResult)
    {
        $this->assertSame($expectedResult, Str::endsWith($haystack, $needle));
    }

    /** @dataProvider containsProvider */
    public function test_contains(string $haystack, string $needle, bool $expectedResult)
    {
        $this->assertSame($expectedResult, Str::contains($haystack, $needle));
    }

    /** @dataProvider prefixProvider */
    public function test_prefix($text, string $prefix, string $expectedResult)
    {
        $this->assertSame($expectedResult, Str::prefix($text, $prefix));
    }

    /** @dataProvider stringifyProvider */
    public function test_stringify($value, string $expectedResult)
    {
        $this->assertSame($expectedResult, Str::stringify($value));
    }

    public function test_format_as_table()
    {
        $values = ['key1' => -3.14, 'key3 with long name' => ['a', 'b' => 2], 'key2' => 'aaa'];
        $result = <<<TABLE
key1                : -3.14
key2                : aaa
key3 with long name : {"0":"a","b":2}
TABLE;

        $this->assertSame($result, Str::formatAsTable($values));
    }

    public function startsWithProvider()
    {
        yield ['', '', false];
        yield ['abc', '', false];
        yield ['abc', 'a', true];
        yield ['abc', ' a', false];
        yield ['abc', 'abc', true];
        yield ['abc', ' abc', false];
        yield ['abc', 'abcd', false];
        yield ['<h1>a</> bc', '<h1>', true];
        yield ['<h1>a</> bc', 'a', false];
    }

    public function endsWithProvider()
    {
        yield ['', '', true];
        yield ['abc', '', false];
        yield ['abc', 'c', true];
        yield ['abc', 'c ', false];
        yield ['abc', 'abc', true];
        yield ['abc', 'abc ', false];
        yield ['abc', 'aabc', false];
        yield ['ab <h1>c</>', '</>', true];
        yield ['ab <h1>c</>', 'c', false];
    }

    public function containsProvider()
    {
        yield ['', '', false];
        yield ['abc', '', false];
        yield ['abc', 'a', true];
        yield ['abc', 'b', true];
        yield ['abc', 'c', true];
        yield ['abc', 'ab', true];
        yield ['abc', 'bc', true];
        yield ['abc', 'ac', false];
        yield ['abc', 'c ', false];
        yield ['abc', 'abc', true];
        yield ['abc', ' abc', false];
        yield ['ab <h1>c</>', '<h1>', true];
        yield ['ab <h1>c</>', 'c', true];
        yield ['ab <h1>c</>', 'ab c', false];
    }

    public function prefixProvider()
    {
        yield ['', '', ''];
        yield ['aaa', 'xxx', 'xxxaaa'];
        yield ["aaa\nbbb\nccc", 'xxx', "xxxaaa\nxxxbbb\nxxxccc"];
        yield [['aaa', 'bbb', 'ccc'], 'xxx', "xxxaaa\nxxxbbb\nxxxccc"];
    }

    public function stringifyProvider()
    {
        yield ['', ''];
        yield [fopen('php://memory', 'r+'), 'PHP Resource'];
        yield [true, 'true'];
        yield [false, 'false'];
        yield [-3.14, '-3.14'];
        yield [[1, 2, 3], '[1,2,3]'];
        yield [['a' => 'aaa', 'b' => '3.14', 'c' => ['a', 'b']], '{"a":"aaa","b":"3.14","c":["a","b"]}'];
        yield [new class() {
            public $a = 'aaa';
            private $b = 'bbb';
        }, '{"a":"aaa"}'];
        yield [new class() {
            public function __toString()
            {
                return 'aaa';
            }
        }, 'aaa'];
    }
}
