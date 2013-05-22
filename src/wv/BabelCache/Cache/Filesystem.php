<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Cache;

use wv\BabelCache\CacheInterface;
use wv\BabelCache\Exception;

/**
 * Filesystem Cache
 *
 * @package BabelCache.Cache
 */
class Filesystem implements CacheInterface {
	protected $dataDir;   ///< string  absolute path to the cache directory
	protected $filePerm;  ///< int     permissions to use for created files
	protected $dirPerm;   ///< int     permissions to use for lock directories

	/**
	 * Constructor
	 *
	 * @param string $dataDirectory  the full path to the cache directory
	 * @param int    $filePerm       file permissions to use
	 * @param int    $dirPerm        directory permissions to use
	 */
	public function __construct($dataDirectory, $filePerm = 0664, $dirPerm = 0777) {
		if (!is_dir($dataDirectory)) {
			throw new Exception('Invalid cache directory "'.$dataDirectory.'" given.');
		}

		$this->dataDir  = $dataDirectory;
		$this->filePerm = (int) $filePerm;
		$this->dirPerm  = (int) $dirPerm;
	}

	/**
	 * Gets a value out of the cache
	 *
	 * This method will try to read the value from the cache. If it's not found,
	 * $default will be returned.
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @param  mixed  $default    the default value
	 * @return mixed              the found value or $default
	 */
	public function get($namespace, $key, $default = null, &$found = null) {
		$found = false;
		$file  = $this->getFilename($namespace, $key);

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

		$found = true;

		return unserialize($data);
	}

	/**
	 * Sets a value
	 *
	 * This method will put a value into the cache. If it already exists, it
	 * will be overwritten.
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @param  mixed  $value      the value to store
	 * @return mixed              the set value
	 */
	public function set($namespace, $key, $value) {
		$filename = $this->getFilename($namespace, $key);
		$level    = error_reporting(0);

		touch($filename, $this->filePerm);
		file_put_contents($filename, serialize($value), LOCK_EX);

		error_reporting($level);
		return $value;
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value exists, else false
	 */
	public function exists($namespace, $key) {
		return is_file($this->getFilename($namespace, $key));
	}

	/**
	 * Removes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function remove($namespace, $key) {
		return @unlink($this->getFilename($namespace, $key));
	}

	/**
	 * Removes all values in a given namespace
	 *
	 * This method will remove all values by making them unavailable. For this,
	 * the version number of the flushed namespace is increased by one.
	 *
	 * Implementations are *not* required to support non-recursive flushes. If
	 * those are not supported, a recursive flush must be performed instead.
	 * Userland code should assume that every clear operation is recursive and
	 * the $recursive flag is a mere optimization hint.
	 *
	 * @param  string  $namespace  the namespace to use
	 * @param  boolean $recursive  if set to true, all child namespaces will be cleared as well
	 * @return boolean             true if the flush was successful, else false
	 */
	public function clear($namespace, $recursive = false) {
		$this->checkString($namespace, 'namespace');

		$namespace = $this->getDirFromNamespace($namespace);
		$root      = $this->dataDir.'/'.$namespace;

		return $recursive ? $this->deleteRecursive($root) : $this->deleteFiles($root);
	}

	/**
	 * Locks a key
	 *
	 * This method will create a lock for a specific key. Caches that don't
	 * support native locking will use a special "lock:key" value.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the lock was aquired, else false
	 */
	public function lock($namespace, $key, $duration = 1) {
		$key = $this->getFullKeyHelper($namespace, $key);
		$dir = $this->dataDir.'/lock-'.sha1($key);

		clearstatcache();

		return @mkdir($dir, parent::$dirPerm);
	}

	/**
	 * Releases a lock
	 *
	 * This method will remove a lock for a specific key.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the lock was released, else false
	 */
	public function unlock($namespace, $key) {
		$key = $this->getFullKeyHelper($namespace, $key);
		$dir = $this->dataDir.'/lock-'.sha1($key);

		clearstatcache();

		return is_dir($dir) ? rmdir($dir) : true;
	}

	/**
	 * Creates the directories for a given namespace
	 *
	 * This method will recursively create the required directories to map a
	 * given namespace.
	 *
	 * @param  array  $namespace  the namespace, already split up
	 * @param  string $root       the current root directory (used for recursion)
	 * @return boolean            always true
	 */
	protected function createNamespaceDir($namespace, $root) {
		if (!empty($namespace)) {
			$thisPart = array_shift($namespace);
			$dir      = $root.'/'.$thisPart;

			$this->makeDir($dir);

			return $this->createNamespaceDir($namespace, $dir);
		}

		return true;
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
		$namespace = $this->checkString($namespace, 'namespace');
		$key       = $this->checkString($key, 'key');
		$dir       = $this->dataDir;
		$hash      = md5($key);
		$part      = $this->getDirFromNamespace($namespace);

		clearstatcache();

		if (!is_dir($dir.'/'.$part)) {
			$this->createNamespaceDir(explode('.', $namespace), $dir, $hash);
		}

		return $dir.'/'.$part.'/'.$hash;
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
			if (!@mkdir($dir, $this->dirPerm, true)) {
				throw new Exception('Can\'t create directory in '.$dir.'.');
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

	protected function deleteFiles($root) {
		if (!is_dir($root)) return true;

		$files  = glob($root.'/*', GLOB_NOSORT);
		$status = true;
		$level  = error_reporting(0);

		foreach ($files as $file) {
			if (is_dir($file)) continue;
			$status &= unlink($file);
		}

		clearstatcache();
		error_reporting($level);

		return $status;
	}
}
