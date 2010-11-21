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
					$key[] = 'r'.str_replace(' ', '_', get_resource_type($var));
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

	protected function checkString($str) {
		if (
			strlen($str) === 0 ||
			$str[0] === '.' ||
			$str[strlen($str)-1] === '.' ||
			strpos($str, '..') !== false ||
			!preg_match('#^[a-z0-9_.-]+$#i', $str)
		) {
			throw new BabelCache_Exception('A malformed string was given.');
		}

		return $str;
	}

	/**
	 * @param  string $namespace
	 * @param  string $key
	 * @return string
	 */
	protected function getFullKeyHelper($namespace, $key) {
		$fullKey = $this->checkString($namespace);

		if (strlen($key) > 0) {
			$fullKey .= '$'.$this->checkString($key);
		}

		return $fullKey;
	}
}
