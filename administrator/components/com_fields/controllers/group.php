<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fields
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * The Group controller
 *
 * @since  3.7.0
 */
class FieldsControllerGroup extends JControllerForm
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string

	 * @since   3.7.0
	 */
	protected $text_prefix = 'COM_FIELDS_GROUP';

	/**
	 * The component for which the group applies.
	 *
	 * @var    string
	 * @since   3.7.0
	 */
	private $component = '';

	/**
	 * The context for which the group applies, as checked by the component entry point.
	 *
	 * @var    string
	 * @since  3.11.19
	 */
	private $internalContext = '';

	/**
	 * Class constructor.
	 *
	 * @param   array  $config  A named array of configuration variables.
	 *
	 * @since   3.7.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		// Group links and the group edit form do not always send the context, use the one the entry point authorised
		$this->internalContext = JFactory::getApplication()->getUserStateFromRequest(
			'com_fields.groups.context',
			'context',
			'com_content.article',
			'CMD'
		);

		$parts = FieldsHelper::extract($this->internalContext);

		if ($parts)
		{
			$this->component = $parts[0];
		}
	}

	/**
	 * Method to run batch operations.
	 *
	 * @param   object  $model  The model.
	 *
	 * @return  boolean   True if successful, false otherwise and internal error is set.
	 *
	 * @since   3.7.0
	 */
	public function batch($model = null)
	{
		$this->checkToken();

		// Set the model
		$model = $this->getModel('Group');

		// Preset the redirect
		$this->setRedirect('index.php?option=com_fields&view=groups');

		return parent::batch($model);
	}

	/**
	 * Method override to check if you can add a new record.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   3.7.0
	 */
	protected function allowAdd($data = array())
	{
		return JFactory::getUser()->authorise('core.create', $this->component);
	}

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   3.7.0
	 */
	protected function allowEdit($data = array(), $key = 'parent_id')
	{
		$recordId = (int) isset($data[$key]) ? $data[$key] : 0;
		$user = JFactory::getUser();

		// Zero record (parent_id:0), return component edit permission by calling parent controller method
		if (!$recordId)
		{
			return parent::allowEdit($data, $key);
		}

		// Get existing record
		$record = $this->getModel()->getItem($recordId);

		if (empty($record))
		{
			return false;
		}

		list($recordOption) = explode('.', $record->context);

		// Validate request context and field group context match
		if ($recordOption !== $this->component)
		{
			return false;
		}

		// Check edit on the record asset (explicit or inherited)
		if ($user->authorise('core.edit', $this->component . '.fieldgroup.' . $recordId))
		{
			return true;
		}

		// Check edit own on the record asset (explicit or inherited)
		if ($user->authorise('core.edit.own', $this->component . '.fieldgroup.' . $recordId) || $user->authorise('core.edit.own', $this->component))
		{
			// Grant if current user is owner of the record
			return $user->id == $record->created_by;
		}

		return false;
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   3.11.19
	 */
	protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
	{
		return parent::getRedirectToItemAppend($recordId, $urlVar) . '&context=' . $this->internalContext;
	}

	/**
	 * Gets the URL arguments to append to a list redirect.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   3.11.19
	 */
	protected function getRedirectToListAppend()
	{
		return parent::getRedirectToListAppend() . '&context=' . $this->internalContext;
	}

	/**
	 * Function that allows child controller access to model data after the data has been saved.
	 *
	 * @param   JModelLegacy  $model      The data model object.
	 * @param   array         $validData  The validated data.
	 *
	 * @return  void
	 *
	 * @since   3.7.0
	 */
	protected function postSaveHook(JModelLegacy $model, $validData = array())
	{
		$item = $model->getItem();

		if (isset($item->params) && is_array($item->params))
		{
			$registry = new Registry;
			$registry->loadArray($item->params);
			$item->params = (string) $registry;
		}

		return;
	}
}
