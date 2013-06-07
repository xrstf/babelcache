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
 * Interface for adapters that natively support atomic increment operations
 *
 * @package BabelCache
 */
interface IncrementInterface {
	/**
	 * Increment a value
	 *
	 * This performs an atomic increment operation on the given key.
	 *
	 * @param  string $key  the key
	 * @return int          the value after it has been incremented or false if the operation failed
	 */
	public function increment($key);
}
