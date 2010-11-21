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
 * Dieses Interface kann von Addons implementiert werden, um Redaxo eine
 * spezifische Cache-Implementierung zu geben. Für besondere Systemumgebungen
 * können speziell angepasste Cache-Implementierungen sinnvoll sein.
 */
interface BabelCache_Interface {
	/**
	 * Setzt einen Wert in den Redaxo Cache
	 *
	 * @param String  $namespace  Der Namespace des abgeleten Wertes
	 * @param String  $key        Eindeutiger Identifier für den Cacheeintrag
	 * @param mixed   $value      Wert, der gespeichert werden soll
	 */
	public function set($namespace, $key, $value);

	/**
	 * Holt einen Wert zu einem Schlüssel aus dem Cache
	 *
	 * @param String  $namespace  Der Namespace des abgeleten Wertes
	 * @param String  $key        Eindeutiger Identifier unter dem der Cacheeintrag erwartet wird
	 * @param mixed   $default    Defaultwert, der bei einem Cache-Miss zurückgegeben wird
	 * @return mixed              der gewünschte Wert oder NULL
	 */
	public function get($namespace, $key, $default = null);

	/**
	 * Löscht einen Eintrag aus dem Cache
	 *
	 * @param String  $namespace  Der Namespace des abgeleten Wertes
	 * @param String  $key        Eindeutiger Identifier unter dem der Cacheeintrag erwartet wird
	 */
	public function delete($namespace, $key);

	public function exists($namespace, $key);
	public function lock($namespace, $key, $duration = 1);
	public function unlock($namespace, $key);
	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50);
	public function flush($namespace, $recursive = false);

	public static function isAvailable();
}
