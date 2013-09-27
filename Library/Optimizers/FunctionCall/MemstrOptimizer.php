<?php

/**
 * MemnstrOptimizer
 *
 * Like 'strpos' but it returns a boolean value
 */
class MemstrOptimizer
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
			$str = Utils::addSlaches($expression['parameters'][1]['value']);
			unset($expression['parameters'][1]);
		}

		$resolvedParams = $call->getReadOnlyResolvedParams($expression['parameters'], $context, $expression);

		$context->headersManager->add('kernel/string');

		if (isset($str)) {
			return new CompiledExpression('bool', 'zephir_memnstr_str(' . $resolvedParams[0] . ', SL("' . $str . '"), "' . Compiler::getShortPath($expression['file']) . '", ' . $expression['line'] . ')', $expression);
		}

		return new CompiledExpression('bool', 'zephir_memnstr(' . $resolvedParams[0] . ', ' . $resolvedParams[1] . ', "' . Compiler::getShortPath($expression['file']) . '", ' . $expression['line'] . ')', $expression);
	}

}
