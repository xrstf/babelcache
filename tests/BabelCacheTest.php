<?php

class BabelCacheTest extends BabelCache {
	public function testString($str) {
		try {
			$this->checkString($str);
			return true;
		}
		catch (Exception $e) {
			return false;
		}
	}
}
