<?php
/**
 * @package    Joomla.Test
 *
 * @copyright  (C) 2026 Niel Buys. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Base test case for the unit test suite.
 *
 * The suite was written for PHPUnit 4.8. This class puts back the PHPUnit 4.8 methods that later versions removed
 * and the tests still call, implemented with the current PHPUnit API, so the tests run unchanged on PHPUnit 9.
 * New tests should use the current API instead.
 *
 * @package  Joomla.Test
 * @since    3.12.0
 */
abstract class TestCasePhpunit extends \PHPUnit\Framework\TestCase
{
	/**
	 * @var    array  $_SERVER as it was before the test.
	 * @since  3.12.0
	 */
	private $serverSuperglobal;

	/**
	 * Saves $_SERVER before setUp() runs.
	 *
	 * @return  void
	 *
	 * @before
	 * @since   3.12.0
	 */
	protected function saveServerSuperglobal()
	{
		$this->serverSuperglobal = $_SERVER;
	}

	/**
	 * Restores $_SERVER after tearDown() has run.
	 *
	 * Many tests restore $_SERVER from a copy taken part way through setUp(). When setUp() fails or skips the test
	 * before taking that copy, tearDown() sets $_SERVER to null, which PHPUnit's backupGlobals does not undo, and later
	 * tests that need $_SERVER['argv'] then stop the whole run.
	 *
	 * @return  void
	 *
	 * @after
	 * @since   3.12.0
	 */
	protected function restoreServerSuperglobal()
	{
		$_SERVER = $this->serverSuperglobal;
	}

	/**
	 * Returns a mock object for the specified class.
	 *
	 * @param   string      $originalClassName        Name of the class to mock.
	 * @param   array|null  $methods                  The methods to mock: an empty array mocks all methods, null none.
	 * @param   array       $arguments                Arguments for the original constructor.
	 * @param   string      $mockClassName            Class name for the generated mock class.
	 * @param   boolean     $callOriginalConstructor  Whether to call the original constructor.
	 * @param   boolean     $callOriginalClone        Whether to call the original clone method.
	 * @param   boolean     $callAutoload             Whether to use the autoloader for the original class.
	 * @param   boolean     $cloneArguments           Whether to clone the arguments passed to mocked methods.
	 * @param   boolean     $callOriginalMethods      Whether to proxy calls to the original methods.
	 * @param   object      $proxyTarget              The object calls are proxied to.
	 *
	 * @return  \PHPUnit\Framework\MockObject\MockObject
	 *
	 * @since   3.12.0
	 */
	public function getMock($originalClassName, $methods = array(), array $arguments = array(), $mockClassName = '',
		$callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true, $cloneArguments = false,
		$callOriginalMethods = false, $proxyTarget = null)
	{
		$builder = $this->getMockBuilder($originalClassName)
			->setMethods($methods)
			->setConstructorArgs($arguments)
			->setMockClassName($mockClassName);

		if (!$callOriginalConstructor)
		{
			$builder->disableOriginalConstructor();
		}

		if (!$callOriginalClone)
		{
			$builder->disableOriginalClone();
		}

		if (!$callAutoload)
		{
			$builder->disableAutoload();
		}

		if ($cloneArguments)
		{
			$builder->enableArgumentCloning();
		}

		if ($callOriginalMethods)
		{
			$builder->enableProxyingToOriginalMethods();
		}

		if ($proxyTarget !== null)
		{
			$builder->setProxyTarget($proxyTarget);
		}

		return $builder->getMock();
	}

	/**
	 * Sets up an expectation for an exception to be raised by the code under test.
	 *
	 * @param   string          $exception  The exception class.
	 * @param   string          $message    A string the exception message must contain.
	 * @param   integer|string  $code       The exception code.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public function setExpectedException($exception, $message = '', $code = null)
	{
		$this->expectException($exception);

		if ($message !== null && $message !== '')
		{
			$this->expectExceptionMessage($message);
		}

		if ($code !== null)
		{
			$this->expectExceptionCode($code);
		}
	}

	/**
	 * Asserts that a variable is of a given PHP type.
	 *
	 * @param   string  $expected  The type name, as accepted by PHPUnit 4.8.
	 * @param   mixed   $actual    The variable to check.
	 * @param   string  $message   The failure message.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public static function assertInternalType($expected, $actual, $message = '')
	{
		$types = array(
			'array' => 'array', 'bool' => 'bool', 'boolean' => 'bool', 'callable' => 'callable', 'double' => 'float',
			'float' => 'float', 'int' => 'int', 'integer' => 'int', 'iterable' => 'iterable', 'null' => 'null',
			'numeric' => 'numeric', 'object' => 'object', 'real' => 'float', 'resource' => 'resource',
			'scalar' => 'scalar', 'string' => 'string',
		);

		static::assertThat($actual, new \PHPUnit\Framework\Constraint\IsType($types[strtolower($expected)]), $message);
	}

	/**
	 * Asserts that HTML or XML contains a tag matching the given criteria.
	 *
	 * @param   array    $matcher  The criteria; see TestPhpunitTag::findNodes().
	 * @param   string   $actual   The HTML or XML.
	 * @param   string   $message  The failure message.
	 * @param   boolean  $isHtml   Whether $actual is HTML.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public static function assertTag($matcher, $actual, $message = '', $isHtml = true)
	{
		$tags = TestPhpunitTag::findNodes(TestPhpunitTag::load($actual, $isHtml), $matcher, $isHtml);

		static::assertTrue(count($tags) > 0 && $tags[0] instanceof DOMNode, $message);
	}

	/**
	 * Asserts that HTML or XML contains no tag matching the given criteria.
	 *
	 * @param   array    $matcher  The criteria; see TestPhpunitTag::findNodes().
	 * @param   string   $actual   The HTML or XML.
	 * @param   string   $message  The failure message.
	 * @param   boolean  $isHtml   Whether $actual is HTML.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public static function assertNotTag($matcher, $actual, $message = '', $isHtml = true)
	{
		$tags = TestPhpunitTag::findNodes(TestPhpunitTag::load($actual, $isHtml), $matcher, $isHtml);

		static::assertFalse(count($tags) > 0 && $tags[0] instanceof DOMNode, $message);
	}

	/**
	 * Returns the value of an object's attribute, or a class's static attribute, whatever its visibility.
	 *
	 * @param   object|string  $classOrObject  The object, or the class name for a static attribute.
	 * @param   string         $attributeName  The attribute name.
	 *
	 * @return  mixed
	 *
	 * @since   3.12.0
	 */
	public static function readAttribute($classOrObject, $attributeName)
	{
		$class = new ReflectionClass($classOrObject);

		while ($class && !$class->hasProperty($attributeName))
		{
			$class = $class->getParentClass();
		}

		if (!$class)
		{
			throw new \PHPUnit\Framework\Exception(sprintf('Attribute "%s" not found.', $attributeName));
		}

		$property = $class->getProperty($attributeName);

		return $property->isStatic() ? $property->getValue() : $property->getValue($classOrObject);
	}

	/**
	 * Returns the value of an object's attribute, whatever its visibility.
	 *
	 * @param   object  $object         The object.
	 * @param   string  $attributeName  The attribute name.
	 *
	 * @return  mixed
	 *
	 * @since   3.12.0
	 */
	public static function getObjectAttribute($object, $attributeName)
	{
		return static::readAttribute($object, $attributeName);
	}

	/**
	 * Asserts that an attribute equals a value.
	 *
	 * @param   mixed          $expected       The expected value.
	 * @param   string         $attributeName  The attribute name.
	 * @param   object|string  $classOrObject  The object, or the class name for a static attribute.
	 * @param   string         $message        The failure message.
	 * @param   float          $delta          Allowed numeric difference.
	 * @param   integer        $maxDepth       Unused; kept for the PHPUnit 4.8 signature.
	 * @param   boolean        $canonicalize   Whether to sort arrays before comparing.
	 * @param   boolean        $ignoreCase     Whether to ignore case when comparing strings.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public static function assertAttributeEquals($expected, $attributeName, $classOrObject, $message = '', $delta = 0.0,
		$maxDepth = 10, $canonicalize = false, $ignoreCase = false)
	{
		$actual = static::readAttribute($classOrObject, $attributeName);

		if ($delta)
		{
			static::assertEqualsWithDelta($expected, $actual, $delta, $message);
		}
		elseif ($canonicalize)
		{
			static::assertEqualsCanonicalizing($expected, $actual, $message);
		}
		elseif ($ignoreCase)
		{
			static::assertEqualsIgnoringCase($expected, $actual, $message);
		}
		else
		{
			static::assertEquals($expected, $actual, $message);
		}
	}

	/**
	 * Asserts that an attribute is identical to a value.
	 *
	 * @param   mixed          $expected       The expected value.
	 * @param   string         $attributeName  The attribute name.
	 * @param   object|string  $classOrObject  The object, or the class name for a static attribute.
	 * @param   string         $message        The failure message.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public static function assertAttributeSame($expected, $attributeName, $classOrObject, $message = '')
	{
		static::assertSame($expected, static::readAttribute($classOrObject, $attributeName), $message);
	}

	/**
	 * Asserts that an attribute is an instance of a class.
	 *
	 * @param   string         $expected       The expected class.
	 * @param   string         $attributeName  The attribute name.
	 * @param   object|string  $classOrObject  The object, or the class name for a static attribute.
	 * @param   string         $message        The failure message.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public static function assertAttributeInstanceOf($expected, $attributeName, $classOrObject, $message = '')
	{
		static::assertInstanceOf($expected, static::readAttribute($classOrObject, $attributeName), $message);
	}

	/**
	 * Asserts that an attribute is not an instance of a class.
	 *
	 * @param   string         $expected       The class.
	 * @param   string         $attributeName  The attribute name.
	 * @param   object|string  $classOrObject  The object, or the class name for a static attribute.
	 * @param   string         $message        The failure message.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public static function assertAttributeNotInstanceOf($expected, $attributeName, $classOrObject, $message = '')
	{
		static::assertNotInstanceOf($expected, static::readAttribute($classOrObject, $attributeName), $message);
	}

	/**
	 * Asserts that an attribute is empty.
	 *
	 * @param   string         $attributeName  The attribute name.
	 * @param   object|string  $classOrObject  The object, or the class name for a static attribute.
	 * @param   string         $message        The failure message.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public static function assertAttributeEmpty($attributeName, $classOrObject, $message = '')
	{
		static::assertEmpty(static::readAttribute($classOrObject, $attributeName), $message);
	}

	/**
	 * Asserts that an attribute only contains values of a given type.
	 *
	 * @param   string         $type           The type or class name.
	 * @param   string         $attributeName  The attribute name.
	 * @param   object|string  $classOrObject  The object, or the class name for a static attribute.
	 * @param   boolean        $isNativeType   Whether $type is a native PHP type.
	 * @param   string         $message        The failure message.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public static function assertAttributeContainsOnly($type, $attributeName, $classOrObject, $isNativeType = null, $message = '')
	{
		static::assertContainsOnly($type, static::readAttribute($classOrObject, $attributeName), $isNativeType, $message);
	}
}
