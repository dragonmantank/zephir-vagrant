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
 * FunctionCall
 *
 * Call functions. By default functions are called in the PHP userland if an optimizer
 * was not found or there is not a user-handler for it
 */
class FunctionCall extends Call
{

	/**
	 * Function is called using a normal method name
	 */
	const CALL_NORMAL = 1;

	/**
	 * Function is called using a dynamic variable as method name
	 */
	const CALL_DYNAMIC = 2;

	/**
	 * Function is called using a dynamic string as method name
	 */
	const CALL_DYNAMIC_STRING = 3;

	static protected $_optimizers = array();

	static protected $_functionReflection = array();

	/**
	 * Process the ReflectionFunction for the specified function name
	 *
	 * @param string $funcName
	 */
	protected function getReflector($funcName)
	{
		/**
		 * Check if the optimizer is already cached
		 */
		if (!isset(self::$_functionReflection[$funcName])) {
			try {
				$reflectionFunction = new ReflectionFunction($funcName);
			} catch (Exception $e) {
				$reflectionFunction = null;
			}
			self::$_functionReflection[$funcName] = $reflectionFunction;
			return $reflectionFunction;
		}
		return self::$_functionReflection[$funcName];
	}

	/**
	 * This method gets the reflection of a function
	 * to check if any of their parameters are passed by reference
	 * Built-in functions rarely change the parameters if they aren't passed by reference
	 *
	 * @param string $funcName
	 * @param array $expression
	 * @return boolean
	 */
	protected function isReadOnly($funcName, $expression)
	{
		if ($this->isBuiltInFunction($funcName)) {
			return false;
		}

		$reflector = $this->getReflector($funcName);
		if ($reflector) {

			if (isset($expression['parameters'])) {

				/**
				 * Check if the number of parameters
				 */
				$numberParameters = count($expression['parameters']);
				if ($numberParameters < $reflector->getNumberOfRequiredParameters()) {
					throw new CompilerException("The number of parameters passed is less than the number of requiered parameters by '" . $funcName . "'", $expression);
				}
			} else {
				$numberParameters = 0;
				if ($reflector->getNumberOfRequiredParameters() > 0) {
					throw new CompilerException("The number of parameters passed is less than the number of requiered parameters by '" . $funcName . "'", $expression);
				}
			}

			if ($numberParameters > 0) {
				$n = 1;
				$parameters = $reflector->getParameters();
				foreach ($parameters as $parameter) {
					if ($numberParameters >= $n) {
						if ($parameter->isPassedByReference()) {
							return false;
						}
					}
					$n++;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Once the function processes the parameters we should mark
	 * specific parameters to be passed by reference
	 *
	 * @param string $funcName
	 * @param array $expression
	 * @param array $references
	 * @return boolean
	 */
	protected function markReferences($funcName, $parameters, CompilationContext $compilationContext, &$references)
	{
		if ($this->isBuiltInFunction($funcName)) {
			return false;
		}

		$reflector = $this->getReflector($funcName);
		if ($reflector) {
			$numberParameters = count($parameters);
			if ($numberParameters > 0) {
				$n = 1;
				$funcParameters = $reflector->getParameters();
				foreach ($funcParameters as $parameter) {
					if ($numberParameters >= $n) {
						if ($parameter->isPassedByReference()) {
							$variable = $compilationContext->symbolTable->getVariable($parameters[$n - 1]);
							$variable->setDynamicTypes('undefined');
							$compilationContext->codePrinter->output('Z_SET_ISREF_P(' . $parameters[$n - 1] . ');');
							$references[] = $parameters[$n - 1] ;
							return false;
						}
					}
					$n++;
				}
			}
		}
	}

	/**
	 * Tries to find specific an specialized optimizer for function calls
	 *
	 * @param string $funcName
	 * @param array $expression
	 */
	protected function optimize($funcName, array $expression, Call $call, CompilationContext $compilationContext)
	{
		/**
		 * Check if the optimizer is already cached
		 */
		if (!isset(self::$_optimizers[$funcName])) {

			$camelizeFunctionName = Utils::camelize($funcName);

			$path = ZEPHIRPATH . 'Library/Optimizers/FunctionCall/' . $camelizeFunctionName . 'Optimizer.php';
			if (file_exists($path)) {
				require $path;
				$className = $camelizeFunctionName . 'Optimizer';
				$optimizer = new $className();
			} else {
				$optimizer = null;
			}

			self::$_optimizers[$funcName] = $optimizer;

		} else {
			$optimizer = self::$_optimizers[$funcName];
		}

		if ($optimizer) {
			return $optimizer->optimize($expression, $call, $compilationContext);
		}

		return false;
	}

	/**
	 * Checks if the function is a built-in provided by Zephir
	 *
	 * @param string $functionName
	 */
	public function isBuiltInFunction($functionName)
	{
		switch ($functionName) {
			case 'memstr':
			case 'get_class_ns':
			case 'get_ns_class':
			case 'camelize':
			case 'uncamelize':
			case 'starts_with':
			case 'ends_with':
			case 'prepare_virtual_path':
			case 'create_instance':
			case 'create_instance_params':
			case 'create_symbol_table':
			case 'globals_get':
			case 'globals_set':
			case 'merge_append':
			case 'get_class_lower':
				return true;
		}
		return false;
	}

	/**
	 * Checks if a function exists or is a built-in Zephir function
	 *
	 * @param string $functionName
	 */
	public function functionExists($functionName)
	{
		if (function_exists($functionName)) {
			return true;
		}
		if ($this->isBuiltInFunction($functionName)) {
			return true;
		}
		return false;
	}

	/**
	 * @param array $expression
	 * @param CompilationContext $compilationContext
	 */
	protected function _callNormal($expression, $compilationContext)
	{

		$funcName = strtolower($expression['name']);

		if ($funcName == 'array') {
			throw new CompilerException("Cannot use 'array' as a function call", $expression);
		}

		$exists = true;
		if (!$this->functionExists($funcName)) {
			$compilationContext->logger->warning("Function \"$funcName\" does not exist at compile time", "nonexistent-function", $expression);
			$exists = false;
		}

		/**
		 * Try to optimize function calls
		 */
		$compiledExpr = $this->optimize($funcName, $expression, $this, $compilationContext);
		if (is_object($compiledExpr)) {
			return $compiledExpr;
		}

		/**
		 * Static variables can be passed using local variables saving memory if
		 * the function is read only
		 */
		if ($exists) {
			$readOnly = $this->isReadOnly($funcName, $expression);
		} else {
			$readOnly = false;
		}

		/**
		 * Resolve parameters
		 */
		if (isset($expression['parameters'])) {
			if ($readOnly) {
				$params = $this->getReadOnlyResolvedParams($expression['parameters'], $compilationContext, $expression);
			} else {
				$params = $this->getResolvedParams($expression['parameters'], $compilationContext, $expression);
			}
		} else {
			$params = array();
		}

		/**
		 * Some functions receive parameters as references
		 * We mark those parameters temporary as references to properly pass them
		 */
		$this->markReferences($funcName, $params, $compilationContext, $references, $expression);
		//if (count($references)) {
			//var_dump($references);
		//}

		$codePrinter = $compilationContext->codePrinter;

		/**
		 * Process the expected symbol to be returned
		 */
		$this->processExpectedReturn($compilationContext);

		/**
		 * At this point the function will be called in the PHP userland.
		 * PHP functions only return zvals so we need to validate the target variable is also a zval
		 */
		$symbolVariable = $this->getSymbolVariable();
		if ($symbolVariable) {

			if ($symbolVariable->getType() != 'variable') {
				throw new CompilerException("Returned values by functions can only be assigned to variant variables", $expression);
			}

			/**
			 * We don't know the exact dynamic type returned by the method call
			 */
			$symbolVariable->setDynamicTypes('undefined');
		}

		/**
		 * Include fcall header
		 */
		$compilationContext->headersManager->add('kernel/fcall');

		/**
		 * Call functions must grown the stack
		 */
		$compilationContext->symbolTable->mustGrownStack(true);

		if (!isset($expression['parameters'])) {
			if ($this->isExpectingReturn()) {
				if ($this->mustInitSymbolVariable()) {
					$symbolVariable->initVariant($compilationContext);
				}
				$codePrinter->output('zephir_call_func(' . $symbolVariable->getName() . ', "' . $funcName . '");');
			} else {
				$codePrinter->output('zephir_call_func_noret("' . $funcName . '");');
			}
		} else {
			if (count($params)) {
				if ($this->isExpectingReturn()) {
					if ($this->mustInitSymbolVariable()) {
						$symbolVariable->initVariant($compilationContext);
					}
					$codePrinter->output('zephir_call_func_p' . count($params) . '(' . $symbolVariable->getName() . ', "' . $funcName . '", ' . join(', ', $params) . ');');
				} else {
					$codePrinter->output('zephir_call_func_p' . count($params) . '_noret("' . $funcName . '", ' . join(', ', $params) . ');');
				}
			} else {
				$codePrinter->output('zephir_call_func_noret("' . $funcName . '");');
			}
		}

		/**
		 * We can mark temporary variables generated as idle
		 */
		foreach ($this->getTemporalVariables() as $tempVariable) {
			$tempVariable->setIdle(true);
		}

		if ($this->isExpectingReturn()) {
			return new CompiledExpression('variable', $symbolVariable->getRealName(), $expression);
		}

		return new CompiledExpression('null', null, $expression);
	}

	/**
	 *
	 * @param array $expression
	 * @param CompilationContext $compilationContext
	 */
	protected function _callDynamic($expression, $compilationContext)
	{

		$variable = $compilationContext->symbolTable->getVariableForRead($expression['name'], $compilationContext, $expression);
		switch ($variable->getType()) {
			case 'variable':
				break;
			default:
				throw new CompiledException("Variable type: " . $variable->getType() . " cannot be used as dynamic caller", $propertyAccess['left']);
		}

		/**
		 * Resolve parameters
		 */
		if (isset($expression['parameters'])) {
			$params = $this->getResolvedParams($expression['parameters'], $compilationContext, $expression);
		} else {
			$params = array();
		}

		$codePrinter = $compilationContext->codePrinter;

		/**
		 * Process the expected symbol to be returned
		 */
		$this->processExpectedReturn($compilationContext);

		/**
		 * At this point the function will be called in the PHP userland.
		 * PHP functions only return zvals so we need to validate the target variable is also a zval
		 */
		$symbolVariable = $this->getSymbolVariable();
		if ($symbolVariable) {

			if ($symbolVariable->getType() != 'variable') {
				throw new CompilerException("Returned values by functions can only be assigned to variant variables", $expression);
			}

			/**
			 * We don't know the exact dynamic type returned by the method call
			 */
			$symbolVariable->setDynamicTypes('undefined');
		}

		/**
		 * Include fcall header
		 */
		$compilationContext->headersManager->add('kernel/fcall');

		/**
		 * Call functions must grown the stack
		 */
		$compilationContext->symbolTable->mustGrownStack(true);

        /**
         * Find last value of variable
         */
        $methodStatements = $compilationContext->currentMethod->getStatementsBlock()->getStatements();
        $varName          = $expression['name'];
        $lastValue        = null;
        foreach ($methodStatements as $statement) {

            if (isset($statement['line'])
                && $statement['line'] <= $expression['line']
                && isset($statement['type'])
                && $statement['type'] === 'let'
                && isset($statement['assignments'])
                && is_array($statement['assignments'])
            ) {

                foreach ($statement['assignments'] as $assignment) {

                    if (isset($assignment['variable'])
                        && $assignment['variable'] === $varName
                        && isset($assignment['expr']['value'])
                    ) {
                        $lastValue = $assignment['expr']['value'];
                    }
                }
            }
        }

        if ($lastValue) {

            $funcName = $lastValue;

            if (!isset($expression['parameters'])) {
                if ($this->isExpectingReturn()) {
                    if ($this->mustInitSymbolVariable()) {
                        $symbolVariable->initVariant($compilationContext);
                    }
                    $codePrinter->output('zephir_call_func(' . $symbolVariable->getName() . ', "' . $funcName . '");');
                } else {
                    $codePrinter->output('zephir_call_func_noret("' . $funcName . '");');
                }
            } else {
                if (count($params)) {
                    if ($this->isExpectingReturn()) {
                        if ($this->mustInitSymbolVariable()) {
                            $symbolVariable->initVariant($compilationContext);
                        }
                        $codePrinter->output('zephir_call_func_p' . count($params) . '(' . $symbolVariable->getName() . ', "' . $funcName . '", ' . join(', ', $params) . ');');
                    } else {
                        $codePrinter->output('zephir_call_func_p' . count($params) . '_noret("' . $funcName . '", ' . join(', ', $params) . ');');
                    }
                } else {
                    $codePrinter->output('zephir_call_func_noret("' . $funcName . '");');
                }
            }
        }

		/**
		 * We can mark temporary variables generated as idle
		 */
		foreach ($this->getTemporalVariables() as $tempVariable) {
			$tempVariable->setIdle(true);
		}

		if ($this->isExpectingReturn()) {
			return new CompiledExpression('variable', $symbolVariable->getRealName(), $expression);
		}

		return new CompiledExpression('null', null, $expression);
	}

	/**
	 * Compiles a function
	 *
	 * @param Expression $expr
	 * @param CompilationContext $expr
	 */
	public function compile(Expression $expr, CompilationContext $compilationContext)
	{

		$this->_expression = $expr;
		$expression = $expr->getExpression();

		switch ($expression['call-type']) {
			case self::CALL_NORMAL:
				return $this->_callNormal($expression, $compilationContext);
			case self::CALL_DYNAMIC:
				return $this->_callDynamic($expression, $compilationContext);
		}

		return new CompiledExpression('null', null, $expression);
	}

}
