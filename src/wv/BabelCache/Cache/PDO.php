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
use wv\BabelCache\Util;

/**
 * PDO Cache
 *
 * This cache will use a PDO database to cache data. The DBMS specific DDL
 * statements can be found in the db-schema directory.
 *
 * @package BabelCache.Cache
 */
abstract class PDO implements CacheInterface {
	protected $pdo     = null;    ///< PDO     database connection
	protected $table   = null;    ///< string  table name
	protected $queries = array(); ///< array   list of queries
	protected $stmts   = array(); ///< array   list of prepared statements
	protected $prefix  = '';      ///< string  cache element prefix

	/**
	 * Constructor
	 *
	 * @param PDO    $connection  the already established connection
	 * @param string $tableName
	 */
	public function __construct(\PDO $connection, $tableName) {
		$this->pdo     = $connection;
		$this->table   = $tableName;
		$this->queries = $this->getQueries($tableName);
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

	abstract protected function getQueries($table);
	abstract protected function supportsRawData();

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
		$stmt  = $this->getStatement('get');

		$stmt->execute(array('prefix' => $this->prefix, 'namespace' => $namespace, 'key' => $key));

		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		$found = !empty($row) && !empty($row['payload']);

		if (!$found) {
			return $default;
		}

		$payload = $this->supportsRawData() ? $row['payload'] : base64_decode($row['payload']);

		return unserialize($payload);
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
		$stmt    = $this->getStatement('set');
		$payload = serialize($value);

		if (!$this->supportsRawData()) {
			$payload = base64_encode($payload);
		}

		// update/insert data
		$stmt->execute(array('prefix' => $this->prefix, 'namespace' => $namespace, 'key' => $key, 'payload' => $payload));
		$stmt->closeCursor();

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
		$stmt = $this->getStatement('exists');
		$stmt->execute(array('prefix' => $this->prefix, 'namespace' => $namespace, 'key' => $key));

		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		return !empty($row);
	}

	/**
	 * Deletes a single value from the cache
	 *
	 * @param  string $namespace  the namespace to use
	 * @param  string $key        the object key
	 * @return boolean            true if the value was deleted, else false
	 */
	public function delete($namespace, $key) {
		$stmt = $this->getStatement('delete');
		$stmt->execute(array('prefix' => $this->prefix, 'namespace' => $namespace, 'key' => $key));
		$stmt->closeCursor();

		return $stmt->rowCount() > 0;
	}

	/**
	 * Deletes all values in a given namespace
	 *
	 * @param  string  $namespace  the namespace to use
	 * @param  boolean $recursive  if set to true, all child namespaces will be cleared as well
	 * @return boolean             true if the flush was successful, else false
	 */
	public function clear($namespace, $recursive = false) {
		Util::checkString($namespace, 'namespace');

		if ($recursive) {
			$stmt   = $this->getStatement('rclear');
			$quoted = str_replace(array('\\', '%', '_'), array('\\\\', '\%', '\_'), $namespace);

			$stmt->execute(array('prefix' => $this->prefix, 'namespace' => $namespace, 'nslike' => $namespace.'.%'));
		}
		else {
			$stmt = $this->getStatement('clear');
			$stmt->execute(array('prefix' => $this->prefix, 'namespace' => $namespace));
		}

		// clear ALL locks, as per specs
		$stmt = $this->getStatement('lclear');
		$stmt->execute(array('prefix' => $this->prefix, 'key' => 'lock:%'));

		return true;
	}

	/**
	 * Locks a key
	 *
	 * This method will create a lock for a specific key.
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the lock was aquired, else false
	 */
	public function lock($namespace, $key) {
		$stmt = $this->getStatement('lock');
		$stmt->execute(array('prefix' => $this->prefix, 'namespace' => $namespace, 'key' => 'lock:'.sha1($key)));

		return $stmt->rowCount() > 0;
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
		$stmt = $this->getStatement('delete');
		$stmt->execute(array('prefix' => $this->prefix, 'namespace' => $namespace, 'key' => 'lock:'.sha1($key)));

		return $stmt->rowCount() > 0;
	}

	/**
	 * Check if a key is locked
	 *
	 * @param  string $namespace  the namespace
	 * @param  string $key        the key
	 * @return boolean            true if the key is locked, else false
	 */
	public function hasLock($namespace, $key) {
		$stmt = $this->getStatement('exists');
		$stmt->execute(array('prefix' => $this->prefix, 'namespace' => $namespace, 'key' => 'lock:'.sha1($key)));

		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		return !empty($row);
	}

	protected function getStatement($key) {
		if (!isset($this->stmts[$key])) {
			if (!isset($this->queries[$key])) {
				throw new Exception('Cannot find query for key '.$key.'.');
			}

			$this->stmts[$key] = $this->pdo->prepare($this->queries[$key]);
		}

		return $this->stmts[$key];
	}
}
