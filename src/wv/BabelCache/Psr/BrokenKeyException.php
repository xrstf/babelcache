<?php
/*
 * Copyright (c) 2013, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace wv\BabelCache\Psr;

use Psr\Cache\InvalidArgumentException;

/**
 * Specialized invalid argument exception
 *
 * @package BabelCache.Psr
 */
class BrokenKeyException extends Exception implements InvalidArgumentException {
}
