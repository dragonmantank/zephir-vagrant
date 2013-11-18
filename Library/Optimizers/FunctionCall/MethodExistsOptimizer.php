<?php

/*
 +--------------------------------------------------------------------------+
 | Zephir Language                                                          |
 +--------------------------------------------------------------------------+
 | Copyright (c) 2013 Zephir Team and contributors                          |
 +--------------------------------------------------------------------------+
 | This source file is subject the MIT license, that is bundled with        |
 | this package in the file LICENSE, and is available through the           |
 | world-wide-web at the following url:                                     |
 | http://zephir-lang.com/license.html                                      |
 |                                                                          |
 | If you did not receive a copy of the MIT license and are unable          |
 | to obtain it through the world-wide-web, please send a note to           |
 | license@zephir-lang.com so we can mail you a copy immediately.           |
 +--------------------------------------------------------------------------+
*/

/**
 * MethodExistsOptimizer
 *
 * Optimizes calls to 'method_exists' using internal function
 */
class MethodExistsOptimizer
{
	/**
	 *
	 * @param array $expression
	 * @param Call $call
	 * @param CompilationContext $context
	 */
	public function optimize(array $expression, Call $call, CompilationContext $context)
	{
		if (!isset($expression['parameters'])) {
			return false;
		}

		if (count($expression['parameters']) != 2) {
			return false;
		}

		if ($expression['parameters'][1]['type'] == 'string') {
			$str = Utils::addSlashes($expression['parameters'][1]['value']);
			unset($expression['parameters'][1]);
		}

		$context->headersManager->add('kernel/object');

		$resolvedParams = $call->getReadOnlyResolvedParams($expression['parameters'], $context, $expression);
		if (isset($str)) {
			return new CompiledExpression('bool', '(zephir_method_exists_ex(' . $resolvedParams[0] . ', SS("' . strtolower($str) . '") TSRMLS_CC) == SUCCESS)', $expression);
		}

		return new CompiledExpression('bool', '(zephir_method_exists(' . $resolvedParams[0] . ', ' . $resolvedParams[1] . ' TSRMLS_CC)  == SUCCESS)', $expression);
	}

}
