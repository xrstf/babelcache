<?php

require_once 'BabelCache.php';
require_once 'Blackhole.php';

BabelCache_Suite::run();

class BabelCache_Suite {
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite();
		$suite->addTestSuite('BabelCacheTest');
		$suite->addTestSuite('BlackholeTest');
		$suite->addTestSuite('GenericTest');
		return $suite;
	}

	public static function run() {
		$suite  = self::suite();
		$result = new PHPUnit_Framework_TestResult();

		$suite->run($result);
	}
}
