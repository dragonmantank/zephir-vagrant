
#ifdef HAVE_CONFIG_H
#include "../../ext_config.h"
#endif

#include <php.h>
#include "../../php_ext.h"
#include "../../ext.h"

#include <Zend/zend_operators.h>
#include <Zend/zend_exceptions.h>
#include <Zend/zend_interfaces.h>

#include "kernel/main.h"
#include "kernel/fcall.h"
#include "kernel/object.h"
#include "kernel/memory.h"
#include "kernel/string.h"
#include "kernel/concat.h"
#include "kernel/operators.h"
#include "kernel/array.h"
#include "kernel/exception.h"
#include "kernel/hash.h"


/**
 * Test\Router\Route
 *
 * This class represents every route added to the router
 */
ZEPHIR_INIT_CLASS(Test_Router_Route) {

	ZEPHIR_REGISTER_CLASS(Test\\Router, Route, test, router_route, test_router_route_method_entry, 0);

	zend_declare_property_null(test_router_route_ce, SL("_pattern"), ZEND_ACC_PROTECTED TSRMLS_CC);
	zend_declare_property_null(test_router_route_ce, SL("_compiledPattern"), ZEND_ACC_PROTECTED TSRMLS_CC);
	zend_declare_property_null(test_router_route_ce, SL("_paths"), ZEND_ACC_PROTECTED TSRMLS_CC);
	zend_declare_property_null(test_router_route_ce, SL("_methods"), ZEND_ACC_PROTECTED TSRMLS_CC);
	zend_declare_property_null(test_router_route_ce, SL("_hostname"), ZEND_ACC_PROTECTED TSRMLS_CC);
	zend_declare_property_null(test_router_route_ce, SL("_converters"), ZEND_ACC_PROTECTED TSRMLS_CC);
	zend_declare_property_null(test_router_route_ce, SL("_id"), ZEND_ACC_PROTECTED TSRMLS_CC);
	zend_declare_property_null(test_router_route_ce, SL("_name"), ZEND_ACC_PROTECTED TSRMLS_CC);
	zend_declare_property_null(test_router_route_ce, SL("_beforeMatch"), ZEND_ACC_PROTECTED TSRMLS_CC);

	return SUCCESS;

}

/**
 * Test\Router\Route constructor
 *
 * @param string pattern
 * @param array paths
 * @param array|string httpMethods
 */
PHP_METHOD(Test_Router_Route, __construct) {

	zval *pattern, *paths = NULL, *httpMethods = NULL;

	ZEPHIR_MM_GROW();
	zephir_fetch_params(1, 1, 2, &pattern, &paths, &httpMethods);

	if (!paths) {
		paths = ZEPHIR_GLOBAL(global_null);
	}
	if (!httpMethods) {
		httpMethods = ZEPHIR_GLOBAL(global_null);
	}


	zephir_call_method_p2_noret(this_ptr, "reconfigure", pattern, paths);
	zephir_update_property_this(this_ptr, SL("_methods"), httpMethods TSRMLS_CC);
	ZEPHIR_MM_RESTORE();

}

/**
 * Replaces placeholders from pattern returning a valid PCRE regular expression
 *
 * @param string pattern
 * @return string
 */
PHP_METHOD(Test_Router_Route, compilePattern) {

	zval *pattern = NULL, *idPattern, _0 = zval_used_for_init, *_1 = NULL, _2 = zval_used_for_init;

	ZEPHIR_MM_GROW();
	zephir_fetch_params(1, 1, 0, &pattern);

	ZEPHIR_SEPARATE_PARAM(pattern);


	if (zephir_memnstr_str(pattern, SL(":"), "test/router/route.zep", 57)) {
		ZEPHIR_INIT_VAR(idPattern);
		ZVAL_STRING(idPattern, "/([a-zA-Z0-9\\_\\-]+)", 1);
		if (zephir_memnstr_str(pattern, SL("/:module"), "test/router/route.zep", 63)) {
			ZEPHIR_SINIT_VAR(_0);
			ZVAL_STRING(&_0, "/:module", 0);
			ZEPHIR_INIT_VAR(_1);
			zephir_call_func_p3(_1, "str_replace", &_0, idPattern, pattern);
			ZEPHIR_CPY_WRT(pattern, _1);
		}
		if (zephir_memnstr_str(pattern, SL("/:controller"), "test/router/route.zep", 68)) {
			ZEPHIR_SINIT_NVAR(_0);
			ZVAL_STRING(&_0, "/:controller", 0);
			ZEPHIR_INIT_NVAR(_1);
			zephir_call_func_p3(_1, "str_replace", &_0, idPattern, pattern);
			ZEPHIR_CPY_WRT(pattern, _1);
		}
		if (zephir_memnstr_str(pattern, SL("/:namespace"), "test/router/route.zep", 73)) {
			ZEPHIR_SINIT_NVAR(_0);
			ZVAL_STRING(&_0, "/:namespace", 0);
			ZEPHIR_INIT_NVAR(_1);
			zephir_call_func_p3(_1, "str_replace", &_0, idPattern, pattern);
			ZEPHIR_CPY_WRT(pattern, _1);
		}
		if (zephir_memnstr_str(pattern, SL("/:action"), "test/router/route.zep", 78)) {
			ZEPHIR_SINIT_NVAR(_0);
			ZVAL_STRING(&_0, "/:action", 0);
			ZEPHIR_INIT_NVAR(_1);
			zephir_call_func_p3(_1, "str_replace", &_0, idPattern, pattern);
			ZEPHIR_CPY_WRT(pattern, _1);
		}
		if (zephir_memnstr_str(pattern, SL("/:params"), "test/router/route.zep", 83)) {
			ZEPHIR_SINIT_NVAR(_0);
			ZVAL_STRING(&_0, "/:params", 0);
			ZEPHIR_SINIT_VAR(_2);
			ZVAL_STRING(&_2, "(/.*)*", 0);
			ZEPHIR_INIT_NVAR(_1);
			zephir_call_func_p3(_1, "str_replace", &_0, &_2, pattern);
			ZEPHIR_CPY_WRT(pattern, _1);
		}
		if (zephir_memnstr_str(pattern, SL("/:int"), "test/router/route.zep", 88)) {
			ZEPHIR_SINIT_NVAR(_0);
			ZVAL_STRING(&_0, "/:int", 0);
			ZEPHIR_SINIT_NVAR(_2);
			ZVAL_STRING(&_2, "/([0-9]+)", 0);
			ZEPHIR_INIT_NVAR(_1);
			zephir_call_func_p3(_1, "str_replace", &_0, &_2, pattern);
			ZEPHIR_CPY_WRT(pattern, _1);
		}
	}
	if (zephir_memnstr_str(pattern, SL("("), "test/router/route.zep", 94)) {
		ZEPHIR_CONCAT_SVS(return_value, "#^", pattern, "$#");
		RETURN_MM();
	}
	if (zephir_memnstr_str(pattern, SL("["), "test/router/route.zep", 99)) {
		ZEPHIR_CONCAT_SVS(return_value, "#^", pattern, "$#");
		RETURN_MM();
	}
	RETURN_CCTOR(pattern);

}

/**
 * Set one or more HTTP methods that constraint the matching of the route
 *
 *<code>
 * $route->via('GET');
 * $route->via(array('GET', 'POST'));
 *</code>
 *
 * @param string|array httpMethods
 * @return Test\Router\Route
 */
PHP_METHOD(Test_Router_Route, via) {

	zval *httpMethods;

	zephir_fetch_params(0, 1, 0, &httpMethods);



	zephir_update_property_this(this_ptr, SL("_methods"), httpMethods TSRMLS_CC);
	RETURN_THISW();

}

/**
 * Extracts parameters from a string
 *
 * @param string pattern
 */
PHP_METHOD(Test_Router_Route, extractNamedParams) {

	zend_bool notValid;
	int tmp, cursor, cursorVar, marker, bracketCount = 0, parenthesesCount = 0, foundPattern = 0, intermediate = 0, numberMatches = 0, _0, _5, _10;
	char ch;
	zval *pattern_param = NULL, *matches, _1 = zval_used_for_init, _2 = zval_used_for_init, *_3 = NULL, *_6 = NULL, *_8 = NULL;
	zval *pattern = NULL, *route, *item = NULL, *variable = NULL, *regexp = NULL, *_4 = NULL, *_7 = NULL, *_9 = NULL;

	ZEPHIR_MM_GROW();
	zephir_fetch_params(1, 1, 0, &pattern_param);

		zephir_get_strval(pattern, pattern_param);


	if ((zephir_fast_strlen_ev(pattern) <= 0)) {
		RETURN_MM_BOOL(0);
	}
	ZEPHIR_INIT_VAR(matches);
	array_init(matches);
	ZEPHIR_INIT_VAR(route);
	ZVAL_EMPTY_STRING(route);
	for (_0 = 0; _0 < Z_STRLEN_P(pattern); _0++) {
		cursor = _0; 
		ch = Z_STRVAL_P(pattern)[_0]; 
		if ((parenthesesCount == 0)) {
			if ((ch == '{')) {
				if ((bracketCount == 0)) {
					marker = (cursor + 1);
					intermediate = 0;
					notValid = 0;
				}
				bracketCount++;
			} else {
				if ((ch == '}')) {
					bracketCount--;
					if ((intermediate > 0)) {
						if ((bracketCount == 0)) {
							numberMatches++;
							ZEPHIR_INIT_NVAR(variable);
							ZVAL_EMPTY_STRING(variable);
							ZEPHIR_INIT_NVAR(regexp);
							ZVAL_EMPTY_STRING(regexp);
							ZEPHIR_SINIT_NVAR(_1);
							ZVAL_LONG(&_1, marker);
							ZEPHIR_SINIT_NVAR(_2);
							ZVAL_LONG(&_2, (cursor - marker));
							ZEPHIR_INIT_NVAR(_3);
							zephir_call_func_p3(_3, "substr", pattern, &_1, &_2);
							zephir_get_strval(_4, _3);
							ZEPHIR_CPY_WRT(item, _4);
							for (_5 = 0; _5 < Z_STRLEN_P(item); _5++) {
								cursorVar = _5; 
								ch = Z_STRVAL_P(item)[_5]; 
								if ((ch == '\0')) {
									break;
								}
								if (((cursorVar == 0) && !(((((ch >= 'a') && (ch <= 'z'))) || (((ch >= 'A') && (ch <= 'Z'))))))) {
									notValid = 1;
									break;
								}
								if (((((((((ch >= 'a') && (ch <= 'z'))) || (((ch >= 'A') && (ch <= 'Z')))) || (((ch >= '0') && (ch <= '9')))) || (ch == '-')) || (ch == '_')) || (ch == ':'))) {
									if ((ch == ':')) {
										ZEPHIR_SINIT_NVAR(_1);
										ZVAL_LONG(&_1, 0);
										ZEPHIR_SINIT_NVAR(_2);
										ZVAL_LONG(&_2, cursorVar);
										ZEPHIR_INIT_NVAR(_6);
										zephir_call_func_p3(_6, "substr", item, &_1, &_2);
										zephir_get_strval(_7, _6);
										ZEPHIR_CPY_WRT(variable, _7);
										ZEPHIR_SINIT_NVAR(_1);
										ZVAL_LONG(&_1, (cursorVar + 1));
										ZEPHIR_INIT_NVAR(_8);
										zephir_call_func_p2(_8, "substr", item, &_1);
										zephir_get_strval(_9, _8);
										ZEPHIR_CPY_WRT(regexp, _9);
										break;
									}
								} else {
									notValid = 1;
									break;
								}
							}
							if (!(notValid)) {
								tmp = numberMatches;
								if ((variable && Z_STRLEN_P(variable)) && (regexp && Z_STRLEN_P(regexp))) {
									foundPattern = 0;
									for (_10 = 0; _10 < Z_STRLEN_P(regexp); _10++) {
										ch = Z_STRVAL_P(regexp)[_10]; 
										if ((ch == '\0')) {
											break;
										}
										if (!(foundPattern)) {
											if ((ch == '(')) {
												foundPattern = 1;
											}
										} else {
											if ((ch == ')')) {
												foundPattern = 2;
												break;
											}
										}
									}
									if ((foundPattern != 2)) {
										zephir_concat_self_str(&route, "(", sizeof("(")-1 TSRMLS_CC);
										zephir_concat_self(&route, regexp TSRMLS_CC);
										zephir_concat_self_str(&route, ")", sizeof(")")-1 TSRMLS_CC);
									} else {
										zephir_concat_self(&route, regexp TSRMLS_CC);
									}
									ZEPHIR_INIT_NVAR(_6);
									ZVAL_LONG(_6, tmp);
									zephir_array_update_zval(&matches, variable, &_6, PH_COPY | PH_SEPARATE);
								} else {
									zephir_concat_self_str(&route, "([^/]*)", sizeof("([^/]*)")-1 TSRMLS_CC);
									ZEPHIR_INIT_NVAR(_8);
									ZVAL_LONG(_8, tmp);
									zephir_array_update_zval(&matches, item, &_8, PH_COPY | PH_SEPARATE);
								}
							} else {
								zephir_concat_self_str(&route, "{", sizeof("{")-1 TSRMLS_CC);
								zephir_concat_self(&route, item TSRMLS_CC);
								zephir_concat_self_str(&route, "}", sizeof("}")-1 TSRMLS_CC);
							}
							continue;
						}
					}
				}
			}
		}
		if ((bracketCount == 0)) {
			if ((ch == '(')) {
				parenthesesCount++;
			} else {
				if ((ch == ')')) {
					parenthesesCount--;
					if ((parenthesesCount == 0)) {
						numberMatches++;
					}
				}
			}
		}
		if ((bracketCount > 0)) {
			intermediate++;
		} else {
			zephir_concat_self_char(&route, ch TSRMLS_CC);
		}
	}
	array_init(return_value);
	zephir_array_fast_append(return_value, route);
	zephir_array_fast_append(return_value, matches);
	RETURN_MM();

}

/**
 * Reconfigure the route adding a new pattern and a set of paths
 *
 * @param string pattern
 * @param array paths
 */
PHP_METHOD(Test_Router_Route, reConfigure) {

	int _1;
	zval *pattern, *paths = NULL, *moduleName = NULL, *controllerName = NULL, *actionName = NULL, *parts, *routePaths = NULL, *realClassName = NULL, *namespaceName, *pcrePattern = NULL, *compiledPattern = NULL, *extracted, _0, *_2 = NULL, *_3;

	ZEPHIR_MM_GROW();
	zephir_fetch_params(1, 1, 1, &pattern, &paths);

	if (!paths) {
		paths = ZEPHIR_GLOBAL(global_null);
	}


	if ((Z_TYPE_P(pattern) != IS_STRING)) {
		ZEPHIR_THROW_EXCEPTION_STR(test_router_exception_ce, "The pattern must be string");
		return;
	}
	if ((Z_TYPE_P(paths) != IS_NULL)) {
		if ((Z_TYPE_P(paths) == IS_STRING)) {
			ZEPHIR_INIT_VAR(moduleName);
			ZVAL_NULL(moduleName);
			ZEPHIR_INIT_VAR(controllerName);
			ZVAL_NULL(controllerName);
			ZEPHIR_INIT_VAR(actionName);
			ZVAL_NULL(actionName);
			ZEPHIR_SINIT_VAR(_0);
			ZVAL_STRING(&_0, "::", 0);
			ZEPHIR_INIT_VAR(parts);
			zephir_call_func_p2(parts, "explode", &_0, paths);
			do {
				_1 = zephir_fast_count_int(parts TSRMLS_CC);
				if ((_1 == 3)) {
					ZEPHIR_OBS_NVAR(moduleName);
					zephir_array_fetch_long(&moduleName, parts, 0, PH_NOISY TSRMLS_CC);
					ZEPHIR_OBS_NVAR(controllerName);
					zephir_array_fetch_long(&controllerName, parts, 1, PH_NOISY TSRMLS_CC);
					ZEPHIR_OBS_NVAR(actionName);
					zephir_array_fetch_long(&actionName, parts, 2, PH_NOISY TSRMLS_CC);
					break;
				}
				if ((_1 == 2)) {
					ZEPHIR_OBS_NVAR(controllerName);
					zephir_array_fetch_long(&controllerName, parts, 0, PH_NOISY TSRMLS_CC);
					ZEPHIR_OBS_NVAR(actionName);
					zephir_array_fetch_long(&actionName, parts, 1, PH_NOISY TSRMLS_CC);
					break;
				}
				if ((_1 == 1)) {
					ZEPHIR_OBS_NVAR(controllerName);
					zephir_array_fetch_long(&controllerName, parts, 0, PH_NOISY TSRMLS_CC);
					break;
				}
			} while(0);

			ZEPHIR_INIT_VAR(routePaths);
			array_init(routePaths);
			if ((Z_TYPE_P(moduleName) != IS_NULL)) {
				zephir_array_update_string(&routePaths, SL("module"), &moduleName, PH_COPY | PH_SEPARATE);
			}
			if ((Z_TYPE_P(controllerName) != IS_NULL)) {
				if (zephir_memnstr_str(controllerName, SL("\\"), "test/router/route.zep", 313)) {
					ZEPHIR_INIT_VAR(realClassName);
					zephir_call_func_p1(realClassName, "get_class_ns", controllerName);
					ZEPHIR_INIT_VAR(namespaceName);
					zephir_call_func_p1(namespaceName, "get_ns_class", controllerName);
					if (zephir_is_true(namespaceName)) {
						zephir_array_update_string(&routePaths, SL("namespace"), &namespaceName, PH_COPY | PH_SEPARATE);
					}
				} else {
					ZEPHIR_CPY_WRT(realClassName, controllerName);
				}
				ZEPHIR_INIT_VAR(_2);
				zephir_uncamelize(_2, realClassName);
				zephir_array_update_string(&routePaths, SL("controller"), &_2, PH_COPY | PH_SEPARATE);
			}
			if ((Z_TYPE_P(actionName) != IS_NULL)) {
				zephir_array_update_string(&routePaths, SL("action"), &actionName, PH_COPY | PH_SEPARATE);
			}
		} else {
			ZEPHIR_CPY_WRT(routePaths, paths);
		}
	} else {
		ZEPHIR_INIT_NVAR(routePaths);
		array_init(routePaths);
	}
	if ((Z_TYPE_P(routePaths) != IS_ARRAY)) {
		ZEPHIR_THROW_EXCEPTION_STR(test_router_exception_ce, "The route contains invalid paths");
		return;
	}
	if (!(zephir_start_with_str(pattern, SL("#")))) {
		if (zephir_memnstr_str(pattern, SL("{"), "test/router/route.zep", 351)) {
			ZEPHIR_INIT_VAR(extracted);
			zephir_call_method_p1(extracted, this_ptr, "extractnamedparams", pattern);
			ZEPHIR_OBS_VAR(pcrePattern);
			zephir_array_fetch_long(&pcrePattern, extracted, 0, PH_NOISY TSRMLS_CC);
			ZEPHIR_INIT_NVAR(_2);
			zephir_array_fetch_long(&_3, extracted, 1, PH_NOISY | PH_READONLY TSRMLS_CC);
			zephir_fast_array_merge(_2, &(routePaths), &(_3) TSRMLS_CC);
			ZEPHIR_CPY_WRT(routePaths, _2);
		} else {
			ZEPHIR_CPY_WRT(pcrePattern, pattern);
		}
		ZEPHIR_INIT_VAR(compiledPattern);
		zephir_call_method_p1(compiledPattern, this_ptr, "compilepattern", pcrePattern);
	} else {
		ZEPHIR_CPY_WRT(compiledPattern, pattern);
	}
	zephir_update_property_this(this_ptr, SL("_pattern"), pattern TSRMLS_CC);
	zephir_update_property_this(this_ptr, SL("_compiledPattern"), compiledPattern TSRMLS_CC);
	zephir_update_property_this(this_ptr, SL("_paths"), routePaths TSRMLS_CC);
	ZEPHIR_MM_RESTORE();

}

/**
 * Returns the route's name
 *
 * @return string
 */
PHP_METHOD(Test_Router_Route, getName) {


	RETURN_MEMBER(this_ptr, "_name");

}

/**
 * Sets the route's name
 *
 *<code>
 * $router->add('/about', array(
 *     'controller' => 'about'
 * ))->setName('about');
 *</code>
 *
 * @param string name
 * @return Test\Router\Route
 */
PHP_METHOD(Test_Router_Route, setName) {

	zval *name;

	zephir_fetch_params(0, 1, 0, &name);



	zephir_update_property_this(this_ptr, SL("_name"), name TSRMLS_CC);
	RETURN_THISW();

}

/**
 * Sets a callback that is called if the route is matched.
 * The developer can implement any arbitrary conditions here
 * If the callback returns false the route is treaded as not matched
 *
 * @param callback callback
 * @return Test\Router\Route
 */
PHP_METHOD(Test_Router_Route, beforeMatch) {

	zval *callback;

	zephir_fetch_params(0, 1, 0, &callback);



	zephir_update_property_this(this_ptr, SL("_beforeMatch"), callback TSRMLS_CC);
	RETURN_THISW();

}

/**
 * Returns the 'before match' callback if any
 *
 * @return mixed
 */
PHP_METHOD(Test_Router_Route, getBeforeMatch) {


	RETURN_MEMBER(this_ptr, "_beforeMatch");

}

/**
 * Returns the route's id
 *
 * @return string
 */
PHP_METHOD(Test_Router_Route, getRouteId) {


	RETURN_MEMBER(this_ptr, "_id");

}

/**
 * Returns the route's pattern
 *
 * @return string
 */
PHP_METHOD(Test_Router_Route, getPattern) {


	RETURN_MEMBER(this_ptr, "_pattern");

}

/**
 * Returns the route's compiled pattern
 *
 * @return string
 */
PHP_METHOD(Test_Router_Route, getCompiledPattern) {


	RETURN_MEMBER(this_ptr, "_compiledPattern");

}

/**
 * Returns the paths
 *
 * @return array
 */
PHP_METHOD(Test_Router_Route, getPaths) {


	RETURN_MEMBER(this_ptr, "_paths");

}

/**
 * Returns the paths using positions as keys and names as values
 *
 * @return array
 */
PHP_METHOD(Test_Router_Route, getReversedPaths) {

	HashTable *_2;
	HashPosition _1;
	zval *reversed, *path = NULL, *position = NULL, *_0, **_3;

	ZEPHIR_MM_GROW();

	ZEPHIR_INIT_VAR(reversed);
	array_init(reversed);
	_0 = zephir_fetch_nproperty_this(this_ptr, SL("_paths"), PH_NOISY_CC);
	zephir_is_iterable(_0, &_2, &_1, 0, 0);
	for (
		; zend_hash_get_current_data_ex(_2, (void**) &_3, &_1) == SUCCESS
		; zend_hash_move_forward_ex(_2, &_1)
	) {
		ZEPHIR_GET_HMKEY(path, _2, _1);
		ZEPHIR_GET_HVALUE(position, _3);
		zephir_array_update_zval(&reversed, position, &path, PH_COPY | PH_SEPARATE);
	}
	RETURN_CCTOR(reversed);

}

/**
 * Sets a set of HTTP methods that constraint the matching of the route (alias of via)
 *
 *<code>
 * $route->setHttpMethods('GET');
 * $route->setHttpMethods(array('GET', 'POST'));
 *</code>
 *
 * @param string|array httpMethods
 * @return Test\Router\Route
 */
PHP_METHOD(Test_Router_Route, setHttpMethods) {

	zval *httpMethods;

	zephir_fetch_params(0, 1, 0, &httpMethods);



	zephir_update_property_this(this_ptr, SL("_methods"), httpMethods TSRMLS_CC);
	RETURN_THISW();

}

/**
 * Returns the HTTP methods that constraint matching the route
 *
 * @return string|array
 */
PHP_METHOD(Test_Router_Route, getHttpMethods) {


	RETURN_MEMBER(this_ptr, "_methods");

}

/**
 * Sets a hostname restriction to the route
 *
 *<code>
 * $route->setHostname('localhost');
 *</code>
 *
 * @param string|array httpMethods
 * @return Test\Router\Route
 */
PHP_METHOD(Test_Router_Route, setHostname) {

	zval *hostname;

	zephir_fetch_params(0, 1, 0, &hostname);



	zephir_update_property_this(this_ptr, SL("_hostname"), hostname TSRMLS_CC);
	RETURN_THISW();

}

/**
 * Returns the hostname restriction if any
 *
 * @return string
 */
PHP_METHOD(Test_Router_Route, getHostname) {


	RETURN_MEMBER(this_ptr, "_hostname");

}

/**
 * Adds a converter to perform an additional transformation for certain parameter
 *
 * @param string name
 * @param callable converter
 * @return Test\Router\Route
 */
PHP_METHOD(Test_Router_Route, convert) {

	zval *name, *converter;

	zephir_fetch_params(0, 2, 0, &name, &converter);



	zephir_update_property_array(this_ptr, SL("_converters"), name, converter TSRMLS_CC);
	RETURN_THISW();

}

/**
 * Returns the router converter
 *
 * @return array
 */
PHP_METHOD(Test_Router_Route, getConverters) {


	RETURN_MEMBER(this_ptr, "_converters");

}

