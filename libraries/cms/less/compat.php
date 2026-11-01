<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  LESS
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use LesserPHP\FormatterClassic;
use LesserPHP\Utils\Asserts;

/**
 * The leafo/lessphp `lessc` API on top of splitbrain/lesserphp
 *
 * Extensions written for Joomla 3 use `lessc` methods and properties that lesserphp removed or changed. This class
 * puts back lessphp's public API, so code that calls `new lessc` and its public methods keeps working.
 *
 * Subclasses are less certain, because lesserphp declares types that lessphp did not. A subclass of `lessc` or `JLess`
 * fails to load if it overrides compile(), addParsedFile(), reduce(), findImport() or another typed method without
 * the same types, and overrides of lessphp's protected compiler methods may no longer be called. Formatters must
 * extend `lessc_formatter_classic`, and setFormatter() accepts only the names lessjs, compressed and classic.
 *
 * @package     Joomla.Libraries
 * @subpackage  Less
 * @since       3.12.0
 * @deprecated  4.0  without replacement
 */
class JLessCompat extends \LesserPHP\Lessc
{
	/**
	 * The lessphp version this class stands in for
	 *
	 * @var    string
	 * @since  3.12.0
	 */
	public static $VERSION = 'v0.5.0';

	/**
	 * The value functions added with registerFunction() return for true
	 *
	 * @var    array
	 * @since  3.12.0
	 */
	public static $TRUE = array('keyword', 'true');

	/**
	 * The value functions added with registerFunction() return for false
	 *
	 * @var    array
	 * @since  3.12.0
	 */
	public static $FALSE = array('keyword', 'false');

	/**
	 * Directories searched for imported files
	 *
	 * @var    string|array
	 * @since  3.12.0
	 */
	public $importDir = array();

	/**
	 * Whether @import is ignored
	 *
	 * @var    boolean
	 * @since  3.12.0
	 */
	public bool $importDisabled = false;

	/**
	 * lessphp's syntax prefixes. lesserphp has them as constants, so these can be read but changing them has no effect.
	 *
	 * @var    string
	 * @since  3.12.0
	 */
	public $vPrefix = '@';

	/**
	 * @var    string
	 * @since  3.12.0
	 */
	public $mPrefix = '$';

	/**
	 * @var    string
	 * @since  3.12.0
	 */
	public $parentSelector = '&';

	/**
	 * The file the deprecated parse() method compiles when it is given no string
	 *
	 * @var    string|null
	 * @since  3.12.0
	 */
	protected $_parseFile;

	/**
	 * Constructor
	 *
	 * @param   string  $fname  File for the deprecated parse() method to compile
	 *
	 * @since   3.12.0
	 */
	public function __construct($fname = null)
	{
		parent::__construct();

		if ($fname !== null)
		{
			$this->_parseFile = $fname;
		}

		// lessphp called a LESS function such as my-fn() as the method lib_my_fn() of the compiler, so subclasses could
		// add or override functions that way. lesserphp only looks in the registered functions.
		foreach ((new ReflectionClass($this))->getMethods() as $method)
		{
			// A private method of a subclass cannot be called from here; lessphp could not call it either
			if (strpos($method->name, 'lib_') === 0 && !$method->isStatic() && !$method->isPrivate())
			{
				$callback = Closure::fromCallable(array($this, $method->name));
				$name     = substr($method->name, 4);

				$this->registerFunction($name, $callback);
				$this->registerFunction(str_replace('_', '-', $name), $callback);
			}
		}
	}

	/**
	 * Convert a PHP value to a LESS boolean
	 *
	 * @param   mixed  $a  The value
	 *
	 * @return  array  self::$TRUE or self::$FALSE
	 *
	 * @since   3.12.0
	 */
	public function toBool($a)
	{
		return $a ? self::$TRUE : self::$FALSE;
	}

	/**
	 * Get the colour and the percentage from the arguments of a colour function, such as darken(@c, 10%)
	 *
	 * @param   array  $args  The arguments
	 *
	 * @return  array  The colour and the percentage
	 *
	 * @since   3.12.0
	 */
	public function colorArgs($args)
	{
		if ($args[0] != 'list' || count($args[2]) < 2)
		{
			return array(array('color', 0, 0, 0), 0);
		}

		list($color, $delta) = $args[2];

		return array($this->assertColor($color), floatval($delta[1]));
	}

	/**
	 * Set the output formatter, by object or by lessphp's name for it: 'lessjs', 'compressed' or 'classic'
	 *
	 * @param   FormatterClassic|string|null  $formatter  The formatter
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public function setFormatter($formatter = null)
	{
		if (is_string($formatter))
		{
			$classes = array(
				'lessjs'     => 'LesserPHP\\FormatterLessJs',
				'compressed' => 'LesserPHP\\FormatterCompressed',
				'classic'    => 'LesserPHP\\FormatterClassic',
			);

			if (!isset($classes[$formatter]))
			{
				throw new InvalidArgumentException('Unknown LESS formatter: ' . $formatter);
			}

			$formatter = new $classes[$formatter];
		}

		parent::setFormatter($formatter);
	}

	/**
	 * Compile $in to $out if $in is newer than $out
	 *
	 * @param   string  $in   LESS file
	 * @param   string  $out  CSS file
	 *
	 * @return  boolean  True when it compiled, false when $out was up to date
	 *
	 * @since   3.12.0
	 */
	public function checkedCompile($in, $out)
	{
		if (!is_file($out) || filemtime($in) > filemtime($out))
		{
			$this->compileFile($in, $out);

			return true;
		}

		return false;
	}

	/**
	 * Compile a LESS file, or recompile a lessphp cache structure when one of its files has changed
	 *
	 * @param   string|array  $in     LESS file, or the structure a previous call returned
	 * @param   boolean       $force  Recompile even when nothing has changed
	 *
	 * @return  array|null  The cache structure: root, compiled, files and updated
	 *
	 * @since   3.12.0
	 */
	public function cachedCompile($in, $force = false)
	{
		$root = null;

		if (is_string($in))
		{
			$root = $in;
		}
		elseif (is_array($in) && isset($in['root']))
		{
			if ($force || !isset($in['files']) || !is_array($in['files']))
			{
				$root = $in['root'];
			}
			else
			{
				foreach ($in['files'] as $fname => $ftime)
				{
					if (!file_exists($fname) || filemtime($fname) > $ftime)
					{
						$root = $in['root'];
						break;
					}
				}
			}
		}
		else
		{
			return null;
		}

		if ($root === null)
		{
			return $in;
		}

		return array(
			'root'     => $root,
			'compiled' => $this->compileFile($root),
			'files'    => $this->allParsedFiles(),
			'updated'  => time(),
		);
	}

	/**
	 * Compile a LESS file, and write the CSS to $outFname if one is given
	 *
	 * @param   string  $fname     LESS file
	 * @param   string  $outFname  CSS file
	 *
	 * @return  integer|string  Bytes written to $outFname, or the CSS
	 *
	 * @since   3.12.0
	 */
	public function compileFile(string $fname, ?string $outFname = null)
	{
		$out = parent::compileFile($fname, $outFname);

		// lesserphp forgets the root file when it starts compiling, but lessphp listed it with the imports
		$this->addParsedFile($fname);

		return $out;
	}

	/**
	 * Compile a string, or the file given to the constructor, with optional variables for this call only
	 *
	 * @param   string|array  $str               LESS code, or the variables when no code is given
	 * @param   array         $initialVariables  Variables to set
	 *
	 * @return  string  The compiled CSS
	 *
	 * @since   3.12.0
	 * @deprecated  Use compile() or compileFile()
	 */
	public function parse($str = null, $initialVariables = null)
	{
		if (is_array($str))
		{
			$initialVariables = $str;
			$str = null;
		}

		$oldVars = $this->registeredVars;

		if ($initialVariables !== null)
		{
			$this->setVariables($initialVariables);
		}

		try
		{
			if ($str === null || $str === '')
			{
				if (empty($this->_parseFile))
				{
					throw new Exception('nothing to parse');
				}

				return $this->compileFile($this->_parseFile);
			}

			return $this->compile($str);
		}
		finally
		{
			$this->registeredVars = $oldVars;
		}
	}

	/**
	 * The files the last compile read, as path => modification time
	 *
	 * @return  array
	 *
	 * @since   3.12.0
	 */
	public function allParsedFiles()
	{
		return $this->allParsedFiles;
	}

	/**
	 * Add a file to the list allParsedFiles() returns
	 *
	 * @param   string  $file  The file
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public function addParsedFile($file): void
	{
		parent::addParsedFile($file);
	}

	/**
	 * Throw a compile error
	 *
	 * @param   string  $msg  The message
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   3.12.0
	 */
	public function throwError($msg = null)
	{
		// compile() adds the line the error is on
		throw new Exception((string) $msg);
	}

	/**
	 * Check that a value is a colour, for functions added with registerFunction()
	 *
	 * @param   array   $value  The value
	 * @param   string  $error  The message if it is not
	 *
	 * @return  array
	 *
	 * @since   3.12.0
	 */
	public function assertColor($value, $error = 'expected color value')
	{
		return Asserts::assertColor($value, $error);
	}

	/**
	 * Check that a value is a number, for functions added with registerFunction()
	 *
	 * @param   array   $value  The value
	 * @param   string  $error  The message if it is not
	 *
	 * @return  float
	 *
	 * @since   3.12.0
	 */
	public function assertNumber($value, $error = 'expecting number')
	{
		return Asserts::assertNumber($value, $error);
	}

	/**
	 * Check the number of arguments, for functions added with registerFunction()
	 *
	 * @param   array    $value         The arguments
	 * @param   integer  $expectedArgs  How many there must be
	 * @param   string   $name          The function name for the message
	 *
	 * @return  array
	 *
	 * @since   3.12.0
	 */
	public function assertArgs($value, $expectedArgs, $name = '')
	{
		return Asserts::assertArgs($value, $expectedArgs, $name);
	}

	/**
	 * Quote a string for use in a regular expression
	 *
	 * @param   string  $what  The string
	 *
	 * @return  string
	 *
	 * @since   3.12.0
	 */
	public static function preg_quote($what)
	{
		return preg_quote($what, '/');
	}

	/**
	 * Compile $in to $out if $in is newer than $out
	 *
	 * @param   string       $in    LESS file
	 * @param   string       $out   CSS file
	 * @param   JLessCompat  $less  Compiler to use
	 *
	 * @return  boolean  True when it compiled
	 *
	 * @since   3.12.0
	 */
	public static function ccompile($in, $out, $less = null)
	{
		if ($less === null)
		{
			$less = new static;
		}

		return $less->checkedCompile($in, $out);
	}

	/**
	 * Compile a LESS file or cache structure; see cachedCompile()
	 *
	 * @param   string|array  $in     LESS file or cache structure
	 * @param   boolean       $force  Recompile even when nothing has changed
	 * @param   JLessCompat   $less   Compiler to use
	 *
	 * @return  array|null
	 *
	 * @since   3.12.0
	 */
	public static function cexecute($in, $force = false, $less = null)
	{
		if ($less === null)
		{
			$less = new static;
		}

		return $less->cachedCompile($in, $force);
	}
}
