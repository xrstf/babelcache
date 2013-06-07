<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache;

/**
 * Interface for adapters that support getting/setting multiple values at once
 *
 * @package BabelCache
 */
interface MultiOpsInterface {
	/**
	 * Set multiple values
	 *
	 * @param array $items  associative array of key => value items
	 */
	public function setMultiple(array $items);

	/**
	 * Gets multiple values
	 *
	 * @param  array $keys
	 * @return array
	 */
	public function getMultiple(array $keys);
}
