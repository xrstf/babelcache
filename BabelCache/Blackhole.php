<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Blackhole-Caching
 *
 * Dieser Cache cached gar nicht. Er existiert nur als logisches Pendant zu den
 * anderen Implementierungen, um in nutzendem Code nicht auf null testen muss,
 * sondern einfach diese Klasse angeben kann, wenn man "deaktiviertes Caching"
 * meint.
 *
 * @ingroup cache
 */
class BabelCache_Blackhole extends BabelCache implements BabelCache_ISeekable {
	public function set($namespace, $key, $value) {
		return $value;
	}

	public function exists($namespace, $key) {
		return false;
	}

	public function get($namespace, $key, $default = null) {
		return $default;
	}

	public function lock($namespace, $key, $duration = 1) {
		return true;
	}

	public function unlock($namespace, $key) {
		return true;
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
		return true;
	}

	public function delete($namespace, $key) {
		return true;
	}

	public function find($namespace, $key = '*', $getKey = false, $recursive = false) {
		return array();
	}

	public function getAll($namespace, $recursive = false) {
		return array();
	}

	public function flush($namespace, $recursive = false) {
		return true;
	}

	public function getElementCount($namespace, $recursive = false) {
		return 0;
	}

	public function getSize($namespace, $recursive = false) {
		return 0;
	}
}