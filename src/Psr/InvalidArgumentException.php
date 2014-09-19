<?php

/**
 *
 * This file is part of the Apix Project.
 *
 * (c) Franck Cassedanne <franck at ouarz.net>
 *
 * @license     http://opensource.org/licenses/BSD-3-Clause  New BSD License
 *
 */

namespace Apix\Cache\Psr;

/**
 * Exception for invalid cache arguements.
 */
class InvalidArgumentException
extends \InvalidArgumentException
implements \Psr\Cache\InvalidArgumentException
{ }
