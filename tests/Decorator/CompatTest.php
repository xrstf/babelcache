<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Cache\Memory;
use wv\BabelCache\Decorator\Compat;

class Decorator_CompatTest extends PHPUnit_Framework_TestCase {
	protected function getCache() {
		return new Compat(new Memory());
	}

	public function testIsAvailable() {
		$this->assertTrue(Compat::isAvailable());
	}

	/**
	 * @dataProvider clearProvider
	 */
	public function testFlush($clearLevel, $exists1, $exists2, $exists3) {
		$cache = $this->getCache();
		$l1    = 't.AA_'.mt_rand().'_AA';
		$l2    = $l1.'.BB_'.mt_rand().'_BB';
		$l3    = $l2.'.CC_'.mt_rand().'_CC';

		$cache->set($l1, 'foo', 'bar');
		$cache->set($l2, 'foo', 'bar');
		$cache->set($l3, 'foo', null);

		$this->assertTrue($cache->flush($$clearLevel, true));
		$this->assertSame($exists1, $cache->exists($l1, 'foo'));
		$this->assertSame($exists2, $cache->exists($l2, 'foo'));
		$this->assertSame($exists3, $cache->exists($l3, 'foo'));
	}

	public function clearProvider() {
		return array(
			array('l3', true,  true,  false),
			array('l2', true,  false, false),
			array('l1', false, false, false)
		);
	}
}
