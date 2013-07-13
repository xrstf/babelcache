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
use wv\BabelCache\Factory;
use wv\BabelCache\Util;

/**
 * Filesystem Cache
 *
 * @package BabelCache.Cache
 */
class Filesystem implements CacheInterface {
	protected $dataDir;   ///< string  absolute path to the cache directory
	protected $filePerm;  ///< int     permissions to use for created files
	protected $dirPerm;   ///< int     permissions to use for lock directories
	protected $prefix;    ///< string  file name prefix

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
		$this->prefix   = '';
	}

	/**
	 * Checks whether a caching system is avilable
	 *
	 * This method will be called before an instance is created. It is supposed
	 * to check for the required functions and whether user data caching is
	 * enabled.
	 *
	 * @param  Factory $factory  the project's factory to give the adapter some more knowledge
	 * @return boolean           true if the cache can be used, else false
	 */
	public static function isAvailable(Factory $factory = null) {
		return true;
	}

	/**
	 * Sets the key prefix
	 *
	 * The key prefix will be put in front of the generated cache key, so that
	 * multiple installations of the same system can co-exist on the same
	 * machine.
	 *
	 * @param string $prefix  the prefix to use (will be trimmed)
	 */
	public function setPrefix($prefix) {
		$this->prefix = trim($prefix);
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
			// @codeCoverageIgnoreStart
			clearstatcache();
			return $default;
			// @codeCoverageIgnoreEnd
		}

		// try to lock the file
		if (!flock($handle, LOCK_SH)) {
			// @codeCoverageIgnoreStart
			fclose($handle);
			return $default;
			// @codeCoverageIgnoreEnd
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
	 * Deletes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function delete($namespace, $key) {
		return @unlink($this->getFilename($namespace, $key));
	}

	/**
	 * Deletes all values in a given namespace
	 *
	 * This method will delete all values by making them unavailable. For this,
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
		Util::checkString($namespace, 'namespace');

		$namespace = $this->hashNamespace($namespace);
		$namespace = $this->getDirFromNamespace($namespace);
		$root      = $this->dataDir.'/'.$namespace;

		if ($recursive) {
			return $this->deleteRecursive($root) && $this->deleteLocks();
		}
		else {
			return $this->deleteFiles($root) && $this->deleteLocks();
		}
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
		$dir = $this->getLockDir($namespace, $key);

		clearstatcache();

		return @mkdir($dir, $this->dirPerm);
	}

	/**
	 * Releases a lock
	 *
	 * This method will delete a lock for a specific key.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the lock was released, else false
	 */
	public function unlock($namespace, $key) {
		$dir = $this->getLockDir($namespace, $key);

		clearstatcache();

		return is_dir($dir) ? rmdir($dir) : false;
	}

	/**
	 * Check if a key is locked
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the key is locked, else false
	 */
	public function hasLock($namespace, $key) {
		return is_dir($this->getLockDir($namespace, $key));
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
		$namespace = Util::checkString($namespace, 'namespace');
		$key       = Util::checkString($key, 'key');
		$dir       = $this->dataDir;
		$hash      = md5($key);
		$namespace = $this->hashNamespace($namespace);
		$part      = $this->getDirFromNamespace($namespace);

		clearstatcache();

		if (!is_dir($dir.'/'.$part)) {
			$this->createNamespaceDir(explode('.', $part), $dir, $hash);
		}

		return $dir.'/'.$part.'/'.$hash;
	}

	/**
	 * Return the directory for locking a key
	 *
	 * @param  string $namespace
	 * @param  string $key
	 * @return string
	 */
	protected function getLockDir($namespace, $key) {
		$key = Util::getFullKeyHelper($namespace, $key);

		return $this->dataDir.'/lock-'.sha1($key);
	}

	/**
	 * Deletes a directory and all children
	 *
	 * This method is used for flushing namespaces and deletes all files and
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
			$dirIterator = new \RecursiveDirectoryIterator($root);
			$recIterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::CHILD_FIRST);
			$status      = true;
			$level       = error_reporting(0);

			foreach ($recIterator as $file) {
				if (!$recIterator->isDot() && $file->isDir()) {
					$status &= rmdir($file->getPathname());
				}
				elseif ($file->isFile()) {
					$status &= unlink($file->getPathname());
				}
			}

			rmdir($root);

			$recIterator = null;
			$dirIterator = null;

			clearstatcache();
			error_reporting($level);

			return !!$status;
		}
		// @codeCoverageIgnoreStart
		catch (\Exception $e) {
			return false;
		}
		// @codeCoverageIgnoreEnd
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
		$namespace = trim($namespace, '.');

		if ($this->prefix) {
			$namespace = $this->prefix.'.'.$namespace;
		}

		return str_replace('.', '/', $namespace);
	}

	/**
	 * Hash a namespace
	 *
	 * Namespace hashing works by hashing all individual steps on their own.
	 *
	 * @param  string $namespace  the namespace
	 * @return string             the hashed namespace
	 */
	protected function hashNamespace($namespace) {
		$parts = explode('.', $namespace);

		foreach ($parts as $idx => $part) {
			$parts[$idx] = sha1($part);
		}

		return implode('.', $parts);
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
			// @codeCoverageIgnoreStart
			if (!@mkdir($dir, $this->dirPerm, true)) {
				throw new Exception('Can\'t create directory in '.$dir.'.');
			}
			// @codeCoverageIgnoreEnd

			clearstatcache();
		}
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

		return !!$status;
	}

	protected function deleteLocks() {
		$dirs   = glob($this->dataDir.'/*', GLOB_NOSORT | GLOB_ONLYDIR);
		$status = true;
		$level  = 0; //error_reporting(0);

		foreach ($dirs as $dir) {
			$basename = basename($dir);

			if (substr($basename, 0, 5) === 'lock-') {
				$status &= rmdir($dir);
			}
		}

		clearstatcache();
		error_reporting($level);

		return !!$status;
	}
}
