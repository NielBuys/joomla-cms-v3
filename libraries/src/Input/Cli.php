<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Input;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Filter\InputFilter;

/**
 * Joomla! Input CLI Class
 *
 * @since       1.7.0
 * @deprecated  5.0  Use Joomla\Input\Cli instead
 */
class Cli extends Input
{
	/**
	 * The executable that was called to run the CLI script.
	 *
	 * @var    string
	 * @since  1.7.0
	 * @deprecated  5.0  Use Joomla\Input\Cli instead
	 */
	public $executable;

	/**
	 * The additional arguments passed to the script that are not associated
	 * with a specific argument name.
	 *
	 * @var    array
	 * @since  1.7.0
	 * @deprecated  5.0  Use Joomla\Input\Cli instead
	 */
	public $args = array();

	/**
	 * Constructor.
	 *
	 * @param   array  $source   Source data (Optional, default is $_REQUEST)
	 * @param   array  $options  Array of configuration parameters (Optional)
	 *
	 * @since   1.7.0
	 * @deprecated  5.0  Use Joomla\Input\Cli instead
	 */
	public function __construct(array $source = null, array $options = array())
	{
		if (isset($options['filter']))
		{
			$this->filter = $options['filter'];
		}
		else
		{
			$this->filter = InputFilter::getInstance();
		}

		// Get the command line options
		$this->parseArguments();

		// Set the options for the class.
		$this->options = $options;
	}

	/**
	 * Method to serialize the input.
	 *
	 * Returns an array rather than a string: Joomla\Input\Input declares
	 * `serialize(): array` since the Serializable deprecation fix, and a child that
	 * widens that return type is a fatal error. This class kept the old string form
	 * and so could not be loaded at all — which took every CLI application with it,
	 * because Joomla\CMS\Input\Cli is only ever loaded by one.
	 *
	 * @return  array  The serialized input.
	 *
	 * @since   3.0.0
	 * @deprecated  5.0  Use Joomla\Input\Cli instead
	 */
	public function serialize(): array
	{
		return $this->__serialize();
	}

	/**
	 * Method to serialize the input.
	 *
	 * @return  array  The serialized input.
	 *
	 * @since   3.10.0
	 * @deprecated  5.0  Use Joomla\Input\Cli instead
	 */
	public function __serialize(): array
	{
		// Load all of the inputs.
		$this->loadAllInputs();

		// Remove $_ENV and $_SERVER from the inputs.
		$inputs = $this->inputs;
		unset($inputs['env']);
		unset($inputs['server']);

		// Return an array representation of the object's state, keeping the
		// executable and args this class adds on top of the parent's three keys.
		return array(
			'executable' => $this->executable,
			'args'       => $this->args,
			'options'    => $this->options,
			'data'       => $this->data,
			'inputs'     => $inputs,
		);
	}

	/**
	 * Method to unserialize the input.
	 *
	 * Accepts the array produced by serialize() as well as a string from an older
	 * release, so payloads written before this fix still restore.
	 *
	 * @param   array|string  $input  The serialized input.
	 *
	 * @return  void
	 *
	 * @since   3.0.0
	 * @deprecated  5.0  Use Joomla\Input\Cli instead
	 */
	public function unserialize($input)
	{
		$this->__unserialize($input);
	}

	/**
	 * Method to unserialize the input.
	 *
	 * @param   array|string  $data  The serialized input.
	 *
	 * @return  void
	 *
	 * @since   3.10.0
	 * @deprecated  5.0  Use Joomla\Input\Cli instead
	 */
	public function __unserialize($data)
	{
		if (!is_array($data))
		{
			$data = unserialize($data);
		}

		if (array_key_exists(0, $data))
		{
			// Payload written before this fix: a positional list.
			list($this->executable, $this->args, $this->options, $this->data, $this->inputs) = $data;
		}
		else
		{
			$this->executable = isset($data['executable']) ? $data['executable'] : null;
			$this->args       = isset($data['args']) ? $data['args'] : array();
			$this->options    = isset($data['options']) ? $data['options'] : array();
			$this->data       = isset($data['data']) ? $data['data'] : array();
			$this->inputs     = isset($data['inputs']) ? $data['inputs'] : array();
		}

		// Load the filter.
		if (isset($this->options['filter']))
		{
			$this->filter = $this->options['filter'];
		}
		else
		{
			$this->filter = InputFilter::getInstance();
		}
	}

	/**
	 * Initialise the options and arguments
	 *
	 * Not supported: -abc c-value
	 *
	 * @return  void
	 *
	 * @since   1.7.0
	 * @deprecated  5.0  Use Joomla\Input\Cli instead
	 */
	protected function parseArguments()
	{
		$argv = $_SERVER['argv'];

		$this->executable = array_shift($argv);

		$out = array();

		for ($i = 0, $j = count($argv); $i < $j; $i++)
		{
			$arg = $argv[$i];

			// --foo --bar=baz
			if (substr($arg, 0, 2) === '--')
			{
				$eqPos = strpos($arg, '=');

				// --foo
				if ($eqPos === false)
				{
					$key = substr($arg, 2);

					// --foo value
					if ($i + 1 < $j && $argv[$i + 1][0] !== '-')
					{
						$value = $argv[$i + 1];
						$i++;
					}
					else
					{
						$value = isset($out[$key]) ? $out[$key] : true;
					}

					$out[$key] = $value;
				}

				// --bar=baz
				else
				{
					$key = substr($arg, 2, $eqPos - 2);
					$value = substr($arg, $eqPos + 1);
					$out[$key] = $value;
				}
			}
			elseif (substr($arg, 0, 1) === '-')
			// -k=value -abc
			{
				// -k=value
				if (substr($arg, 2, 1) === '=')
				{
					$key = substr($arg, 1, 1);
					$value = substr($arg, 3);
					$out[$key] = $value;
				}
				else
				// -abc
				{
					$chars = str_split(substr($arg, 1));

					foreach ($chars as $char)
					{
						$key = $char;
						$value = isset($out[$key]) ? $out[$key] : true;
						$out[$key] = $value;
					}

					// -a a-value
					if ((count($chars) === 1) && ($i + 1 < $j) && ($argv[$i + 1][0] !== '-'))
					{
						$out[$key] = $argv[$i + 1];
						$i++;
					}
				}
			}
			else
			{
				// Plain-arg
				$this->args[] = $arg;
			}
		}

		$this->data = $out;
	}
}
