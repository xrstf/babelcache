<?php

function success($text) {
	print '<span style="color:green"><strong>SUCCESS:</strong> '.$text.'</span>';
	print PHP_EOL;
}

function fail($text) {
	print '<span style="color:red"><strong>FAILURE:</strong> '.$text.'</span>';
	print PHP_EOL;
}

function skip($text) {
	print '<span style="color:#777"><strong>SKIPPED:</strong> '.$text.'</span>';
	print PHP_EOL;
}

function info($text) {
	print '<span style="color:#000">'.$text.'</span>';
	print PHP_EOL;
}

function assertFalse($a, $text) {
	assertEquals($a, false, $text);
}

function assertTrue($a, $text) {
	assertEquals($a, true, $text);
}

function assertEquals($a, $b, $text) {
	if ($a === $b) success($text);
	else fail($text);
}

function assertNotEquals($a, $b, $text) {
	if ($a !== $b) success($text);
	else fail($text);
}

function assertInstanceOf($className, $object, $text) {
	if ($object instanceof $className) success($text);
	else fail($text);
}
