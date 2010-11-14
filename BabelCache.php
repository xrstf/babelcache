<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

abstract class BabelCache {
	protected $expiration = 0; ///< int  never expire

	private static $instances       = null;   ///< array
	private static $cachingStrategy = null;   ///< string
	private static $cacheDisabled   = false;  ///< boolean

	/**
	 * @return boolean  always true
	 */
	public static function isAvailable() {
		return true;
	}

	public static function disableCaching() {
		self::$cacheDisabled = true;
	}

	public static function enableCaching() {
		self::$cacheDisabled = false;
	}

	/**
	 * @param  string $forceCache
	 * @return BabelCache_Interface
	 */
	public static function factory($cacheName) {
		if (self::$cacheDisabled) {
			return self::factory('Blackhole');
		}

		$className = 'BabelCache_'.$cacheName;

		if (!class_exists($className)) {
			throw new BabelCache_Exception('Invalid class given.');
		}

		if (!empty(self::$instances[$className])) {
			return self::$instances[$className];
		}

		// check availability

		if (!call_user_func(array($className, 'isAvailable'))) {
			throw new BabelCache_Exception('The chosen cache is not available.');
		}

		self::$instances[$className] = new $className();
		return self::$instances[$className];
	}

	/**
	 * @throws BabelCache_Exception
	 * @param  string $namespace
	 */
	protected static function cleanupNamespace($namespace) {
		return self::trimString($namespace, 'An empty namespace was given.');
	}

	/**
	 * @throws BabelCache_Exception
	 * @param  string $key
	 */
	protected static function cleanupKey($key) {
		return self::trimString($key, 'An empty key was given.');
	}

	private static function trimString($str, $exception) {
		$str = trim($str); // normale Whitespaces entfernen
		$str = preg_replace('#[^a-z0-9_\.-]#i', '_', $str);
		$str = preg_replace('#\.{2,}#', '.', $str);
		$str = trim($str, '.'); // führende und abschließende Punkte entfernen

		if (strlen($str) == 0) {
			throw new BabelCache_Exception($exception);
		}

		return strtolower($str);
	}

	/**
	 * @param string $namespace
	 */
	protected static function getDirFromNamespace($namespace) {
		return str_replace('.', DIRECTORY_SEPARATOR, $namespace);
	}

	/**
	 * @param string $namespace
	 * @param string $newSep
	 */
	protected static function replaceSeparator($namespace, $newSep) {
		return str_replace('.', $newSep, $namespace);
	}

	/**
	 * @param string $args  Call this method with as many arguments as you want.
	 */
	protected static function concatPath($args) {
		$args = func_get_args();
		return implode(DIRECTORY_SEPARATOR, $args);
	}

	/**
	 * Diese Methode sagt den einzelnen Caches, welches Zeichen weder in
	 * Namespacenamen noch in Keys vorkommen darf. Damit können die
	 * Implementierungen dieses Zeichen dann verwenden, um interne Strukturen
	 * zu kennzeichnen.
	 *
	 * @return string
	 */
	protected static function getSafeDirChar() {
		return '~';
	}

	/**
	 * @param string $prefix
	 */
	public function setNamespacePrefix($prefix) {
		$this->namespacePrefix = self::cleanupNamespace($prefix);
	}

	/**
	 * @param int $expiration
	 */
	public function setExpiration($expiration) {
		$this->expiration = abs((int) $expiration);
	}

	/**
	 * @throws BabelCache_Exception
	 * @param  string $key
	 * @param  int    $length
	 */
	protected static function checkKeyLength($key, $length) {
		if (strlen($key) > $length) {
			throw new BabelCache_Exception('The given key is too long. At most '.$length.' characters are allowed.');
		}
	}

	/**
	 * @param  string $namespace
	 * @param  string $key
	 * @return string
	 */
	protected function getFullKeyHelper($namespace, $key) {
		$fullKey = self::cleanupNamespace($namespace);

		if (strlen($key) > 0) {
			$fullKey .= '$'.self::cleanupKey($key);
		}

		return $fullKey;
	}

	/**
	 * @param  string  $path
	 * @param  string  $keyName
	 * @param  boolean $excludeLastVersion
	 * @return string
	 */
	protected static function versionPathHelper($path, $keyName, $excludeLastVersion = false) {
		if ($excludeLastVersion) {
			$lastNode = array_pop($path);
			$lastNode = reset(explode('@', $lastNode, 2));

			$path[] = $lastNode;
		}

		$path = implode('.', $path);

		if (!empty($keyName)) {
			$path .= '$'.$keyName;
		}

		return $path;
	}

	/**
	 * Cachekey erzeugen
	 *
	 * Diese Hilfsmethode kann genutzt werden, um in Abhängigkeit von beliebigen
	 * Parametern einen eindeutigen Key zu erzeugen. Dazu kann diese Methode
	 * mit beliebig vielen Parametern aufgerufen werden, die in Abhängigkeit von
	 * ihrem Typ verarbeitet und zu einem Key zusammengeführt werden.
	 *
	 * Ein Aufruf könnte beispielsweise wie folgt aussehen:
	 *
	 * @verbatim
	 * $myVar = 5;
	 * $key   = BabelCache::generateKey(12, $myVar, 'foobar', true, 4.45, array(1,2), 'x');
	 * $key   = '12_5_foobar_1_4#45_a[1_2]_x';
	 * @endverbatim
	 *
	 * @param  mixed $vars  Pseudo-Parameter. Diese Methode kann mit beliebig vielen Parametern aufgerufen werden
	 * @return string       der Objekt-Schlüssel
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

					if (preg_match('#[^a-z0-9-_]#i', $var)) {
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
					$key[] = 'r'.preg_replace('#[^a-z0-9_]#i', '_', get_resource_type($var));
					break;

				case 'array':
					$key[] = 'a['.call_user_func_array(array(__CLASS__, 'generateKey'), $var).']';
					break;

				case 'null':
					$key[] = 'n';
					break;
			}
		}

		return implode('_', $key);
	}
}
