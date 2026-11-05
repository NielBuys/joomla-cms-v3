<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_config
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Cancel Controller
 *
 * @since  3.2
 */
class ConfigControllerCancel extends JControllerBase
{
	/**
	 * The parts of the task, set by ConfigControllerHelper::parseController()
	 *
	 * @var    array
	 * @since  3.12.0
	 */
	public $options = array();

	/**
	 * Prefix for the view and model classes, set by the component entry point
	 *
	 * @var    string
	 * @since  3.12.0
	 */
	public $prefix = 'Config';

	/**
	 * Application object - Redeclared for proper typehinting
	 *
	 * @var    JApplicationCms
	 * @since  3.2
	 */
	protected $app;

	/**
	 * Method to handle cancel
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.2
	 */
	public function execute()
	{
		// Redirect back to home(base) page
		$this->app->redirect(JUri::base());
	}
}
