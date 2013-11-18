<?php

/*
 +----------------------------------------------------------------------+
 | Zephir Language                                                      |
 +----------------------------------------------------------------------+
 | Copyright (c) 2013 Zephir Team                                       |
 +----------------------------------------------------------------------+
 | This source file is subject to version 1.0 of the MIT license,       |
 | that is bundled with this package in the file LICENSE, and is        |
 | available through the world-wide-web at the following url:           |
 | http://www.zephir-lang.com/license                                   |
 |                                                                      |
 | If you did not receive a copy of the MIT license and are unable      |
 | to obtain it through the world-wide-web, please send a note to       |
 | license@zephir-lang.com so we can mail you a copy immediately.       |
 +----------------------------------------------------------------------+
*/

/**
 * ClassMethodParameters
 *
 * Represents the parameters defined in a method
 */
class ClassMethodParameters
{

	/**
	 *
	 * @param array $parameters
	 */
	public function __construct(array $parameters)
	{
		$this->_parameters = $parameters;
	}

	public function getParameters()
	{
		return $this->_parameters;
	}

}
