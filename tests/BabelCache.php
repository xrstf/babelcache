<?php

require_once 'Generic.php';

class BabelCacheTest extends PHPUnit_Framework_TestCase {
	public function testGenerateKey() {
		$this->assertNotEquals(BabelCache::generateKey('1'), BabelCache::generateKey(1));
		$this->assertNotEquals('', BabelCache::generateKey(false));
		$this->assertNotEquals('', BabelCache::generateKey(null));
	}

	public function testFactory() {
		$obj = BabelCache::factory('Blackhole');
		$this->assertInstanceOf('BabelCache_Blackhole', $obj);
	}

	/**
	 * @expectedException BabelCache_Exception
	 */
	public function testFactoryMissing() {
		BabelCache::factory('UNKNOWN_CLASS');
	}
}
