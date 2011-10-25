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
 * SQLite Cache
 *
 * This is an improved fallback cache for systems with "special needs"
 * regarding the filesystem. To avoid problems with file permissions, stati and
 * all the other stuff that makes the PHP file API so goddamn awful, use this
 * implementation.
 *
 * @author Christoph Mewes
 */
class BabelCache_SQLite extends BabelCache implements BabelCache_Interface {
	protected $pdo   = null;    ///< PDO    database connection
	protected $stmts = array(); ///< array  list of prepared statements

	/**
	 * Constructor
	 *
	 * @param string $connection  the already established connection
	 */
	public function __construct(PDO $connection) {
		$this->pdo = $connection;

		$stmt   = $this->pdo->query('SELECT * FROM sqlite_master WHERE type = "table" AND name = "babelcache"');
		$tables = $stmt->fetchAll();

		if (empty($tables)) {
			$sql = 'CREATE TABLE "babelcache" ("namespace" VARCHAR(255), "keyhash" VARCHAR(40), "payload" BLOB, PRIMARY KEY ("namespace", "keyhash"))';
			$this->pdo->exec($sql);
		}

		$stmt->closeCursor();
	}

	/**
	 * Connect to a database
	 *
	 * @param  string $databaseFile  full path to the database file
	 * @return PDO
	 */
	public static function connect($databaseFile) {
		return new PDO('sqlite:'.$databaseFile, null, null, array(
			PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_PERSISTENT => true
		));
	}

	public static function isAvailable() {
		return in_array('sqlite2', PDO::getAvailableDrivers());
	}

	protected function getStatement($key) {
		$where   = '"namespace" = :namespace AND "keyhash" = :hash';
		$queries = array(
			'insert'  => 'INSERT INTO "babelcache" ("namespace", "keyhash", "payload") VALUES (:namespace,:hash,:payload)',
			'replace' => 'INSERT OR REPLACE INTO "babelcache" ("namespace", "keyhash", "payload") VALUES (:namespace,:hash,:payload)',
			'select'  => 'SELECT "payload" FROM "babelcache" WHERE '.$where,
			'delete'  => 'DELETE FROM "babelcache" WHERE '.$where,
			'flush'   => 'DELETE FROM "babelcache" WHERE "namespace" = :namespace',
			'flushr'  => 'DELETE FROM "babelcache" WHERE "namespace" = :namespace OR "namespace" LIKE :likens',
		);

		if (!isset($this->stmts[$key])) {
			if (!isset($queries[$key])) {
				throw new BabelCache_Exception('Cannot find query for key '.$key.'.');
			}

			$this->stmts[$key] = $this->pdo->prepare($queries[$key]);
		}

		return $this->stmts[$key];
	}

	public function lock($namespace, $key, $duration = 1) {
		$this->begin();

		// lock already exists
		if ($this->isLocked($namespace, $key)) {
			return false;
		}

		// insert lock
		$stmt = $this->getStatement('insert');
		$stmt->execute(array('namespace' => $namespace, 'hash' => 'lock:'.sha1($key), 'payload' => ''));
		$stmt->closeCursor();

		// finished
		$this->commit();
		return true;
	}

	public function unlock($namespace, $key) {
		$stmt = $this->getStatement('delete');
		$stmt->execute(array('namespace' => $namespace, 'hash' => 'lock:'.sha1($key)));
		$stmt->closeCursor();

		return $stmt->rowCount() > 0;
	}

	public function isLocked($namespace, $key) {
		$lock = 'lock:'.sha1($key);
		$stmt = $this->getStatement('select');
		$stmt->execute(array('namespace' => $namespace, 'hash' => $lock));

		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		// lock already exists
		return !empty($row);
	}

	public function waitForObject($namespace, $key, $default = null, $maxWaitTime = 3, $checkInterval = 500) {
		$start          = microtime(true);
		$waited         = 0;
		$checkInterval *= 1000;

		while ($waited < $maxWaitTime && $this->isLocked($namespace, $key)) {
			usleep($checkInterval);
			$waited = microtime(true) - $start;
		}

		if (!$this->isLocked($namespace, $key)) {
			return $this->get($namespace, $key, $default);
		}

		return $default;
	}

	public function set($namespace, $key, $value) {
		$stmt    = $this->getStatement('replace');
		$payload = base64_encode(serialize($value));

		// update/insert data
		$stmt->execute(array('namespace' => $namespace, 'hash' => sha1($key), 'payload' => $payload));
		$stmt->closeCursor();

		return $value;
	}

	public function get($namespace, $key, $default = null) {
		$stmt = $this->getStatement('select');
		$stmt->execute(array('namespace' => $namespace, 'hash' => sha1($key)));

		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		if (empty($row) || empty($row['payload'])) {
			return $default;
		}

		return unserialize(base64_decode($row['payload']));
	}

	public function exists($namespace, $key) {
		$stmt = $this->getStatement('select');
		$stmt->execute(array('namespace' => $namespace, 'hash' => sha1($key)));

		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$stmt->closeCursor();

		return !empty($row);
	}

	public function delete($namespace, $key) {
		$stmt = $this->getStatement('delete');
		$stmt->execute(array('namespace' => $namespace, 'hash' => sha1($key)));
		$stmt->closeCursor();

		return $stmt->rowCount() > 0;
	}

	public function flush($namespace, $recursive = false) {
		$this->checkString($namespace);

		$stmt = $this->getStatement($recursive ? 'flushr' : 'flush');
		$stmt->bindValue('namespace', $namespace);

		if ($recursive) {
			$namespace = str_replace(array('\\', '%', '_'), array('\\\\', '\%', '\_'), $namespace);
			$likens    = "$namespace.%";

			$stmt->bindValue('likens', $likens);
		}

		$stmt->execute();
		return true;
	}

	protected function begin() {
		$this->pdo->beginTransaction();
	}

	protected function commit() {
		$this->pdo->commit();
	}
}
