<?php
/**
 * Part of the Joomla Framework Registry Package
 *
 * @copyright  Copyright (C) 2005 - 2022 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Registry\Format;

use Joomla\Registry\AbstractRegistryFormat;
use Symfony\Component\Yaml\Dumper as SymfonyYamlDumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as SymfonyYamlParser;

/**
 * YAML format handler for Registry.
 *
 * @since  1.0
 */
class Yaml extends AbstractRegistryFormat
{
	/**
	 * The most values a parsed document may hold once its aliases are expanded.
	 *
	 * @var    integer
	 * @since  1.6.6
	 */
	const MAX_VALUES = 1000000;

	/**
	 * The most bytes stringToObject() parses again while it repairs old-style lines, so repairs stay fast.
	 *
	 * @var    integer
	 * @since  1.6.7
	 */
	const MAX_REPAIR_BYTES = 10000000;

	/**
	 * A block mapping key at the start of a line, plain, single-quoted or double-quoted, and the colon after it.
	 *
	 * @var    string
	 * @since  1.6.7
	 */
	const KEY_PATTERN = '([^\s#\-{\[\'"][^:{}\[\]]*|\'[^\']*\'|"[^"]*"):(?:[ \t]|$)';

	/**
	 * The YAML parser class.
	 *
	 * @var    \Symfony\Component\Yaml\Parser
	 * @since  1.0
	 */
	private $parser;

	/**
	 * The YAML dumper class.
	 *
	 * @var    \Symfony\Component\Yaml\Dumper
	 * @since  1.0
	 */
	private $dumper;

	/**
	 * Construct to set up the parser and dumper
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->parser = new SymfonyYamlParser;
		$this->dumper = new SymfonyYamlDumper;
	}

	/**
	 * Converts an object into a YAML formatted string.
	 * We use json_* to convert the passed object to an array.
	 *
	 * @param   object  $object   Data source object.
	 * @param   array   $options  Options used by the formatter.
	 *
	 * @return  string  YAML formatted string.
	 *
	 * @since   1.0
	 */
	public function objectToString($object, $options = array())
	{
		$array = json_decode(json_encode($object), true);

		return $this->dumper->dump($array, 2, 0);
	}

	/**
	 * Parse a YAML formatted string and convert it into an object.
	 * We use the json_* methods to convert the parsed YAML array to an object.
	 *
	 * symfony/yaml 3.0 and later reject YAML that 2.x accepted: duplicate mapping keys, plain scalars that start with
	 * a reserved indicator or a tag or contain ": ", and null, boolean or float mapping keys. So that YAML stored
	 * under 2.x still loads, the line the parser reports is repaired the way 2.x read it and the string is parsed
	 * again; see repairLine(). Every other error, including the parser's limits on nesting and alias expansion, is
	 * still thrown, and so is any error once MAX_REPAIR_BYTES have been parsed again.
	 *
	 * @param   string  $data     YAML formatted string to convert.
	 * @param   array   $options  Options used by the formatter.
	 *
	 * @return  object  Data object.
	 *
	 * @since   1.0
	 */
	public function stringToObject($data, array $options = array())
	{
		$data = trim($data);

		// Each attempt repairs one line and parses the whole document again, so the total is capped by size
		for ($reparsed = 0; ; $reparsed += strlen($data))
		{
			try
			{
				$array = $this->parser->parse($data);

				break;
			}
			catch (ParseException $e)
			{
				$repaired = $reparsed + strlen($data) <= self::MAX_REPAIR_BYTES ? $this->repairLine($data, $e) : null;

				if ($repaired === null)
				{
					throw $e;
				}

				$data = $repaired;
			}
		}

		// Aliases let a short document describe a huge structure, which json_encode() below would expand in memory
		$budget = self::MAX_VALUES;
		$this->countValues($array, $budget);

		return (object) json_decode(json_encode($array));
	}

	/**
	 * Counts the values in a parsed document, and stops once the budget is used up.
	 *
	 * @param   mixed    $value   The parsed value.
	 * @param   integer  $budget  How many more values are allowed; reduced by the values counted.
	 *
	 * @return  void
	 *
	 * @since   1.6.6
	 * @throws  ParseException  If the document has more than MAX_VALUES values once its aliases are expanded.
	 */
	private function countValues($value, &$budget)
	{
		if (--$budget < 0)
		{
			throw new ParseException(sprintf('The YAML document expands to more than %d values.', self::MAX_VALUES));
		}

		if (is_array($value))
		{
			foreach ($value as $item)
			{
				$this->countValues($item, $budget);
			}
		}
	}

	/**
	 * Repairs the line a parse error points at, the way symfony/yaml 2.x read it.
	 *
	 * - A duplicate key in a block mapping: 2.x kept the first value, so the later key and its nested lines are removed.
	 * - A plain scalar that starts with a reserved indicator (@ % ` | >) or a tag, or a mapping value that contains
	 *   ": ": 2.x read it as a string, so it is quoted.
	 * - A null, boolean or float mapping key: 2.x used PHP's array key for it, so it is replaced by that key, quoted.
	 *
	 * Inside flow collections ([...] and {...}) nothing is repaired, so those errors are still thrown.
	 *
	 * @param   string          $data   The YAML string.
	 * @param   ParseException  $error  The parse error.
	 *
	 * @return  string|null  The repaired string, or null if the error is not one of these.
	 *
	 * @since   1.6.6
	 */
	private function repairLine($data, ParseException $error)
	{
		$message = $error->getMessage();
		$lines   = preg_split('/\r\n|\r|\n/', $data);
		$index   = $error->getParsedLine() - 1;

		if (!isset($lines[$index]))
		{
			return null;
		}

		$line = $lines[$index];

		if (strpos($message, 'Duplicate key') === 0)
		{
			if (!preg_match('/^Duplicate key "(.*)" detected/', $message, $duplicate))
			{
				return null;
			}

			// The duplicate is this line's key when an earlier key of the same block mapping has its name. Otherwise it
			// may be inside an inline mapping on this line, as in "a: {a: 1, a: 2}", which is not repaired.
			$isKeyLine = preg_match('/^( *)' . self::KEY_PATTERN . '/', $line, $matches)
				&& trim($matches[2], '\'" ') === $duplicate[1]
				&& $this->hasEarlierSibling($lines, $index, strlen($matches[1]), $duplicate[1]);

			// When the later value is a block scalar or a nested block, the error points at a line inside it, so the
			// key is on the closest less indented line above
			if (!$isKeyLine)
			{
				$index = $this->findParentKey($lines, $index, $duplicate[1]);

				if ($index === null)
				{
					return null;
				}

				preg_match('/^( *)/', $lines[$index], $matches);

				if (!$this->hasEarlierSibling($lines, $index, strlen($matches[1]), $duplicate[1]))
				{
					return null;
				}
			}

			$indent = strlen($matches[1]);

			$end = $index + 1;

			// Remove the nested lines that belong to the duplicate key too, and comments between them
			while (isset($lines[$end])
				&& (trim($lines[$end]) === '' || ltrim($lines[$end])[0] === '#'
				|| strlen($lines[$end]) - strlen(ltrim($lines[$end], ' ')) > $indent))
			{
				$end++;
			}

			array_splice($lines, $index, $end - $index);

			return implode("\n", $lines);
		}

		if (strpos($message, 'Non-string keys are not supported') === 0 || strpos($message, 'Numeric keys are not supported') === 0)
		{
			$keys = 'null|Null|NULL|~|true|True|TRUE|false|False|FALSE|[-+]?(?:\d+\.?\d*|\.\d+)(?:[eE][-+]?\d+)?';

			if (!preg_match('/^(\s*(?:- +)?)(' . $keys . ')(:(?:[ \t].*)?)$/', $line, $matches))
			{
				return null;
			}

			$lines[$index] = $matches[1] . "'" . $this->phpArrayKey($matches[2]) . "'" . $matches[3];

			return implode("\n", $lines);
		}

		// The start of the value, and whether a "key: " must come before it
		if (strpos($message, 'cannot start a plain scalar') !== false)
		{
			$value = '[@%`|>]';
			$keyed = '?';
		}
		elseif (strpos($message, 'Tags support is not enabled') === 0)
		{
			$value = '![^\s!]*\s';
			$keyed = '?';
		}
		elseif (strpos($message, 'A colon cannot be used in an unquoted mapping value') === 0)
		{
			// Only after a key, so a line that does not have the "key: value" form is never quoted as a whole
			$value = '[^\s#\'"{\[!&*]';
			$keyed = '';
		}
		else
		{
			return null;
		}

		// "key: value" or "- value". The key runs to the first ": ", so keys such as a[0] or http://x work. The value
		// runs to the end of the line, and a comment is split off afterwards without a regular expression, so a long
		// line cannot make the match backtrack.
		$key = '[^\s#\'"{\[](?:[^:]|:(?![ \t]))*?:[ \t]+';

		if (!preg_match('/^(\s*(?:- +)?(?:' . $key . ')' . $keyed . ')(' . $value . '.*)$/', $line, $matches))
		{
			return null;
		}

		$value   = $matches[2];
		$comment = '';

		for ($hash = strpos($value, '#'); $hash !== false; $hash = strpos($value, '#', $hash + 1))
		{
			if (ctype_space($value[$hash - 1]))
			{
				$end     = strlen(rtrim(substr($value, 0, $hash)));
				$comment = substr($value, $end);
				$value   = substr($value, 0, $end);
				break;
			}
		}

		$lines[$index] = $matches[1] . "'" . str_replace("'", "''", $value) . "'" . $comment;

		return implode("\n", $lines);
	}

	/**
	 * Converts a null, boolean or float mapping key to the array key symfony/yaml 2.x gave it.
	 *
	 * 2.x parsed the key as a scalar and used it as a PHP array key, so null became '', true 1 and false 0. It
	 * converted a float to a string first, so 1.5 stayed '1.5', 1.0 became '1' and 1e3 became '1000'.
	 *
	 * @param   string  $key  The key as written.
	 *
	 * @return  string
	 *
	 * @since   1.6.7
	 */
	private function phpArrayKey($key)
	{
		switch (strtolower($key))
		{
			case 'null':
			case '~':
				return '';

			case 'true':
				return '1';

			case 'false':
				return '0';
		}

		return (string) (float) $key;
	}

	/**
	 * Finds the block mapping key that a line belongs to, when that key is the given duplicate.
	 *
	 * @param   array    $lines  The document's lines.
	 * @param   integer  $index  A line inside the duplicate key's value.
	 * @param   string   $key    The duplicate key.
	 *
	 * @return  integer|null  The line of the key, or null if the closest less indented line is not that key.
	 *
	 * @since   1.6.7
	 */
	private function findParentKey(array $lines, $index, $key)
	{
		$indent = strlen($lines[$index]) - strlen(ltrim($lines[$index], ' '));

		for ($i = $index - 1; $i >= 0; $i--)
		{
			$text = ltrim($lines[$i], ' ');

			if ($text === '' || $text[0] === '#' || strlen($lines[$i]) - strlen($text) >= $indent)
			{
				continue;
			}

			if (preg_match('/^ *' . self::KEY_PATTERN . '/', $lines[$i], $matches)
				&& trim($matches[1], '\'" ') === $key)
			{
				return $i;
			}

			return null;
		}

		return null;
	}

	/**
	 * Checks whether a block mapping has a key before the given line.
	 *
	 * @param   array    $lines   The document's lines.
	 * @param   integer  $index   The line of the later key.
	 * @param   integer  $indent  The indentation of that key.
	 * @param   string   $key     The key.
	 *
	 * @return  boolean
	 *
	 * @since   1.6.7
	 */
	private function hasEarlierSibling(array $lines, $index, $indent, $key)
	{
		for ($i = $index - 1; $i >= 0; $i--)
		{
			$text = ltrim($lines[$i], ' ');

			if ($text === '' || $text[0] === '#')
			{
				continue;
			}

			$lineIndent = strlen($lines[$i]) - strlen($text);
			$keyIndent  = $lineIndent;

			// In "- a: 1" the key starts after the dash, where the mapping's other keys line up
			if (preg_match('/^(?:- +)+/', $text, $dash))
			{
				$keyIndent += strlen($dash[0]);
				$text       = substr($text, strlen($dash[0]));
			}

			if ($keyIndent === $indent
				&& preg_match('/^' . self::KEY_PATTERN . '/', $text, $matches)
				&& trim($matches[1], '\'" ') === $key)
			{
				return true;
			}

			// A less indented line is the parent, or the sequence item the mapping starts in
			if ($lineIndent < $indent)
			{
				return false;
			}
		}

		return false;
	}
}
