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

	private static $safeDirChar = '~';

	public function __construct($dataDirectory) {
		clearstatcache();
		self::makeDir($dataDirectory);

		$this->dataDir    = $dataDirectory;
		$this->quickCache = new BabelCache_Memory();
	}

	/**
	 * @return boolean  always true
	 */
	public static function isAvailable() {
		return true;
	}

	public function lock($namespace, $key, $duration = 1) {
		$key = $this->getFullKeyHelper($namespace, $key);
		$dir = $this->dataDir.'/lock#'.$key;

		clearstatcache();
		return @mkdir($dir, 0777);
	}

	public function unlock($namespace, $key) {
		$key = $this->getFullKeyHelper($namespace, $key);
		$dir = $this->dataDir.'/lock#'.$key;

		clearstatcache();
		return is_dir($dir) ? @rmdir($dir) : true;
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
		$key            = $this->getFullKeyHelper($namespace, $key);
		$dir            = $this->dataDir.'/lock#'.$key;
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

		return $default;
	}

	public function set($namespace, $key, $value) {
		$this->checkString($namespace);

		$this->quickCache->set(self::getMemNamespace($namespace), $key, $value);

		$filename = $this->getFilename($namespace, $key);
		$level    = error_reporting(0);

		touch($filename, 0777);
		file_put_contents($filename, serialize($value));

		error_reporting($level);
		return $value;
	}

	public function get($namespace, $key, $default = null) {
		$this->checkString($namespace);

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
		$this->checkString($namespace);

		if ($this->quickCache->exists(self::getMemNamespace($namespace), $key)) {
			return true;
		}

		return file_exists($this->getFilename($namespace, $key));
	}

	public function delete($namespace, $key) {
		$this->checkString($namespace);

		$this->quickCache->delete(self::getMemNamespace($namespace), $key);
		clearstatcache();

		return @unlink($this->getFilename($namespace, $key));
	}

	public function flush($namespace, $recursive = false) {
		$this->checkString($namespace);

		// flush quick cache

		$this->quickCache->flush(self::getMemNamespace($namespace), $recursive);

		// handle our own cache

		$namespace = $this->getDirFromNamespace($namespace);
		$root      = $this->dataDir.'/'.$namespace;

		// Wenn wir rekursiv löschen, können wir wirklich alles in diesem Verzeichnis
		// löschen.

		if ($recursive) {
			clearstatcache();
			return $this->deleteRecursive($root);
		}

		// Löschen wir nicht rekursiv, dürfen wir nur das data~-Verzeichnis
		// entfernen.

		else {
			$dataDir = 'data'.self::$safeDirChar;
			clearstatcache();
			return $this->deleteRecursive($root.'/'.$dataDir);
		}
	}

	private static function getMemNamespace($namespace) {
		return 'fscache.'.$namespace;
	}

	private static function createNamespaceDir($namespace, $root, $hash) {
		if (!empty($namespace)) {
			$thisPart = array_shift($namespace);
			$dir      = $root.'/'.$thisPart;

			self::makeDir($dir);
			return self::createNamespaceDir($namespace, $dir, $hash);
		}
		else {
			// Zuletzt erzeugen wir das Verteilungsverzeichnis für die Cache-Daten,
			// damit nicht alle Dateien in einem großen Verzeichnis leben müssen.

			// Dazu legen wir in dem Zielnamespace ein Verzeichnis "data~" an,
			// in dem die 00, 01, ... 99 ... FE, FF-Verzeichnisse erzeugt werden.
			// Dadurch vermeiden wir Kollisionen mit Namespaces, die auch Teilnamespaces
			// der Länge 2 haben.

			$dir = $root.'/data'.self::$safeDirChar;
			self::makeDir($dir);

			// Jetzt kommen die kleinen Verzeichnisse...

			$dir = $dir.'/'.self::cutHash($hash);
			self::makeDir($dir);

			return true;
		}
	}

	private static function dataDirExists($namespace, $root) {
		$namespace = self::getDirFromNamespace($namespace);
		$dataDir   = 'data'.self::$safeDirChar;
		$dirname   = $root.'/'.$namespace.'/'.$dataDir;

		clearstatcache();
		return is_dir($dirname);
	}

	private function getFilename($namespace, $key) {
		$namespace = $this->checkString($namespace);
		$key       = $this->checkString($key);
		$dir       = $this->dataDir;
		$hash      = md5($key);

		if (!self::dataDirExists($namespace, $dir)) {
			self::createNamespaceDir(explode('.', $namespace), $dir, $hash);
		}

		// Finalen Dateipfad erstellen

		$namespace = self::getDirFromNamespace($namespace);
		$dataDir   = 'data'.self::$safeDirChar;
		$hashPart  = self::cutHash($hash);
		$dir       = $dir.'/'.$namespace.'/'.$dataDir.'/'.$hashPart;

		self::makeDir($dir);
		return $dir.'/'.$hash;
	}

	private static function getSubNamespaces($namespace) {
		$namespace = self::getDirFromNamespace($this->checkString($namespace));
		$dir       = $this->dataDir.'/'.$namespace;
		$dataDir   = 'data'.self::$safeDirChar;

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

	private static function deleteRecursive($root) {
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
	private static function getDirFromNamespace($namespace) {
		return str_replace('.', '/', $namespace);
	}

	private static function makeDir($dir) {
		if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
			throw new BabelCache_Exception('Can\'t create directory in '.$dir.'.');
		}
	}

	private static function cutHash($hash) {
		return substr($hash, 0, 2);
	}
}
