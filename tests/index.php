<?php

require '../Autoload.php';
require 'Library.php';
require 'TestFactory.php';

print '<pre>';

info('Testing generateKey()...');
assertNotEquals(BabelCache::generateKey('1'), BabelCache::generateKey(1), 'generateKey() recognizes the type of variables.');
assertNotEquals('', BabelCache::generateKey(false), 'generateKey() handles false correctly.');
assertNotEquals('', BabelCache::generateKey(null), 'generateKey() handles null correctly.');

$factory = new TestFactory();
$obj     = $factory->getCache('Blackhole');

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
		$cache = $factory->getCache($cache);
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
	}
	catch (Exception $e) {
		fail('Exception: '.$e->getMessage());
	}
}