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
 * WhileStatement
 *
 * While statement, the same as in PHP/C
 */
class WhileStatement
{
	protected $_statement;

	public function __construct($statement)
	{
		$this->_statement = $statement;
	}

	/**
	 * Perform the compilation of code
	 */
	public function compile(CompilationContext $compilationContext)
	{
		$exprRaw = $this->_statement['expr'];

		$codePrinter = $compilationContext->codePrinter;

		/**
		 * Compound conditions can be evaluated in a single line of the C-code
		 */
		$codePrinter->output('while (1) {');

		$codePrinter->increaseLevel();

		/**
		 * Variables are initialized in a different way inside loops
		 */
		$compilationContext->insideCycle++;

		$expr = new EvalExpression();
		$condition = $expr->optimize($exprRaw, $compilationContext);

		$codePrinter->output('if (!(' . $condition . ')) {');
		$codePrinter->output("\t" . 'break;');
		$codePrinter->output('}');

		$codePrinter->decreaseLevel();

		/**
		 * Compile statements in the 'while' block
		 */
		if (isset($this->_statement['statements'])) {
			$st = new StatementsBlock($this->_statement['statements']);
			$st->compile($compilationContext);
		}

		/**
		 * Restore the cycle counter
		 */
		$compilationContext->insideCycle--;

		$codePrinter->output('}');

	}

}
