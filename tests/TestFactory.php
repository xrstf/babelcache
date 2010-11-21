<?php

class TestFactory extends BabelCache_Factory {
	protected function getCacheDirectory() {
		$dir = dirname(__FILE__).'/fscache';
		if (!is_dir($dir)) mkdir($dir, 0777);

		return $dir;
	}
}
