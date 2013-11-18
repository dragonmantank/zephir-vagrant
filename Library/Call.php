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
 * Call
 *
 * Base class for common functionality in functions/calls
 */
class Call
{

	/**
	 * Call expression
	 */
	protected $_expression;

	protected $_mustInit;

	protected $_symbolVariable;

	protected $_isExpecting = false;

	protected $_resolvedParams;

	protected $_resolvedTypes = array();

	protected $_resolvedDynamicTypes = array();

	protected $_temporalVariables = array();

	/**
	 * Processes the symbol variable that will be used to return
	 * the result of the symbol call
	 *
	 * @param \CompilationContext $compilationContext
	 */
	public function processExpectedReturn($compilationContext)
	{

		$expr = $this->_expression;
		$expression = $expr->getExpression();

		/**
		 * Create temporary variable if needed
		 */
		$mustInit = false;
		$symbolVariable = null;
		$isExpecting = $expr->isExpectingReturn();
		if ($isExpecting) {
			$symbolVariable = $expr->getExpectingVariable();
			if (is_object($symbolVariable)) {
				$readDetector = new ReadDetector($expression);
				if ($readDetector->detect($symbolVariable->getName(), $expression)) {
					$symbolVariable = $compilationContext->symbolTable->getTempVariableForWrite('variable', $compilationContext, $expression);
				} else {
					$mustInit = true;
				}
			} else {
				$symbolVariable = $compilationContext->symbolTable->getTempVariableForWrite('variable', $compilationContext, $expression);
			}
		}

		$this->_mustInit = $mustInit;
		$this->_symbolVariable = $symbolVariable;
		$this->_isExpecting = $isExpecting;
	}

	/**
	 * Processes the symbol variable that will be used to return
	 * the result of the symbol call. If a temporal variable is used
	 * as returned value only the body is freed between calls
	 *
	 * @param \CompilationContext $compilationContext
	 */
	public function processExpectedComplexLiteralReturn($compilationContext)
	{

		$expr = $this->_expression;
		$expression = $expr->getExpression();

		/**
		 * Create temporary variable if needed
		 */
		$mustInit = false;
		$isExpecting = $expr->isExpectingReturn();
		if ($isExpecting) {
			$symbolVariable = $expr->getExpectingVariable();
			if (is_object($symbolVariable)) {
				$readDetector = new ReadDetector($expression);
				if ($readDetector->detect($symbolVariable->getName(), $expression)) {
					$symbolVariable = $compilationContext->symbolTable->getTempComplexLiteralVariableForWrite('variable', $compilationContext, $expression);
				} else {
					$mustInit = true;
				}
			} else {
				$symbolVariable = $compilationContext->symbolTable->getTempComplexLiteralVariableForWrite('variable', $compilationContext, $expression);
			}
		}

		$this->_mustInit = $mustInit;
		$this->_symbolVariable = $symbolVariable;
		$this->_isExpecting = $isExpecting;
	}

	public function isExpectingReturn()
	{
		return $this->_isExpecting;
	}

	/**
	 * Returns if the symbol to be returned by the call must be initialized
	 *
	 * @return boolean
	 */
	public function mustInitSymbolVariable()
	{
		return $this->_mustInit;
	}

	/**
	 * Returns the symbol variable that must be returned by the call
	 *
	 * @return \Variable
	 */
	public function getSymbolVariable()
	{
		return $this->_symbolVariable;
	}

	/**
	 * Resolves paramameters
	 *
	 * @param array $parameters
	 * @param CompilationContext $compilationContext
	 * @param array $expression
	 * @param boolean $readOnly
	 * @return array
	 */
	public function getResolvedParamsAsExpr($parameters, $compilationContext, $expression, $readOnly=false)
	{
		if (!$this->_resolvedParams) {
			$params = array();
			foreach ($parameters as $parameter) {
				$paramExpr = new Expression($parameter);
				switch ($parameter['type']) {
					case 'property-access':
					case 'array-access':
					case 'static-property-access':
						$paramExpr->setReadOnly(true);
						break;
					default:
						$paramExpr->setReadOnly($readOnly);
						break;
				}
				$params[] = $paramExpr->compile($compilationContext);
			}
			$this->_resolvedParams = $params;
		}
		return $this->_resolvedParams;
	}

	/**
	 * Resolve parameters getting aware that the target function/method could retain or change
	 * the parameters
	 *
	 * @param array $parameters
	 * @param \CompilationContext $compilationContext
	 * @param array $expression
	 * @return array
	 */
	public function getResolvedParams($parameters, $compilationContext, $expression)
	{

		$codePrinter = $compilationContext->codePrinter;

		$exprParams = $this->getResolvedParamsAsExpr($parameters, $compilationContext, $expression);

		$params = array();
		$types = array();
		$dynamicTypes = array();
		foreach ($exprParams as $compiledExpression) {
			$expression = $compiledExpression->getOriginal();
			switch ($compiledExpression->getType()) {
				case 'null':
					$params[] = 'ZEPHIR_GLOBAL(global_null)';
					$types[] = $compiledExpression->getType();
					$dynamicTypes[] = $compiledExpression->getType();
					break;
				case 'int':
				case 'uint':
				case 'long':
					$parameterVariable = $compilationContext->symbolTable->getTempVariableForWrite('variable', $compilationContext, $expression);
					$codePrinter->output('ZVAL_LONG(' . $parameterVariable->getName() . ', ' . $compiledExpression->getCode() . ');');
					$this->_temporalVariables[] = $parameterVariable;
					$params[] = $parameterVariable->getName();
					$types[] = $compiledExpression->getType();
					$dynamicTypes[] = $compiledExpression->getType();
					break;
				case 'double':
					$parameterVariable = $compilationContext->symbolTable->getTempVariableForWrite('variable', $compilationContext, $expression);
					$codePrinter->output('ZVAL_DOUBLE(' . $parameterVariable->getName() . ', ' . $compiledExpression->getCode() . ');');
					$this->_temporalVariables[] = $parameterVariable;
					$params[] = $parameterVariable->getName();
					$types[] = $compiledExpression->getType();
					break;
				case 'bool':
					if ($compiledExpression->getCode() == 'true') {
						$params[] = 'ZEPHIR_GLOBAL(global_true)';
					} else {
						$params[] = 'ZEPHIR_GLOBAL(global_false)';
					}
					$types[] = $compiledExpression->getType();
					$dynamicTypes[] = $compiledExpression->getType();
					break;
				case 'ulong':
				case 'string':
					$parameterVariable = $compilationContext->symbolTable->getTempVariableForWrite('variable', $compilationContext, $expression);
					$codePrinter->output('ZVAL_STRING(' . $parameterVariable->getName() . ', "' . $compiledExpression->getCode() . '", 1);');
					$this->_temporalVariables[] = $parameterVariable;
					$params[] = $parameterVariable->getName();
					$types[] = $compiledExpression->getType();
					$dynamicTypes[] = $compiledExpression->getType();
					break;
				case 'variable':
					$parameterVariable = $compilationContext->symbolTable->getVariableForRead($compiledExpression->getCode(), $compilationContext, $expression);
					switch ($parameterVariable->getType()) {
						case 'int':
						case 'uint':
						case 'long':
						case 'ulong': /* ulong must be stored in string */
							$parameterTempVariable = $compilationContext->symbolTable->getTempVariableForWrite('variable', $compilationContext, $expression);
							$codePrinter->output('ZVAL_LONG(' . $parameterTempVariable->getName() . ', ' . $parameterVariable->getName() . ');');
							$params[] = $parameterTempVariable->getName();
							$this->_temporalVariables[] = $parameterTempVariable;
							$types[] = $parameterVariable->getType();
							$dynamicTypes[] = $parameterVariable->getType();
							break;
						case 'bool':
							$params[] = '(' . $parameterVariable->getName() . ' ? ZEPHIR_GLOBAL(global_true) : ZEPHIR_GLOBAL(global_false))';
							$types[] = $parameterVariable->getType();
							$dynamicTypes[] = $parameterVariable->getType();
							break;
						case 'string':
							$params[] = $parameterVariable->getName();
							$types[] = $parameterVariable->getType();
							$dynamicTypes[] = $parameterVariable->getType();
							break;
						case 'variable':
							$params[] = $parameterVariable->getName();
							$types[] = $parameterVariable->getType();
							$dynamicTypes[] = $parameterVariable->getDynamicTypes();
							break;
						default:
							throw new CompilerException("Cannot use variable type: " . $parameterVariable->getType() . " as parameter", $expression);
					}
					break;
				default:
					throw new CompilerException("Cannot use value type: " . $compiledExpression->getType() . " as parameter", $expression);
			}
		}

		$this->_resolvedTypes = $types;
		$this->_resolvedDynamicTypes = $dynamicTypes;
		return $params;
	}

	/**
	 * Resolve parameters using zvals in the stack and without allocating memory for constants
	 *
	 * @param array $parameters
	 * @param CompilationContext $compilationContext
	 * @param array $expression
	 * @return array
	 */
	public function getReadOnlyResolvedParams($parameters, $compilationContext, $expression)
	{

		$codePrinter = $compilationContext->codePrinter;

		$exprParams = $this->getResolvedParamsAsExpr($parameters, $compilationContext, $expression, true);

		$params = array();
		foreach ($exprParams as $compiledExpression) {
			$expression = $compiledExpression->getOriginal();
			switch ($compiledExpression->getType()) {
				case 'null':
					$params[] = 'ZEPHIR_GLOBAL(global_null)';
					break;
				case 'int':
				case 'uint':
				case 'long':
				case 'ulong':
					$parameterVariable = $compilationContext->symbolTable->getTempLocalVariableForWrite('variable', $compilationContext, $expression);
					$codePrinter->output('ZVAL_LONG(&' . $parameterVariable->getName() . ', ' . $compiledExpression->getCode() . ');');
					$this->_temporalVariables[] = $parameterVariable;
					$params[] = '&' . $parameterVariable->getName();
					break;
				case 'double':
					$parameterVariable = $compilationContext->symbolTable->getTempLocalVariableForWrite('variable', $compilationContext, $expression);
					$codePrinter->output('ZVAL_DOUBLE(&' . $parameterVariable->getName() . ', ' . $compiledExpression->getCode() . ');');
					$this->_temporalVariables[] = $parameterVariable;
					$params[] = '&' . $parameterVariable->getName();
					break;
				case 'bool':
					if ($compiledExpression->getCode() == 'true') {
						$params[] = 'ZEPHIR_GLOBAL(global_true)';
					} else {
						$params[] = 'ZEPHIR_GLOBAL(global_false)';
					}
					break;
				case 'string':
					$parameterVariable = $compilationContext->symbolTable->getTempLocalVariableForWrite('variable', $compilationContext, $expression);
					$codePrinter->output('ZVAL_STRING(&' . $parameterVariable->getName() . ', "' . $compiledExpression->getCode() . '", 0);');
					$this->_temporalVariables[] = $parameterVariable;
					$params[] = '&' . $parameterVariable->getName();
					break;
				case 'variable':
					$parameterVariable = $compilationContext->symbolTable->getVariableForRead($compiledExpression->getCode(), $compilationContext, $expression);
					switch ($parameterVariable->getType()) {
						case 'int':
						case 'uint':
						case 'long':
						case 'ulong':
							$parameterTempVariable = $compilationContext->symbolTable->getTempLocalVariableForWrite('variable', $compilationContext, $expression);
							$codePrinter->output('ZVAL_LONG(&' . $parameterTempVariable->getName() . ', ' . $compiledExpression->getCode() . ');');
							$params[] = '&' . $parameterTempVariable->getName();
							$this->_temporalVariables[] = $parameterTempVariable;
							break;
						case 'bool':
							$params[] = '(' . $parameterVariable->getName() . ' ? ZEPHIR_GLOBAL(global_true) : ZEPHIR_GLOBAL(global_false))';
							break;
						case 'string':
						case 'variable':
							$params[] = $parameterVariable->getName();
							break;
						default:
							throw new CompilerException("Cannot use variable type: " . $parameterVariable->getType() . " as parameter", $expression);
					}
					break;
				default:
					throw new CompilerException("Cannot use value type: " . $compiledExpression->getType() . " as parameter", $expression);
			}
		}

		return $params;
	}

	/**
	 * Return resolved parameter types
	 *
	 * @return array
	 */
	public function getResolvedTypes()
	{
		return $this->_resolvedTypes;
	}

	/**
	 * Return resolved parameter dynamic types
	 *
	 * @return array
	 */
	public function getResolvedDynamicTypes()
	{
		return $this->_resolvedDynamicTypes;
	}

	/**
	 * Returns the temporal variables generated during the parameter resolving
	 *
	 * @return \Variable[]
	 */
	public function getTemporalVariables()
	{
		return $this->_temporalVariables;
	}

}
