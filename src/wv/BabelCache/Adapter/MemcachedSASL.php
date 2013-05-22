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
use wv\BabelCache\IncrementInterface;
use wv\BabelCache\LockingInterface;

/**
 * Memcached with SASL wrapper
 *
 * This class implements the binary protocol for talking with a memcached
 * daemon. It supports authentification via SASL (username and password). In
 * contrast to the native PHP extensions, multiple servers with weights are
 * not supported. You can onyl connect to one server.
 *
 * This work has been based on Ronny Wang's MIT-licensed implementation, but
 * adds some feature requests and is adjusted to BabelCache's coding guidelines.
 *
 * @see     https://github.com/ronnywang/PHPMemcacheSASL
 * @author  Ronny Wang
 * @author  Christoph Mewes
 * @package BabelCache.Adapter
 */
class MemcachedSASL implements AdapterInterface, IncrementInterface, LockingInterface {
	const REQUEST_FORMAT  = 'CCnCCnNNNN';
	const RESPONSE_FORMAT = 'Cmagic/Copcode/nkeylength/Cextralength/Cdatatype/nstatus/Nbodylength/NOpaque/NCAS1/NCAS2';

	const MEMC_VAL_TYPE_MASK     = 0x0F;
	const MEMC_VAL_IS_STRING     = 0;
	const MEMC_VAL_IS_LONG       = 1;
	const MEMC_VAL_IS_DOUBLE     = 2;
	const MEMC_VAL_IS_BOOL       = 3;
	const MEMC_VAL_IS_SERIALIZED = 4;
	const MEMC_VAL_COMPRESSED    = 16; // 2^4

	const OPCODE_GET             = 0x00;
	const OPCODE_SET             = 0x01;
	const OPCODE_ADD             = 0x02;
	const OPCODE_DELETE          = 0x04;
	const OPCODE_INCREMENT       = 0x05;
	const OPCODE_FLUSH           = 0x08;
	const OPCODE_SASL_LIST_MECHS = 0x20;
	const OPCODE_SASL_AUTH       = 0x21;

	protected $socket;

	public function __construct($host, $port = 11211) {
		$errNo  = null;
		$errMsg = null;
		$socket = @stream_socket_client($host.':'.$port, $errNo, $errMsg);

		if (!$socket) {
			throw new Exception('Could not connect to '.$host.':'.$port.': '.$errMsg);
		}

		$this->socket = $socket;
	}

	/**
	 * Checks whether a caching system is avilable
	 *
	 * @return boolean  always true
	 */
	public static function isAvailable() {
		return true;
	}

	public function get($key, &$found = null) {
		$response = $this->send(self::OPCODE_GET, array('key' => $key));
		$found    = $response['status'] == 0;

		if (!$found) {
			return null;
		}

		$body = $data['body'];
		$type = $data['extra'] & self::MEMC_VAL_TYPE_MASK;

		return $this->decodeValue($data['body'], $data['extra']);
	}

	public function set($key, $value, $ttl = null) {
		list($value, $flags) = $this->encodeValue($value, 0);

		$extra    = pack('NN', $flags, $ttl);
		$response = $this->send(self::OPCODE_SET, array(
			'key'   => $key,
			'value' => $value,
			'extra' => $extra
		));

		return $this->isSuccess($response);
	}

	public function remove($key) {
		$response = $this->send(self::OPCODE_DELETE, array('key' => $key));

		return $this->isSuccess($response);
	}

	public function exists($key) {
		$response = $this->send(self::OPCODE_GET, array('key' => $key));

		return $this->isSuccess($response);
	}

	public function clear() {
		$response = $this->send(self::OPCODE_FLUSH);

		return $this->isSuccess($response);
	}

	public function increment($key) {
		$extra = pack('N2N2N',
			/*     offset */ 0, 1,
			/*    initial */ 0, 0,
			/* expiration */ 0xFFFFFFFF // means "fail if key does not exist"
		);

		$response = $this->send(self::OPCODE_INCREMENT, array(
			'key'   => $key,
			'extra' => $extra
		));

		return $this->isSuccess($response);
	}

	public function lock($key) {
		$response = $this->send(self::OPCODE_ADD, array(
			'key'   => 'lock:'.$key,
			'value' => 1
		));

		return $this->isSuccess($response);
	}

	public function unlock($key) {
		return $this->remove('lock:'.$key);
	}

	public function authenticate($user, $password) {
		$response = $this->send(self::OPCODE_SASL_AUTH, array(
			'key'   => 'PLAIN',
			'value' => chr(0).$user.chr(0).$password
		));

		if (!$this->isSuccess($response)) {
			throw new Exception($response['body'], $response['status']);
		}
	}

	protected function isSuccess(array $response) {
		return $response['status'] == 0;
	}

	protected function buildRequest(array $data) {
		$valueLength = $extraLength = $keyLength = 0;

		if (array_key_exists('extra', $data)) {
			$extraLength = strlen($data['extra']);
		}

		if (array_key_exists('key', $data)) {
			$keyLength = strlen($data['key']);
		}

		if (array_key_exists('value', $data)) {
			$valueLength = strlen($data['value']);
		}

		$bodyLength = $extraLength + $keyLength + $valueLength;
		$ret        = pack(self::REQUEST_FORMAT,
			0x80,
			$data['opcode'],
			$keyLength,
			$extraLength,
			array_key_exists('datatype', $data) ? $data['datatype'] : null,
			array_key_exists('status', $data)   ? $data['status']   : null,
			$bodyLength,
			array_key_exists('Opaque', $data) ? $data['Opaque'] : null,
			array_key_exists('CAS1', $data)   ? $data['CAS1']   : null,
			array_key_exists('CAS2', $data)   ? $data['CAS2']   : null
		);

		if (array_key_exists('extra', $data)) {
			$ret .= $data['extra'];
		}

		if (array_key_exists('key', $data)) {
			$ret .= $data['key'];
		}

		if (array_key_exists('value', $data)) {
			$ret .= $data['value'];
		}

		return $ret;
	}

	protected function decodeResponse($data) {
		return unpack(self::RESPONSE_FORMAT, $data);
	}

	protected function send($opcode, array $data = array(), $readResponse = true) {
		$data['opcode'] = $opcode;
		$payload        = $this->buildRequest($data);

		fwrite($this->socket, $payload);

		return $readResponse ? $this->receive() : $payload;
	}

	protected function receive() {
		$data     = fread($this->socket, 24);
		$response = $this->decodeResponse($data);

		if ($response['bodylength']) {
			$bodyLength = $response['bodylength'];
			$data       = '';

			while ($bodyLength > 0) {
				$chunk = fread($this->socket, $bodyLength);
				$data .= $chunk;

				$bodyLength -= strlen($chunk);
			}

			if ($response['extralength']) {
				$extra_unpacked    = unpack('Nint', substr($data, 0, $response['extralength']));
				$response['extra'] = $extra_unpacked['int'];
			}

			$response['key']  = substr($data, $response['extralength'], $response['keylength']);
			$response['body'] = substr($data, $response['extralength'] + $response['keylength']);
		}

		return $response;
	}

	/**
	 * encode a value and set a bitmask for remembering how to decode it
	 *
	 * @param  mixed $value
	 * @param  int   $flags
	 * @return array         [encodedValue, flags]
	 */
	protected function encodeValue($value, $flags) {
		if (is_string($value)) {
			$flags |= self::MEMC_VAL_IS_STRING;
		}
		elseif (is_long($value)) {
			$flags |= self::MEMC_VAL_IS_LONG;
		}
		elseif (is_double($value)) {
			$flags |= self::MEMC_VAL_IS_DOUBLE;
		}
		elseif (is_bool($value)) {
			$flags |= self::MEMC_VAL_IS_BOOL;
		}
		else {
			$value  = serialize($value);
			$flags |= self::MEMC_VAL_IS_SERIALIZED;
		}

		return array($value, $flags);
	}

	/**
	 * decode a previously encoded value
	 *
	 * @param  mixed $value
	 * @param  int   $flags
	 * @return mixed
	 */
	protected function decodeValue($value, $flags) {
		$type = $flags & self::MEMC_VAL_TYPE_MASK;

		switch ($type) {
			case self::MEMC_VAL_IS_STRING:
				$value = strval($value);
				break;

			case self::MEMC_VAL_IS_LONG:
				$value = intval($value);
				break;

			case self::MEMC_VAL_IS_DOUBLE:
				$value = doubleval($value);
				break;

			case self::MEMC_VAL_IS_BOOL:
				$value = $value ? true : false;
				break;

			case self::MEMC_VAL_IS_SERIALIZED:
				$value = unserialize($value);
				break;
		}

		return $value;
	}
}
