<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Util;

class UtilTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider generateKeyProvider
	 */
	public function testGenerateKey($value, $unexpected) {
		$key           = Util::generateKey($value);
		$unexpectedKey = Util::generateKey($unexpected);

		$this->assertInternalType('string', $key);
		$this->assertNotSame('', $key, 'keys should never be empty');
		$this->assertNotSame($unexpectedKey, $key);
	}

	public function generateKeyProvider() {
		$foo = new stdClass();
		$fp  = fopen(__FILE__, 'r');
		$foo->x = 1;

		return array(
			array(1,              '1'),
			array(3.41,           '3'),
			array(3.41,           3),
			array($fp,            ''),
			array($fp,            null),
			array(false,          ''),
			array(false,          'false'),
			array(false,          0),
			array(false,          true),
			array(false,          null),
			array(null,           ''),
			array(array(),        ''),
			array(array(null),    ''),
			array(array(1),       array('1')),
			array('foo',          'FOO'),
			array(new stdClass(), 'object'),
			array(new stdClass(), $foo)
		);
	}

	/**
	 * @dataProvider validStringsProvider
	 */
	public function testValidStrings($string) {
		$this->assertSame($string, Util::checkString($string, 'str'));
	}

	public function validStringsProvider() {
		return array(
			array('foo'),
			array('foo.bar'),
			array('foo.-test-.123'),
			array(1),
			array(-1),
		);
	}

	/**
	 * @dataProvider      invalidStringsProvider
	 * @expectedException wv\BabelCache\Exception
	 */
	public function testInvalidStrings($string) {
		Util::checkString($string, 'str');
	}

	public function invalidStringsProvider() {
		return array(
			array(1.24),
			array(false),
			array(true),
			array(new stdClass()),
			array(null),
			array(''),
			array(' foo '),
			array(' foo'),
			array('foo '),
			array('foo bar'),
			array('foo.'),
			array('.foo'),
			array('..fo.o'),
			array('..fo.o..bar'),
			array('$pecia1 aren\'t allowed.')
		);
	}

	/**
	 * @dataProvider getFullKeyProvider
	 */
	public function testGetFullKey($namespace, $key, $expected) {
		$this->assertSame($expected, Util::getFullKeyHelper($namespace, $key));
	}

	public function getFullKeyProvider() {
		return array(
			array('foo', '', 'foo'),
			array('foo', 'bar', 'foo$bar'),
			array('foo.bar', 'bar', 'foo.bar$bar')
		);
	}
}
