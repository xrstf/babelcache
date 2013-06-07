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
 * Utility class
 *
 * @package BabelCache
 */
abstract class Util {
	/**
	 * Create cache key
	 *
	 * This helper method can be used to create a nice cache key from a list of
	 * arbitrary variables. You can call this method with as much parameters as
	 * you like. It will do it's best to convert them to an unique string
	 * representation.
	 *
	 * One example call could look like this:
	 *
	 * @verbatim
	 * $myVar = 5;
	 * $key   = Util::generateKey(12, $myVar, 'foobar', true, 4.45, array(1,2), 'x');
	 * $key   = '12_5_foobar_1_4#45_a[1_2]_x';
	 * @endverbatim
	 *
	 * @param  mixed $vars  pseudo parameter
	 * @return string       the generated key
	 */
	public static function generateKey($vars) {
		$vars = func_get_args();
		$key  = array();

		foreach ($vars as $var) {
			switch (strtolower(gettype($var))) {
				case 'integer':
					$key[] = 'i'.$var;
					break;

				case 'string':

					if (preg_match('#[^a-z0-9-_]#', $var)) {
						// Das Prozentzeichen kennzeichnet, dass es sich bei "2147483647"
						// um einen Hashwert (und nicht eine einfache Zahl) handelt.
						$var = '%'.substr(md5($var), 0, 8);
					}

					$key[] = 's'.strtolower($var);
					break;

				case 'boolean':
					$key[] = 'b'.((int) $var);
					break;

				case 'float':
				case 'double':
					$key[] = 'f'.$var;
					break;

				case 'object':
					$key[] = 'o'.substr(md5(print_r($var, true)), 0, 8);
					break;

				case 'resource':
					$key[] = 'r'.str_replace(' ', '_', get_resource_type($var));
					break;

				case 'array':
					$key[] = empty($var) ? 'a[]' : 'a['.call_user_func_array(array(__CLASS__, 'generateKey'), $var).']';
					break;

				case 'null':
					$key[] = 'n';
					break;
			}
		}

		return implode('_', $key);
	}

	/**
	 * Checks a string for validity
	 *
	 * To keep things nice and easy, only a small subset of ASCII is allowed in
	 * namespace/key values. Additionally you can't start or end a value with
	 * a dot, the value must be non-empty, cannot contain '..' and may contain
	 * only the characters A-Z, 0-9, '_', '.', '[', ']', '$', '#' and '-'.
	 *
	 * @throws BabelCache_Exception  if the string contains illegal characters
	 * @param  string $str           the string to check
	 * @param  string $name          the 'variable name' of the string
	 * @return string                the unaltered string
	 */
	public static function checkString($str, $name) {
		if (!is_string($str) && !is_integer($str)) {
			throw new Exception('Given '.$name.' parameter is not a string/int, but a '.gettype($str).'.');
		}

		if (is_string($str) && strlen($str) === 0) {
			throw new Exception('No '.$name.' given.');
		}

		if (
			$str[0] === '.' ||
			$str[strlen($str)-1] === '.' ||
			strpos($str, '..') !== false ||
			!preg_match('#^[a-z0-9_.\[\]%\#-]+$#i', $str)
		) {
			throw new Exception('A malformed '.$name.' was given.');
		}

		return $str;
	}

	/**
	 * Concats namespace and key
	 *
	 * This method will just concat the namespace and the key, if the key is
	 * not empty. The splitter character is '$'.
	 * Both namespace and key will be checked with checkString().
	 *
	 * It's allowed to enter an empty key. This will not trigger an exception.
	 *
	 * @throws BabelCache_Exception  if one of the arguments is malformed
	 * @param  string $namespace     namespace
	 * @param  string $key           key
	 * @return string                'namespace$key' or 'namespace'
	 */
	public static function getFullKeyHelper($namespace, $key) {
		$fullKey = self::checkString($namespace, 'namespace');

		if (strlen($key) > 0) {
			$fullKey .= '$'.self::checkString($key, 'key');
		}

		return $fullKey;
	}

	/**
	 * Waits for a lock to be released
	 *
	 * This method will wait for a specific amount of time for the lock to be
	 * released. For this, it constantly checks the lock (tweak the check
	 * interval with the last parameter).
	 *
	 * When the maximum waiting time elapsed, the $default value will be
	 * returned. Else the value will be read from the cache.
	 *
	 * @param  CacheInterface $cache          the cache to work on
	 * @param  string         $namespace      the namespace
	 * @param  string         $key            the key
	 * @param  mixed          $default        the value to return if the lock does not get released
	 * @param  int            $maxWaitTime    the maximum waiting time (in seconds)
	 * @param  int            $checkInterval  the check interval (in milliseconds)
	 * @return mixed                          the value from the cache if the lock was released, else $default
	 */
	public static function waitForLockRelease(CacheInterface $cache, $namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 750) {
		$start  = microtime(true);
		$waited = 0;

		$checkInterval *= 1000;

		while ($waited < $maxWaitTime && $cache->hasLock($namespace, $key)) {
			usleep($checkInterval);
			$waited = microtime(true) - $start;
		}

		if (!$cache->hasLock($namespace, $key)) {
			return $cache->get($namespace, $key, $default);
		}

		return $default;
	}
}
