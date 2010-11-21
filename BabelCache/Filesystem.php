<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

class BabelCache_Filesystem extends BabelCache implements BabelCache_Interface {
	protected $dataDir    = '';
	protected $quickCache = null;

	public function __construct($dataDirectory, $quickCache = 'BabelCache_Memory') {
		global $I18N;

		clearstatcache();

		if (!is_dir($dataDirectory) && !@mkdir($dataDirectory, 0777, true)) {
			throw new BabelCache_Exception('Can\'t create cache directory.');
		}

		$this->dataDir = $dataDirectory;

		if (!empty($quickCache)) {
			$this->quickCache = ($quickCache instanceof BabelCache_IFlushable) ? $quickCache : BabelCache::factory($quickCache);
		}
		else {
			$this->quickCache = BabelCache::factory('BabelCache_Memory');
		}
	}

	/**
	 * @return boolean  always true
	 */
	public static function isAvailable() {
		return true;
	}

	public function lock($namespace, $key, $duration = 1) {
		$key = $this->getFullKeyHelper($namespace, $key);
		$dir = parent::concatPath($this->dataDir, 'lock#'.$key);

		clearstatcache();
		return @mkdir($dir, 0777);
	}

	public function unlock($namespace, $key) {
		$key = $this->getFullKeyHelper($namespace, $key);
		$dir = parent::concatPath($this->dataDir, 'lock#'.$key);

		clearstatcache();
		return is_dir($dir) ? rmdir($dir) : true;
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
		$key            = $this->getFullKeyHelper($namespace, $key);
		$dir            = parent::concatPath($this->dataDir, 'lock#'.$key);
		$start          = microtime(true);
		$waited         = 0;
		$checkInterval *= 1000;

		while ($waited < $maxWaitTime && is_dir($dir)) {
			usleep($checkInterval);
			$waited = microtime(true) - $start;
			clearstatcache();
		}

		clearstatcache();

		if (!is_dir($dir)) {
			return $this->get($namespace, $key, $default);
		}
		else {
			return $default;
		}
	}

	public function set($namespace, $key, $value) {
		$this->quickCache->set(self::getMemNamespace($namespace), $key, $value);

		$filename = $this->getFilename($namespace, $key);
		$level    = error_reporting(0);

		touch($filename, 0777);
		file_put_contents($filename, serialize($value));

		error_reporting($level);
		return $value;
	}

	public function get($namespace, $key, $default = null) {
		$memNamespace = self::getMemNamespace($namespace);

		if ($this->quickCache->exists($memNamespace, $key)) {
			return $this->quickCache->get($memNamespace, $key);
		}

		$data = @file_get_contents($this->getFilename($namespace, $key));
		$data = $data === false ? $default : unserialize($data);

		$this->quickCache->set($memNamespace, $key, $data);
		return $data;
	}

	public function exists($namespace, $key) {
		if ($this->quickCache->exists(self::getMemNamespace($namespace), $key)) {
			return true;
		}

		return file_exists($this->getFilename($namespace, $key));
	}

	public function delete($namespace, $key) {
		$this->quickCache->delete(self::getMemNamespace($namespace), $key);
		clearstatcache();
		return @unlink($this->getFilename($namespace, $key));
	}

	public function flush($namespace, $recursive = false) {
		// flush quick cache

		$this->quickCache->flush(self::getMemNamespace($namespace), $recursive);

		// handle our own cache

		$namespace = self::getDirFromNamespace(self::cleanupNamespace($namespace));
		$root      = parent::concatPath($this->dataDir, $namespace);

		// Wenn wir rekursiv löschen, können wir wirklich alles in diesem Verzeichnis
		// löschen.

		if ($recursive) {
			clearstatcache();
			return self::deleteRecursive($root);
		}

		// Löschen wir nicht rekursiv, dürfen wir nur das data~-Verzeichnis
		// entfernen.

		else {
			$dataDir = 'data'.parent::getSafeDirChar();
			clearstatcache();
			return self::deleteRecursive(parent::concatPath($root, $dataDir));
		}
	}

	protected static function getMemNamespace($namespace) {
		$namespace = parent::cleanupNamespace($namespace);
		return 'fscache.'.$namespace;
	}

	protected static function createNamespaceDir($namespace, $root, $hash) {
		global $I18N;

		if (!empty($namespace)) {
			$thisPart = array_shift($namespace);
			$dir      = parent::concatPath($root, $thisPart);

			if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
				throw new BabelCache_Exception('Can\'t create namespace directory.');
			}

			return self::createNamespaceDir($namespace, $dir, $hash);
		}
		else {
			// Zuletzt erzeugen wir das Verteilungsverzeichnis für die Cache-Daten,
			// damit nicht alle Dateien in einem großen Verzeichnis leben müssen.

			// Dazu legen wir in dem Zielnamespace ein Verzeichnis "data~" an,
			// in dem die 00, 01, ... 99 ... EF, FF-Verzeichnisse erzeugt werden.
			// Dadurch vermeiden wir Kollisionen mit Namespaces, die auch Teilnamespaces
			// der Länge 2 haben.

			$dir = parent::concatPath($root, 'data'.parent::getSafeDirChar());

			if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
				throw new BabelCache_Exception('Can\'t create namespace directory.');
			}

			// Jetzt kommen die kleinen Verzeichnisse...

			$dir = parent::concatPath($dir, $hash[0].$hash[1]);

			if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
				throw new BabelCache_Exception('Can\'t create splitter directory.');
			}

			return true;
		}
	}

	protected static function dataDirExists($namespace, $root) {
		$namespace = self::getDirFromNamespace($namespace);
		$dataDir   = 'data'.parent::getSafeDirChar();
		$dirname   = parent::concatPath($root, $namespace, $dataDir);

		clearstatcache();
		return is_dir($dirname);
	}

	protected function getFilename($namespace, $key) {
		global $I18N;

		$namespace = parent::cleanupNamespace($namespace);
		$key       = parent::cleanupKey($key);
		$dir       = $this->dataDir;
		$hash      = md5($key);

		if (!self::dataDirExists($namespace, $dir)) {
			self::createNamespaceDir(explode('.', $namespace), $dir, $hash);
		}

		// Finalen Dateipfad erstellen

		$namespace = self::getDirFromNamespace($namespace);
		$dataDir   = 'data'.parent::getSafeDirChar();
		$hashPart  = $hash[0].$hash[1];
		$dir       = parent::concatPath($dir, $namespace, $dataDir, $hashPart);

		if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
			throw new BabelCache_Exception('Can\'t create directory.');
		}

		return parent::concatPath($dir, $hash);
	}

	protected static function getSubNamespaces($namespace) {
		$namespace = self::getDirFromNamespace(parent::cleanupNamespace($namespace));
		$dir       = parent::concatPath($this->dataDir, $namespace);
		$dataDir   = 'data'.parent::getSafeDirChar();

		// Verzeichnisse ermitteln

		$namespaces = is_dir($namespaces) ? scandir($namespaces) : array(); // Warning vermeiden (scandir gäbe auch false zurück)

		// data~-Verzeichnis entfernen, falls vorhanden

		$dataDirIndex = array_search($dataDir, $namespaces);

		if ($dataDirIndx !== false) {
			unset($namespaces[$dataDirIndex]);
		}

		sort($namespaces);
		return array_values($namespaces);
	}

	protected static function deleteRecursive($root) {
		if (!is_dir($root)) {
			return true;
		}

		try {
			$dirIterator = new RecursiveDirectoryIterator($root);
			$recIterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::CHILD_FIRST);
			$status      = true;
			$level       = error_reporting(0);

			foreach ($recIterator as $file) {
				if ($file->isDir()) $status &= rmdir($file);
				elseif ($file->isFile()) $status &= unlink($file);
			}

			rmdir($root);

			$recIterator = null;
			$dirIterator = null;

			error_reporting($level);
			return $status;
		}
		catch (UnexpectedValueException $e) {
			return false;
		}
	}

	/**
	 * @param string $namespace
	 */
	protected static function getDirFromNamespace($namespace) {
		return str_replace('.', DIRECTORY_SEPARATOR, $namespace);
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
}
