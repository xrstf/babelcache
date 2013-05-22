<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Adapter;

use wv\BabelCache\AdapterInterface;
use wv\BabelCache\Exception;
use wv\BabelCache\LockingInterface;

/**
 * Filesystem Cache
 *
 * @package BabelCache.Adapter
 */
class Filesystem implements AdapterInterface, LockingInterface {
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
	 * Checks whether a caching system is avilable
	 *
	 * This method will be called before an instance is created. It is supposed
	 * to check for the required functions and whether user data caching is
	 * enabled.
	 *
	 * @return boolean  true if the cache can be used, else false
	 */
	public static function isAvailable() {
		return true;
	}

	/**
	 * Gets a value out of the cache
	 *
	 * This method will try to read the value from the cache. If it's not found,
	 * $default will be returned.
	 *
	 * @param  string  $key    the object key
	 * @param  boolean $found  will be set to true or false when the method is finished
	 * @return mixed           the found value or null
	 */
	public function get($key, $default = null, &$found = null) {
		$found = false;
		$file  = $this->getFilename($key);

		if (!file_exists($file)) {
			return $default;
		}

		// open the file
		$handle = @fopen($file, 'rb');

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
	 * @param  string $key    the object key
	 * @param  mixed  $value  the value to store
	 * @return mixed          the set value
	 */
	public function set($key, $value) {
		$filename = $this->getFilename($key);
		$level    = error_reporting(0);

		touch($filename, $this->filePerm);
		file_put_contents($filename, serialize($value), LOCK_EX);

		error_reporting($level);

		return $value;
	}

	/**
	 * Checks whether a value exists
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value exists, else false
	 */
	public function exists($key) {
		return is_file($this->getFilename($key));
	}

	/**
	 * Removes a single value from the cache
	 *
	 * @param  string $key  the object key
	 * @return boolean      true if the value was deleted, else false
	 */
	public function remove($key) {
		return @unlink($this->getFilename($key));
	}

	/**
	 * Removes all values
	 *
	 * @return boolean  true if the flush was successful, else false
	 */
	public function clear() {
		try {
			$status   = true;
			$level    = error_reporting(0);
			$iterator = new \DirectoryIterator($this->dataDir);

			foreach ($iterator as $file) {
				if ($file->isFile()) {
					$status &= unlink($file->getPathname());
				}

				// try to remove locks as well
				elseif ($file->isDir() && !$file->isDot()) {
					$status &= rmdir($file->getPathname());
				}
			}

			clearstatcache();
			error_reporting($level);

			return !!$status;
		}
		catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Locks a key
	 *
	 * This method will create a lock for a specific key.
	 *
	 * @param  string $key  the key
	 * @return boolean      true if the lock was aquired, else false
	 */
	public function lock($key) {
		$dir = $this->dataDir.'/lock-'.sha1($key);

		clearstatcache();

		return @mkdir($dir, $this->dirPerm);
	}

	/**
	 * Releases a lock
	 *
	 * This method will remove a lock for a specific key.
	 *
	 * @param  string $key  the key
	 * @return boolean      true if the lock was released or there was no lock, else false
	 */
	public function unlock($key) {
		$dir = $this->dataDir.'/lock-'.sha1($key);

		clearstatcache();

		return is_dir($dir) ? rmdir($dir) : true;
	}

	/**
	 * Gets the full filename of a cache element
	 *
	 * @param  string $key  element key
	 * @return string       the full path
	 */
	protected function getFilename($key) {
		return $this->dataDir.'/'.sha1($key);
	}
}
