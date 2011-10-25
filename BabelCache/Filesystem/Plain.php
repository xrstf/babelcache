<?php
/*
 * Copyright (c) 2011, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * Filesystem Cache
 *
 * This is the fallback caching strategy, that should be used if no in-memory
 * cache is available. It only requires write permissions to a specific
 * directory.
 *
 * Namespaces will be mapped to directories and sub-directories.
 *
 * @author Christoph Mewes
 * @see    BabelCache_Memory
 */
class BabelCache_Filesystem_Plain extends BabelCache_Filesystem {
	public function lock($namespace, $key, $duration = 1) {
		$key = $this->getFullKeyHelper($namespace, $key);
		$dir = $this->dataDir.'/lock_'.sha1($key);

		clearstatcache();
		return @mkdir($dir, parent::$dirPerm);
	}

	public function unlock($namespace, $key) {
		$key = $this->getFullKeyHelper($namespace, $key);
		$dir = $this->dataDir.'/lock_'.sha1($key);

		clearstatcache();
		return is_dir($dir) ? rmdir($dir) : true;
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 50) {
		$key            = $this->getFullKeyHelper($namespace, $key);
		$dir            = $this->dataDir.'/lock_'.sha1($key);
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

	public function flush($namespace, $recursive = false) {
		$this->checkString($namespace);

		$namespace = $this->getDirFromNamespace($namespace);
		$root      = $this->dataDir.'/'.$namespace;

		return $recursive ? $this->deleteRecursive($root) : $this->deleteFiles($root);
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
	 * @param  string $hash       UNUSED
	 * @return boolean            always true
	 */
	protected function createNamespaceDir($namespace, $root, $hash) {
		if (!empty($namespace)) {
			$thisPart = array_shift($namespace);
			$dir      = $root.'/'.$thisPart;

			$this->makeDir($dir);
			return $this->createNamespaceDir($namespace, $dir, null);
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
		$namespace = $this->checkString($namespace);
		$key       = $this->checkString($key);
		$dir       = $this->dataDir;
		$hash      = md5($key);
		$part      = $this->getDirFromNamespace($namespace);

		clearstatcache();

		if (!is_dir($dir.'/'.$part)) {
			$this->createNamespaceDir(explode('.', $namespace), $dir, $hash);
		}

		return $dir.'/'.$part.'/'.$hash;
	}

	protected function deleteFiles($root) {
		if (!is_dir($root)) return true;

		$files  = glob($root.'/*', GLOB_NOSORT);
		$status = true;
		$level  = error_reporting(0);

		foreach ($files as $file) {
			if (is_dir($root.'/'.$file)) continue;
			$status &= unlink($root.'/'.$file);
		}

		clearstatcache();
		error_reporting($level);

		return $status;
	}
}
