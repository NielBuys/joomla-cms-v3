<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Session
 *
 * @copyright   (C) 2007 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Custom session storage handler for PHP
 *
 * @link        https://www.php.net/manual/en/function.session-set-save-handler.php
 * @since       1.7.0
 * @deprecated  4.0  The CMS' Session classes will be replaced with the `joomla/session` package
 */
abstract class JSessionStorage implements SessionHandlerInterface
{
	/**
	 * @var    JSessionStorage[]  JSessionStorage instances container.
	 * @since  1.7.3
	 */
	protected static $instances = array();

	/**
	 * Constructor
	 *
	 * @param   array  $options  Optional parameters.
	 *
	 * @since   1.7.0
	 */
	public function __construct($options = array())
	{
		$this->register($options);
	}

	/**
	 * Returns a session storage handler object, only creating it if it doesn't already exist.
	 *
	 * @param   string  $name     The session store to instantiate
	 * @param   array   $options  Array of options
	 *
	 * @return  JSessionStorage
	 *
	 * @since   1.7.0
	 * @throws  JSessionExceptionUnsupported
	 */
	public static function getInstance($name = 'none', $options = array())
	{
		$name = strtolower(JFilterInput::getInstance()->clean($name, 'word'));

		if (empty(self::$instances[$name]))
		{
			/** @var JSessionStorage $class */
			$class = 'JSessionStorage' . ucfirst($name);

			if (!class_exists($class))
			{
				$path = __DIR__ . '/storage/' . $name . '.php';

				if (!file_exists($path))
				{
					throw new JSessionExceptionUnsupported('Unable to load session storage class: ' . $name);
				}

				JLoader::register($class, $path);

				// The class should now be loaded
				if (!class_exists($class))
				{
					throw new JSessionExceptionUnsupported('Unable to load session storage class: ' . $name);
				}
			}

			// Validate the session storage is supported on this platform
			if (!$class::isSupported())
			{
				throw new JSessionExceptionUnsupported(sprintf('The %s Session Storage is not supported on this platform.', $name));
			}

			self::$instances[$name] = new $class($options);
		}

		return self::$instances[$name];
	}

	/**
	 * Register the functions of this class with PHP's session handler
	 *
	 * @return  void
	 *
	 * @since   1.7.0
	 */
	public function register()
	{
		if (!headers_sent())
		{
			// Pass the object: the separate callables form is deprecated since PHP 8.4. Joomla's session handler
			// already writes the session at shutdown, so PHP does not need to register its own shutdown write.
			session_set_save_handler($this, false);
		}
	}

	/**
	 * Open the SessionHandler backend.
	 *
	 * @param   string  $savePath     The path to the session object.
	 * @param   string  $sessionName  The name of the session.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   1.7.0
	 */
	public function open(string $savePath, string $sessionName): bool
	{
		return true;
	}

	/**
	 * Close the SessionHandler backend.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   1.7.0
	 */
	public function close(): bool
	{
		return true;
	}

	/**
	 * Read the data for a particular session identifier from the
	 * SessionHandler backend.
	 *
	 * @param   string  $id  The session identifier.
	 *
	 * @return  string  The session data.
	 *
	 * @since   1.7.0
	 */
	public function read(string $id): string|false
	{
		return '';
	}

	/**
	 * Write session data to the SessionHandler backend.
	 *
	 * @param   string  $id           The session identifier.
	 * @param   string  $sessionData  The session data.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   1.7.0
	 */
	public function write($id, $sessionData): bool
	{
		return true;
	}

	/**
	 * Destroy the data for a particular session identifier in the
	 * SessionHandler backend.
	 *
	 * @param   string  $id  The session identifier.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   1.7.0
	 */
	public function destroy($id): bool
	{
		return true;
	}

	/**
	 * Garbage collect stale sessions from the SessionHandler backend.
	 *
	 * @param   integer  $maxlifetime  The maximum age of a session.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   1.7.0
	 */
	public function gc(int $maxlifetime): int|false
	{
		return true;
	}

	/**
	 * Create a new session identifier.
	 *
	 * PHP 8.6 warns when a SessionHandlerInterface class lacks this method, and PHP 9.0 requires it.
	 * The identifier is generated here rather than with session_create_id(), which calls back into
	 * this method while a session is active (e.g. in session_regenerate_id()) and would recurse.
	 *
	 * @return  string  The session identifier.
	 *
	 * @since   3.11.17
	 */
	public function create_sid(): string
	{
		// Honour session.sid_length; hex characters are valid for every session.sid_bits_per_character
		$length = max(22, min(256, (int) ini_get('session.sid_length') ?: 32));

		return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
	}

	/**
	 * Check whether a session identifier refers to an existing session.
	 *
	 * PHP 8.6 warns when a SessionHandlerInterface class lacks this method, and PHP 9.0 requires it.
	 * This matches PHP's own check for handlers without validateId(): a session exists when reading it
	 * returns data.
	 *
	 * @param   string  $id  The session identifier.
	 *
	 * @return  boolean  True when the session exists.
	 *
	 * @since   3.11.17
	 */
	public function validateId(string $id): bool
	{
		$data = $this->read($id);

		return \is_string($data) && $data !== '';
	}

	/**
	 * Test to see if the SessionHandler is available.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   3.0.0
	 */
	public static function isSupported()
	{
		return true;
	}

	/**
	 * Test to see if the SessionHandler is available.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @since   1.7.0
	 * @deprecated  4.0 - Use JSessionStorage::isSupported() instead.
	 */
	public static function test()
	{
		JLog::add('JSessionStorage::test() is deprecated. Use JSessionStorage::isSupported() instead.', JLog::WARNING, 'deprecated');

		return static::isSupported();
	}
}
