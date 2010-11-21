<?php

class TestFactory extends BabelCache_Factory {
	protected function getMemcacheAddress() { return array('localhost', 11211); }
	protected function getPrefix()          { return ''; }
	protected function getCacheDirectory()  { return ''; }
}
