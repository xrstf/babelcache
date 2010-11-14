<?php

require_once '../Autoload.php';
require_once 'PHPUnit/Autoload.php';

class GenericTest extends PHPUnit_Framework_TestCase {
	public function provider() {
		return array(
			array('XCache'),
			array('APC'),
			array('Memcache'),
			array('Memcached'),
			array('eAccelerator'),
			array('ZendServer'),
			array('Memory'),
			array('Filesystem')
		);
	}

	/**
	 * @dataProvider provider
	 */
	public function testCache($cache) {
		try {
			$cache = BabelCache::factory($cache);
		}
		catch (BabelCache_Exception $e) {
			$this->markTestSkipped($cache.' is not avilable.');
			return;
		}

		$cache->set('tests',           'foo', 'bar');
		$cache->set('tests.test.deep', 'foo2', null);

		$cache->flush('tests', true);

		$this->assertFalse($cache->exists('tests', 'foo'));
		$this->assertFalse($cache->exists('tests.test.deep', 'foo2'));

		$cache->set('tests2',           'foo', 'bar');
		$cache->set('tests2',           'foo1', 1);
		$cache->set('tests2',           'foo2', false);
		$cache->set('tests2.blub',      'foo3', true);
		$cache->set('tests2.test.deep', 'foo4', null);
		$cache->set('tests2.johnny',    'foo5', new stdClass());
		$cache->set('tests2',           'foo6', 3.41);

		$this->assertSame($cache->get('tests2', 'foo'), 'bar');
		$this->assertSame($cache->get('tests2', 'foo1'), 1);
		$this->assertSame($cache->get('tests2', 'foo2'), false);
		$this->assertSame($cache->get('tests2.blub', 'foo3'), true);
		$this->assertSame($cache->get('tests2.test.deep', 'foo4'), null);
		$this->assertInstanceOf('stdClass', $cache->get('tests2.johnny', 'foo5'));
		$this->assertSame($cache->get('tests2', 'foo6'), 3.41);

		$this->assertTrue($cache->exists('tests2', 'foo6'));

		$cache->flush('tests2', true);
	}
}
