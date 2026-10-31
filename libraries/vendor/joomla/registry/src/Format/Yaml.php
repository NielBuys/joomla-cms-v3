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
	 * symfony/yaml 3.0 and later reject two things that 2.x accepted: duplicate mapping keys, and plain scalars that
	 * start with a reserved indicator (`@`, `%`, a backtick or `|`). So that YAML stored under 2.x still loads, the
	 * line the parser reports is repaired the way 2.x read it and the string is parsed again. Every other error,
	 * including the parser's limits on nesting and alias expansion, is still thrown.
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

		// Each attempt repairs one line, so a document with many old-style lines needs as many attempts
		for ($attempt = 0; ; $attempt++)
		{
			try
			{
				$array = $this->parser->parse($data);

				break;
			}
			catch (ParseException $e)
			{
				$repaired = $attempt < 100 ? $this->repairLine($data, $e) : null;

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
	 * - A plain scalar that starts with a reserved indicator: 2.x read it as a string, so it is quoted.
	 *
	 * @param   string          $data   The YAML string.
	 * @param   ParseException  $error  The parse error.
	 *
	 * @return  string|null  The repaired string, or null if the error is not one of these two.
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
			// Only a "key: value" line of a block mapping whose own key is the duplicate; a duplicate inside an inline
			// mapping or a sequence item is left alone
			if (!preg_match('/^Duplicate key "(.*)" detected/', $message, $duplicate)
				|| !preg_match('/^( *)([^\s#\-{\[\'"][^:{}\[\]]*|\'[^\']*\'|"[^"]*"):(?: |$)/', $line, $matches)
				|| trim($matches[2], '\'" ') !== $duplicate[1])
			{
				return null;
			}

			$indent = strlen($matches[1]);
			$end    = $index + 1;

			// Remove the nested lines that belong to the duplicate key too
			while (isset($lines[$end])
				&& (trim($lines[$end]) === '' || strlen($lines[$end]) - strlen(ltrim($lines[$end], ' ')) > $indent))
			{
				$end++;
			}

			array_splice($lines, $index, $end - $index);

			return implode("\n", $lines);
		}

		if (strpos($message, 'cannot start a plain scalar') !== false)
		{
			// "key: @value" or "- @value", with an optional comment after the value
			if (!preg_match('/^(\s*(?:- +)?(?:[^\s#\'"{\[][^:{}\[\]]*: +)?)([@%`|][^#]*?)(\s+#.*)?$/', $line, $matches))
			{
				return null;
			}

			$comment       = isset($matches[3]) ? $matches[3] : '';
			$lines[$index] = $matches[1] . "'" . str_replace("'", "''", $matches[2]) . "'" . $comment;

			return implode("\n", $lines);
		}

		return null;
	}
}
