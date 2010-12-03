<?php
/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

error_reporting(E_ALL | E_STRICT | E_NOTICE);
ini_set('display_errors', 'On');

require '../Autoload.php';
require 'Library.php';
require 'TestFactory.php';
require 'BabelCacheTest.php';

print '<pre>';

info('Testing generateKey()...');
assertNotEquals(BabelCache::generateKey('1'), BabelCache::generateKey(1), 'generateKey() recognizes the type of variables.');
assertNotEquals('', BabelCache::generateKey(false), 'generateKey() handles false correctly.');
assertNotEquals('', BabelCache::generateKey(null), 'generateKey() handles null correctly.');
assertNotEquals('', BabelCache::generateKey(array()), 'generateKey() handles an empty array correctly.');

$factory = new TestFactory();
$obj     = $factory->getCache('BabelCache_Blackhole');

info('Testing the factory...');
assertInstanceOf('BabelCache_Blackhole', $obj, 'The factory creates the right objects.');

try {
	$obj = $factory->getCache('UNKNOWN_CLASS');
	fail('The factory should have thrown an exception.');
}
catch (BabelCache_Exception $e) {
	success('The factory throws a BabelCache_Exception if the class was not found.');
}
catch (Exception $e) {
	fail('The factory threw a wrong exception ('.get_class($e).').');
}

info('Testing allowed namespace formats...');

$tester = new BabelCacheTest();
assertTrue($tester->testString('foo'), 'foo is a valid namespace.');
assertTrue($tester->testString('foo.bar'), 'foo.bar is a valid namespace.');
assertTrue($tester->testString('foo.-test-.123'), 'foo.-test-.123 is a valid namespace.');

assertFalse($tester->testString('.'), '. is not a valid namespace.');
assertFalse($tester->testString('.foo'), '.foo is not a valid namespace.');
assertFalse($tester->testString(''), 'Namespace cannot be empty.');
assertFalse($tester->testString('$pecia1 aren\'t allowed.'), 'Special characters are not allowed.');

$caches = array(
	'XCache',
	'APC',
	'Memcache',
	'Memcached',
	'eAccelerator',
	'ZendServer',
	'Memory',
	'Filesystem',
);

foreach ($caches as $cache) {
	info('Testing '.$cache.'...');

	try {
		$cache = $factory->getCache('BabelCache_'.$cache);
	}
	catch (BabelCache_Exception $e) {
		skip($cache.' is not avilable.');
		continue;
	}

	try {
		$cache->set('tests',           'foo', 'bar');
		$cache->set('tests.test.deep', 'foo2', null);

		$cache->flush('tests', true);

		assertFalse($cache->exists('tests', 'foo'), 'Flushing works in the same level.');
		assertFalse($cache->exists('tests.test.deep', 'foo2'), 'Flushing works in deeper levels.');

		$cache->set('tests2',           'foo', 'bar');
		$cache->set('tests2',           'foo1', 1);
		$cache->set('tests2',           'foo6', 3.41);
		$cache->set('tests2',           'foo2', false);
		$cache->set('tests2.blub',      'foo3', true);
		$cache->set('tests2.test.deep', 'foo4', null);
		$cache->set('tests2.johnny',    'foo5', new stdClass());

		assertEquals($cache->get('tests2', 'foo'), 'bar', 'set() can store strings.');
		assertEquals($cache->get('tests2', 'foo1'), 1, 'set() can store ints.');
		assertEquals($cache->get('tests2', 'foo6'), 3.41, 'set() can store floats.');
		assertEquals($cache->get('tests2', 'foo2'), false, 'set() can store false.');
		assertEquals($cache->get('tests2.blub', 'foo3'), true, 'set() can store true.');
		assertEquals($cache->get('tests2.test.deep', 'foo4'), null, 'set() can store null.');

		assertInstanceOf('stdClass', $cache->get('tests2.johnny', 'foo5'), 'set() can store objects.');
		assertTrue($cache->exists('tests2', 'foo6'), 'exists() works.');

		$cache->flush('tests2', true);

		$cache->set('tests2', 'foo', 3.41);
		$cache->set('tests2', 'foo', false);

		assertEquals($cache->get('tests2', 'foo'), false, 'set() can overwrite existing values.');

		$cache->delete('tests2', 'foo');
		assertFalse($cache->exists('tests2', 'foo'), 'delete() works.');
	}
	catch (Exception $e) {
		fail('Exception: '.$e->getMessage());
	}
}
