<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

use wv\BabelCache\Adapter\Memory;
use wv\BabelCache\Cache\Generic;

class Cache_GenericTest extends Cache_BaseTest {
	protected function getCache() {
		return new Generic(new Memory());
	}

	public function testIsAvailable() {
		$this->assertTrue(Generic::isAvailable());
	}

	public function testGetAdapter() {
		$cache = $this->getCache();
		$this->assertInstanceOf('wv\BabelCache\AdapterInterface', $cache->getAdapter());
	}

	/**
	 * @expectedException  wv\BabelCache\Exception
	 */
	public function testFailingSet() {
		$cache = new Generic(new BrokenAdapter());
		$cache->set('t.foo', 'key', 'value');
	}

	public function testFailingClear() {
		$cache = new Generic(new BrokenAdapter());
		$this->assertFalse($cache->clear('t.foo'));
	}

	/**
	 * @expectedException  wv\BabelCache\Exception
	 */
	public function testTooLongKey() {
		$cache = $this->getCache();
		$cache->setPrefix('averylongstringthatwillcausethemaxkeylengttobeexceededandanexceptionbeingthrown');
		$cache->set('my.super.ultra.uber.looooong.namespace.with.a.lot.of.steps.and.increasing.length.extending.words.like.the.preceeding.ones', 'key', 'value');
	}

	public function testIncrementWithoutNativeSupport() {
		$factory = new TestFactory('fsadapter');
		$cache   = new Generic($factory->getAdapter('filesystem'));

		$cache->clear('t', true);
		$cache->set('t.foo', 'key', 'value');
		$cache->clear('t.foo', true);

		$this->assertFalse($cache->exists('t.foo', 'key'));
	}
}
