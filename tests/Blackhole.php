<?php

require_once 'Generic.php';

class BlackholeTest extends PHPUnit_Framework_TestCase {
	public function testSimpleMethods() {
		$cache = BabelCache::factory('Blackhole');
		$this->assertSame($cache->set('foo', 'bar', 1), 1);
		$this->assertSame($cache->get('foo', 'bar', 2), 2);
		$this->assertFalse($cache->exists('foo', 'bar'));
	}
}
