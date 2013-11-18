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
 * StaticPropertyAccess
 *
 * Resolves class static properties
 */
class StaticPropertyAccess
{

	protected $_expecting = true;

	protected $_readOnly = false;

	protected $_expectingVariable;

	/**
	 * Sets if the variable must be resolved into a direct variable symbol
	 * create a temporary value or ignore the return value
	 *
	 * @param boolean $expecting
	 * @param Variable $expectingVariable
	 */
	public function setExpectReturn($expecting, Variable $expectingVariable=null)
	{
		$this->_expecting = $expecting;
		$this->_expectingVariable = $expectingVariable;
	}

	/**
	 * Sets if the result of the evaluated expression is read only
	 *
	 * @param boolean $readOnly
	 */
	public function setReadOnly($readOnly)
	{
		$this->_readOnly = $readOnly;
	}

	/**
	 * Access a static property
	 *
	 * @param array $expression
	 * @param CompilationContext $compilationContext
	 * @return \CompiledExpression
	 */
	public function compile($expression, CompilationContext $compilationContext)
	{
		$compiler = $compilationContext->compiler;

		$className = $expression['left']['value'];
		if (substr($className, 0, 1) == '\\') {
			$className = substr($className, 1);
		}

		$property = $expression['right']['value'];

		/**
		 * Fetch the class definition according to the class where the constant
		 * is supposed to be declared
		 */
		if ($className != 'self' && $className != 'parent') {
			if ($compiler->isClass($className)) {
				$classDefinition = $compiler->getClassDefinition($className);
			} else {
				if ($compiler->isInternalClass($className)) {
					$classDefinition = $compiler->getInternalClassDefinition($className);
				} else {
					throw new CompilerException("Cannot locate class '" . $className . "'", $expression['left']);
				}
			}
		} else {
			if ($className == 'self') {
				$classDefinition = $compilationContext->classDefinition;
			} else {
				if ($className == 'parent') {
					$classDefinition = $compilationContext->classDefinition;
					$extendsClass = $classDefinition->getExtendsClass();
					if (!$extendsClass) {
						throw new CompilerException('Cannot access static property "' . $property . '" on parent because class ' .
							$classDefinition->getCompleteName() . ' does not extend any class', $expression);
					} else {
						$classDefinition = $classDefinition->getExtendsClassDefinition();
					}
				}
			}
		}

		if (!$classDefinition->hasProperty($property)) {
			throw new CompilerException("Class '" . $classDefinition->getCompleteName() . "' does not have a property called: '" . $property . "'", $expression);
		}

		/** @var $propertyDefinition ClassProperty */
		$propertyDefinition = $classDefinition->getProperty($property);
		if (!$propertyDefinition->isStatic()) {
			throw new CompilerException("Cannot access non-static property '" . $classDefinition->getCompleteName() . '::' . $property . "'", $expression);
		}

		if ($propertyDefinition->isPrivate()) {
			if ($classDefinition != $compilationContext->classDefinition) {
				throw new CompilerException("Cannot access private static property '" . $classDefinition->getCompleteName() . '::' . $property . "' out of its declaring context", $expression);
			}
		}

		if ($propertyDefinition->isProtected()) {
			if ($classDefinition != $compilationContext->classDefinition && $classDefinition != $compilationContext->classDefinition->getExtendsClassDefinition()) {
				throw new CompilerException("Cannot access protected static property '" . $classDefinition->getCompleteName() . '::' . $property . "' out of its declaring context", $expression);
			}
		}

		/**
		 * Resolves the symbol that expects the value
		 */
		if ($this->_expecting) {
			if ($this->_expectingVariable) {
				$symbolVariable = $this->_expectingVariable;
				if ($symbolVariable->getName() != 'return_value') {
					//$symbolVariable->initVariant($compilationContext);
				} else {
					$symbolVariable = $compilationContext->symbolTable->getTempNonTrackedVariable('variable', $compilationContext, $expression);
				}
			} else {
				$symbolVariable = $compilationContext->symbolTable->getTempNonTrackedVariable('variable', $compilationContext, $expression);
			}
		} else {
			$symbolVariable = $compilationContext->symbolTable->getTempNonTrackedVariable('variable', $compilationContext, $expression);
		}

		/**
		 * Variable that receives property accesses must be polimorphic
		 */
		if ($symbolVariable->getType() != 'variable') {
			throw new CompilerException("Cannot use variable: " . $symbolVariable->getType() . " to assign class constants", $expression);
		}

		$symbolVariable->setDynamicTypes('undefined');

		$compilationContext->headersManager->add('kernel/object');

		if ($classDefinition == $compilationContext->classDefinition) {
			if ($this->_readOnly) {
				$compilationContext->codePrinter->output($symbolVariable->getName() . ' = zephir_fetch_static_property_ce(' . $classDefinition->getClassEntry() . ', SL("' . $property . '") TSRMLS_CC);');
			} else {
				$compilationContext->codePrinter->output('zephir_read_static_property_ce(&' . $symbolVariable->getName() . ', ' . $classDefinition->getClassEntry() . ', SL("' . $property . '") TSRMLS_CC);');
			}
		} else {
			$compilationContext->codePrinter->output('zephir_read_static_property_ce(&' . $symbolVariable->getName() . ', ' . $classDefinition->getClassEntry() . ', SL("' . $property . '") TSRMLS_CC);');
		}

		return new CompiledExpression('variable', $symbolVariable->getRealName(), $expression);
	}

}
