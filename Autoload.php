<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

if (!function_exists('babelcache_autoload')) {
	function babelcache_autoload($class) {
		if (strpos($class, 'BabelCache_') === 0 || $class === 'BabelCache') {
			$file = dirname(__FILE__).'/'.str_replace('_', '/', $class).'.php';

			if (file_exists($file)) {
				require_once $file;
			}
		}
	}

	spl_autoload_register('babelcache_autoload');
}
