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

use LogicException;
use Memcached as MemcachedExt;
use wv\BabelCache\Factory;

/**
 * ElastiCache wrapper
 *
 * This works like the regular Memcached adapter, though it requires the PECL
 * extension provided by Amazon, AmazonElastiCacheClusterClient, to work.
 *
 * Instead of building an instance with a Memcached daemon address, you use the
 * cache cluster's configuration endpoint.
 *
 * @see     http://docs.aws.amazon.com/AmazonElastiCache/latest/UserGuide/
 * @package BabelCache.Adapter
 */
class ElastiCache extends Memcached {
	public function __construct($configurationEndpoint, $port, $persistentID = null) {
		parent::__construct($persistentID);

		$client = $this->getMemcached();

		// see http://php.net/manual/en/memcached.constants.php
		$client->setOptions(array(
			// enable Auto Discovery (AWS-only option)
			MemcachedExt::OPT_CLIENT_MODE => MemcachedExt::DYNAMIC_CLIENT_MODE,

			// make sure our keys don't get mixed up when we scale the cluster
			MemcachedExt::OPT_DISTRIBUTION => MemcachedExt::DISTRIBUTION_CONSISTENT,

			// enabled because PHP manual recommended to do so
			MemcachedExt::OPT_LIBKETAMA_COMPATIBLE => true
		));

		// connect to the config endpoint
		$client->addServer($configurationEndpoint, $port);
	}

	/**
	 * {@inheritdoc}
	 */
	public static function isAvailable(Factory $factory = null) {
		if (!class_exists('Memcached')) return false;
		if (!defined('Memcached::OPT_CLIENT_MODE')) return false;

		if (!$factory) return true;

		$endpoint = $factory->getElastiCacheEndpoint();

		return !empty($endpoint);
	}

	/**
	 * {@inheritdoc}
	 */
	public function addServer($host, $port = 11211, $weight = 1) {
		throw new LogicException('Servers are fully managed by Amazon.');
	}
}
