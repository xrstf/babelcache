<?php
/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Filesystem Cache
 *
 * Namespaces will be mapped to directories and sub-directories. Each namespace
 * folder will then have a data~ directory in which the cache files will be
 * placed. Because of the restriction in BabelCache::checkString(), you cannot
 * use 'data~' in your namespaces, so no collisions can occur.
 *
 * Even though most modern filesystems can easily handle thousands of files in
 * a single directory, this cache will automatically distribute your data in
 * smaller directories, based on the calculated hash. So if the key 'myvalue'
 * is hashed '3423g4234...', the file will be placed in '/data~/34/3423g4234'.
 *
 * By changing cutHash(), you can adjust the length of those splitter
 * directories (1 if you only have a few values, 10 if you expected gazillions
 * of values). You can't change this value at runtime since it would totally
 * mess up your cache directories. Returning an empty string disables the
 * distribution.
 *
 * In most cases, this strategy to spread the data files is not necessary.
 * Before using it, consider using %BabelCache_Filesystem_Plain instead, which
 * should suffice in 99,9% of all cases (if you really need to store large
 * amount of data, consider using Memcached instead).
 *
 * @author Christoph Mewes
 * @see    BabelCache_Filesystem_Plain
 */
class BabelCache_Filesystem extends BabelCache implements BabelCache_Interface {
	protected $dataDir = '';    ///< string  absolute path to the cache directory

	protected static $safeDirChar = '~';  ///< string  special character that is used for the data directory
	protected static $dirPerm     = 0777; ///< int     permissions to use for created directories
	protected static $filePerm    = 0664; ///< int     permissions to use for created files

	/**
	 * Constructor
	 *
	 * Creates the object, tries to create the data directory and creates a new
	 * memory cache.
	 *
	 * @param string $dataDirectory  the full path to the cache directory (chmod 777)
	 */
	public function __construct($dataDirectory) {
		$this->makeDir($dataDirectory);
		$this->dataDir = $dataDirectory;
	}

	public static function isAvailable() {
		return true;
	}

	public function lock($namespace, $key, $duration = 1) {
		$key = $this->getFullKeyHelper($namespace, $key);
		$dir = $this->dataDir.'/lock#'.$key;

		clearstatcache();
		return @mkdir($dir, self::$dirPerm);
	}

	public function unlock($namespace, $key) {
		$key = $this->getFullKeyHelper($namespace, $key);
		$dir = $this->dataDir.'/lock#'.$key;

		clearstatcache();
		return is_dir($dir) ? rmdir($dir) : true;
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

		if (!is_dir($dir)) {
			return $this->get($namespace, $key, $default);
		}

		return $default;
	}

	public function set($namespace, $key, $value) {
		$filename = $this->getFilename($namespace, $key);
		$level    = error_reporting(0);

		touch($filename, self::$filePerm);
		file_put_contents($filename, serialize($value), LOCK_EX);

		error_reporting($level);
		return $value;
	}

	public function get($namespace, $key, $default = null) {
		$file = $this->getFilename($namespace, $key);

		if (!file_exists($file)) {
			return $default;
		}

		// open the file
		$handle = @fopen($file, 'r');

		// old filestats?
		if (!$handle) {
			clearstatcache();
			return $default;
		}

		// try to lock the file
		if (!flock($handle, LOCK_SH)) {
			fclose($handle);
			return $default;
		}

		// read it
		$data = file_get_contents($file);

		// unlock it again
		flock($handle, LOCK_UN);
		fclose($handle);

		return unserialize($data);
	}

	public function exists($namespace, $key) {
		return file_exists($this->getFilename($namespace, $key));
	}

	public function delete($namespace, $key) {
		return @unlink($this->getFilename($namespace, $key));
	}

	public function flush($namespace, $recursive = false) {
		$this->checkString($namespace);

		// handle our own cache

		$namespace = $this->getDirFromNamespace($namespace);
		$root      = $this->dataDir.'/'.$namespace;

		// Wenn wir rekursiv löschen, können wir wirklich alles in diesem Verzeichnis
		// löschen.

		if ($recursive) {
			return $this->deleteRecursive($root);
		}

		// Löschen wir nicht rekursiv, dürfen wir nur das data~-Verzeichnis
		// entfernen.

		else {
			$dataDir = 'data'.self::$safeDirChar;
			return $this->deleteRecursive($root.'/'.$dataDir);
		}
	}

	/**
	 * Creates the directories for a given namespace
	 *
	 * This method will recursively create the required directories to map a
	 * given namespace. Every created directory will be CHMOD 777.
	 *
	 * In the last step, a 'data~' directory is created, which will contain the
	 * partial hash directory (first two characters of the elements hash).
	 *
	 * @param  array  $namespace  the namespace, already split up
	 * @param  string $root       the current root directory (used for recursion)
	 * @param  string $hash       the hash of the element that will be stored
	 * @return boolean            always true
	 */
	protected function createNamespaceDir($namespace, $root, $hash) {
		if (!empty($namespace)) {
			$thisPart = array_shift($namespace);
			$dir      = $root.'/'.$thisPart;

			$this->makeDir($dir);
			return $this->createNamespaceDir($namespace, $dir, $hash);
		}
		else {
			// Zuletzt erzeugen wir das Verteilungsverzeichnis für die Cache-Daten,
			// damit nicht alle Dateien in einem großen Verzeichnis leben müssen.

			// Dazu legen wir in dem Zielnamespace ein Verzeichnis "data~" an,
			// in dem die 00, 01, ... 99 ... FE, FF-Verzeichnisse erzeugt werden.
			// Dadurch vermeiden wir Kollisionen mit Namespaces, die auch Teilnamespaces
			// der Länge 2 haben.

			$dir = $root.'/data'.self::$safeDirChar;
			$this->makeDir($dir);

			// Jetzt kommen die kleinen Verzeichnisse...

			$hash = $this->cutHash($hash);

			if (strlen($hash) > 0) {
				$dir = $dir.'/'.$hash;
				$this->makeDir($dir);
			}

			return true;
		}
	}

	/**
	 * Checks if the target directory exists
	 *
	 * @param  string $namespace  the target namespace
	 * @param  string $root       the root cach directory
	 * @return boolean            true if the directory exists, else false
	 */
	protected function dataDirExists($namespace, $root) {
		$namespace = $this->getDirFromNamespace($namespace);
		$dataDir   = 'data'.self::$safeDirChar;
		$dirname   = $root.'/'.$namespace.'/'.$dataDir;

		clearstatcache();
		return is_dir($dirname);
	}

	/**
	 * Gets the full filename of a cache element
	 *
	 * This method computes the absolute path for a given cache element. Missing
	 * directories will be created automatically.
	 *
	 * @param  string $namespace  element namespace
	 * @param  string $key        element key
	 * @return string             the full path
	 */
	protected function getFilename($namespace, $key) {
		$namespace = $this->checkString($namespace);
		$key       = $this->checkString($key);
		$dir       = $this->dataDir;
		$hash      = md5($key);

		if (!$this->dataDirExists($namespace, $dir)) {
			$this->createNamespaceDir(explode('.', $namespace), $dir, $hash);
		}

		// Finalen Dateipfad erstellen

		$namespace = $this->getDirFromNamespace($namespace);
		$dataDir   = 'data'.self::$safeDirChar;
		$hashPart  = $this->cutHash($hash);
		$dir       = $dir.'/'.$namespace.'/'.$dataDir;

		if (strlen($hashPart) > 0) {
			$dir .= '/'.$hashPart;
			$this->makeDir($dir);
		}

		return $dir.'/'.$hash;
	}

	/**
	 * Removes a directory and all children
	 *
	 * This method is used for flushing namespaces and removes all files and
	 * directories (recursively) in a given root directory.
	 *
	 * @param  string $root  the root from where to start
	 * @return boolean       true if everything was deleted, else false
	 */
	protected function deleteRecursive($root) {
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

			clearstatcache();
			error_reporting($level);
			return $status;
		}
		catch (UnexpectedValueException $e) {
			return false;
		}
	}

	/**
	 * Makes a directory fragment out of a namespace
	 *
	 * This is a simple helper that will replace all dots with slashes.
	 *
	 * @param  string $namespace  the namespace
	 * @return string             the namespace with replaced dots
	 */
	protected function getDirFromNamespace($namespace) {
		return str_replace('.', '/', $namespace);
	}

	/**
	 * Creates a directory
	 *
	 * This method will try to create a directory and chmod 777 it. If this
	 * fails, an exception is thrown.
	 *
	 * @throws BabelCache_Exception  if the directory could not be created
	 * @param  string $dir           the directory to create
	 */
	protected function makeDir($dir) {
		if (!is_dir($dir)) {
			if (!@mkdir($dir, self::$dirPerm, true)) {
				throw new BabelCache_Exception('Can\'t create directory in '.$dir.'.');
			}

			clearstatcache();
		}
	}

	/**
	 * Cut out part of a hash
	 *
	 * This method will return the first 2 characters of a given element hash.
	 * The partial hash is used to evenly distribute the cache elements in a
	 * single namespace.
	 *
	 * @param  string $hash  the element's hash
	 * @return string        the first 2 characters of that hash
	 */
	protected function cutHash($hash) {
		return substr($hash, 0, 2);
	}

	/**
	 * Set the permissions
	 *
	 * @param int $chmod  the new chmod value (like 0777)
	 */
	public static function setDirPermissions($chmod) {
		self::$dirPerm = (int) $chmod;
	}

	/**
	 * Set the permissions
	 *
	 * @param int $chmod  the new chmod value (like 0777)
	 */
	public static function setFilePermissions($chmod) {
		self::$filePerm = (int) $chmod;
	}
}
