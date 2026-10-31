<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  Registry
 *
 * @copyright   (C) 2026 Niel Buys. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\Registry\Registry;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Tests for the Registry YAML format with symfony/yaml 7.4.
 *
 * YAML that symfony/yaml 2.8 loaded must keep loading the same way where the NielBuys fork of joomla/registry can
 * make it, and the parser's limits must still stop hostile documents.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Registry
 * @since       3.12.0
 */
class JRegistryFormatYamlTest extends TestCasePhpunit
{
	/**
	 * Provides YAML that loads, with the data it loads as.
	 *
	 * @return  array
	 *
	 * @since   3.12.0
	 */
	public function dataLoads()
	{
		return array(
			'nested mapping' => array(
				"db:\n  host: localhost\n  options:\n    ssl: false\n",
				array('db' => array('host' => 'localhost', 'options' => array('ssl' => false))),
			),
			'list of maps' => array(
				"items:\n  - name: one\n  - name: two\n",
				array('items' => array(array('name' => 'one'), array('name' => 'two'))),
			),
			// symfony/yaml 2.8 failed on these with a TypeError on PHP 8
			'literal block' => array("text: |\n  line one\n  line two\n", array('text' => "line one\nline two")),
			'folded block' => array("text: >\n  folded\n  text\n", array('text' => 'folded text')),
			// 2.8 kept the first value of a duplicate key, and the registry fork does the same
			'duplicate key' => array("key: first\nkey: second\n", array('key' => 'first')),
			'duplicate nested key' => array(
				"a:\n  x: 1\nb: keep\na:\n  x: 9\n  z: 9\nc: 3\n",
				array('a' => array('x' => 1), 'b' => 'keep', 'c' => 3),
			),
			// 2.8 read plain scalars that start with a reserved indicator as strings
			'reserved indicators' => array(
				"u: @a\nv: %b\nw: `c`\nlist:\n  - @x\n",
				array('u' => '@a', 'v' => '%b', 'w' => '`c`', 'list' => array('@x')),
			),
			'reserved indicator with comment' => array("user: @admin # the admin\n", array('user' => '@admin')),
		);
	}

	/**
	 * Tests that YAML loads as expected.
	 *
	 * @param   string  $yaml      The YAML.
	 * @param   array   $expected  The expected data.
	 *
	 * @return  void
	 *
	 * @dataProvider  dataLoads
	 * @since         3.12.0
	 */
	public function testLoads($yaml, $expected)
	{
		$registry = new Registry;
		$registry->loadString($yaml, 'YAML');

		$this->assertSame($expected, $registry->toArray());
	}

	/**
	 * Provides YAML that must be rejected.
	 *
	 * @return  array
	 *
	 * @since   3.12.0
	 */
	public function dataRejected()
	{
		$bomb = "a: &a [x, x, x, x, x, x, x, x, x]\n";
		$previous = 'a';

		foreach (range('b', 'n') as $anchor)
		{
			$bomb .= "$anchor: &$anchor [" . implode(', ', array_fill(0, 9, '*' . $previous)) . "]\n";
			$previous = $anchor;
		}

		return array(
			'alias expansion' => array($bomb),
			'deep inline nesting' => array(str_repeat('[', 2000) . str_repeat(']', 2000)),
			'php object tag' => array("obj: !php/object:O:8:\"stdClass\":0:{}\n"),
			'duplicate key in an inline mapping' => array("m: {a: 1, a: 2}\n"),
		);
	}

	/**
	 * Tests that hostile or invalid YAML throws a ParseException.
	 *
	 * @param   string  $yaml  The YAML.
	 *
	 * @return  void
	 *
	 * @dataProvider  dataRejected
	 * @since         3.12.0
	 */
	public function testRejected($yaml)
	{
		$this->expectException(ParseException::class);

		$registry = new Registry;
		$registry->loadString($yaml, 'YAML');
	}

	/**
	 * Tests that data written as YAML reads back unchanged.
	 *
	 * @return  void
	 *
	 * @since   3.12.0
	 */
	public function testRoundTrip()
	{
		$data = array(
			'title' => 'Site', 'flag' => true, 'none' => null, 'n' => 3, 'f' => 1.5, 'list' => array('a', 'b'),
			'nested' => array('x' => array('y' => 'z')), 'multi' => "line1\nline2", 'quote' => "it's", 'colon' => 'a: b',
		);

		$copy = new Registry;
		$copy->loadString((new Registry($data))->toString('YAML'), 'YAML');

		$this->assertSame($data, $copy->toArray());
	}
}
