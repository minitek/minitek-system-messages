<?php

/**
 * @title        Minitek System Messages
 * @copyright    Copyright (C) 2011-2021 Minitek, All rights reserved.
 * @license      GNU General Public License version 3 or later.
 * @author url   https://www.minitek.gr/
 * @developers   Minitek.gr / Yannis Maragos
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\String\StringHelper;
use Joomla\Component\Actionlogs\Administrator\Helper\ActionlogsHelper;

/**
 * Minitek System Messages Plugin.
 *
 * @since  3.0.1
 */
class plgSystemMinitekSystemMessages extends JPlugin
{
	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  3.0.1
	 */
	var $app;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.0.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe.
	 * @param   array   $config    An optional associative array of configuration settings.
	 *
	 * @since   3.0.1
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->app = Factory::getApplication();
		$this->user = Factory::getUser();
	}

	/**
	 * After Initialise Event.
	 * Checks for ajax requests and server-sent events.
	 *
	 * @return  void
	 *
	 * @since   3.0.1
	 */
	public function onAfterInitialise()
	{
		$jinput = $this->app->input;

		// Check session expiration
		if (
			$jinput->get('group', '') === 'system' &&
			$jinput->get('plugin', '') === 'miniteksystemmessages' &&
			$jinput->get('type', '') === 'checkSession'
		) {
			if (!$this->user->id) {
				$session_message = array(
					array('message' => Text::_('PLG_SYSTEM_MINITEKSYSTEMMESSAGES_USER_SESSION_EXPIRED'), 'type' => 'warning')
				);

				jexit(json_encode($session_message));
			} else {
				jexit(false);
			}
		}

		// Get actions log notifications
		if (
			$jinput->get('group', '') === 'system' &&
			$jinput->get('plugin', '') === 'miniteksystemmessages' &&
			$jinput->get('type', '') === 'actionsLogEvent'
		) {
			Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

			// Redundant check for unauthorized access
			if (($this->app->isClient('site') && $this->params->get('enable_actions_log', false) &&
					array_intersect($this->user->getAuthorisedViewLevels(), $this->params->get('actions_log_access', [], 'ARRAY'))) ||
				($this->app->isClient('administrator') && $this->params->get('enable_actions_log', false) && $this->params->get('enable_backend', false) &&
					array_intersect($this->user->getAuthorisedViewLevels(), $this->params->get('actions_log_access', [], 'ARRAY')))
			) {
				$this->getActionsLogEvent();
			}
		}

		// Get logged in users
		if (
			$jinput->get('group', '') === 'system' &&
			$jinput->get('plugin', '') === 'miniteksystemmessages' &&
			$jinput->get('type', '') === 'loggedUsersEvent'
		) {
			Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

			// Redundant check for unauthorized access
			if (($this->app->isClient('site') && $this->params->get('enable_logged_users', false) &&
					array_intersect($this->user->getAuthorisedViewLevels(), $this->params->get('logged_users_access', [], 'ARRAY'))) ||
				($this->app->isClient('administrator') && $this->params->get('enable_logged_users', false) && $this->params->get('enable_backend', false) &&
					array_intersect($this->user->getAuthorisedViewLevels(), $this->params->get('logged_users_access', [], 'ARRAY')))
			) {
				$this->getLoggedUsersEvent();
			}
		}
	}

	/**
	 * Gets actions log notifications via server-sent event.
	 *
	 * @return   void
	 *
	 * @since   3.0.1
	 */
	public function getActionsLogEvent()
	{
		// Make session read-only
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}

		session_write_close();

		// Disable default disconnect checks
		ignore_user_abort(true);

		// Set headers for stream
		header("Content-Type: text/event-stream");
		header("Cache-Control: no-cache");
		header("Access-Control-Allow-Origin: *");
		header('X-Accel-Buffering: no');

		echo ":" . str_repeat(" ", 2048) . "\n"; // 2 kB padding
		echo "retry: 2000\n";

		// Get row id of most recent action in log
		$rows = $this->getActionsLog(1);

		if ($rows && isset($rows[0])) {
			$lastId = (int)$rows[0]->id;
		} else {
			jexit();
		}

		// Start stream
		while (true) {
			if (connection_aborted()) {
				exit();
			} else {
				// Search for new records after $lastId
				$rows = $this->getActionsLog(false, $lastId);
				$found = $rows && isset($rows[0]->id);

				if ($found) {
					// Show last record in array
					$last_index = count($rows) - 1;
					echo ":" . str_repeat(" ", 2048) . "\n"; // 2 kB padding
					echo 'data: {"msg": "' . $rows[$last_index]->message . '", "type": "info"}';
					echo "\n\n";

					// Update $lastId with last record in array
					$lastId = $rows[$last_index]->id;

					ob_flush();
					flush();
				} else {
					// No new data to send
					echo ":" . str_repeat(" ", 2048) . "\n"; // 2 kB padding
					echo ": heartbeat\n\n";
					ob_flush();
					flush();
				}
			}

			// 3 second sleep then carry on
			sleep(3);
		}
	}

	/**
	 * Process extension names.
	 *
	 * @param   string   $extension  Extension name.
	 *
	 * @return  string
	 *
	 * @since   3.0.1
	 */
	public static function processExtensionNames($extension)
	{
		if ($extension == 'com_plugins') {
			$extension = 'com_plugins.plugin';
		}

		return $extension;
	}

	/**
	 * Get a list of actions log records.
	 *
	 * @param   int  $count		The number of records to retrieve.
	 * @param   int  $id  		Get records after this id.
	 *
	 * @return  mixed  An array of records, or false on error.
	 *
	 * @since   3.0.1
	 */
	public function getActionsLog($count = false, $id = false)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('a.*, u.name')
			->from('#__action_logs AS a')
			->leftJoin('#__users AS u ON a.user_id = u.id');

		if ($id) {
			$query->where('a.id > ' . $id);
		}

		// Filter by extensions
		$extensions = $this->params->get('actions_log_extensions', [], 'ARRAY');

		$comma_separated = false;

		if (!empty($extensions) && !in_array('all', $extensions)) {
			$extensions = array_map('static::processExtensionNames', $extensions);
			$comma_separated = implode("','", $extensions);
			$comma_separated = "'" . $comma_separated . "'";
		}

		if ($comma_separated) {
			$query->where($db->quoteName('a.extension') . ' IN (' . $comma_separated . ')');
		}

		$query->order('a.id DESC');
		$db->setQuery($query, 0, $count);
		$rows = $db->loadObjectList();

		// Load all actionlog plugins language files
		ActionlogsHelper::loadActionLogPluginsLanguage();

		// Load plg_user_terms language file
		$lang = Factory::getLanguage();
		$lang->load('plg_user_terms', JPATH_ADMINISTRATOR);

		foreach ($rows as $row) {
			$row->message = static::getHumanReadableLogMessage($row);
		}

		return $rows;
	}

	/**
	 * Get human readable log message for a User Action Log.
	 *
	 * @param   stdClass  $log            A User Action log message record
	 * @param   boolean   $generateLinks  Flag to disable link generation when creating a message
	 *
	 * @return  string
	 *
	 * @since   3.0.1
	 */
	public static function getHumanReadableLogMessage($log)
	{
		static $links = array();

		$message = Text::_($log->message_language_key);

		// Remove links from formatted message
		$message = str_replace('<a href="{accountlink}">{username}</a>', '<b>{username}</b>', $message);
		$message = str_replace("<a href='{accountlink}'>{username}</a>", '<b>{username}</b>', $message);
		$message = str_replace('<a href="{itemlink}">{title}</a>', '<b>{title}</b>', $message);
		$message = str_replace("<a href='{itemlink}'>{title}</a>", '<b>{title}</b>', $message);

		$messageData = json_decode($log->message, true);

		// Special handling for translation extension name
		if (isset($messageData['extension_name'])) {
			ActionlogsHelper::loadTranslationFiles($messageData['extension_name']);
			$messageData['extension_name'] = Text::_($messageData['extension_name']);
		}

		// Translating application
		if (isset($messageData['app'])) {
			$messageData['app'] = Text::_($messageData['app']);
		}

		// Translating type
		if (isset($messageData['type'])) {
			$messageData['type'] = Text::_($messageData['type']);
		}

		$linkMode = Factory::getApplication()->get('force_ssl', 0) >= 1 ? JRoute::TLS_FORCE : JRoute::TLS_IGNORE;

		foreach ($messageData as $key => $value) {
			$message = str_replace('{' . $key . '}', $value, $message);
		}

		return $message;
	}

	/**
	 * Gets logged in users via server-sent event.
	 *
	 * @return   void
	 *
	 * @since   3.0.2
	 */
	public function getLoggedUsersEvent()
	{
		// Make session read-only
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}

		session_write_close();

		// Disable default disconnect checks
		ignore_user_abort(true);

		// Set headers for stream
		header("Content-Type: text/event-stream");
		header("Cache-Control: no-cache");
		header("Access-Control-Allow-Origin: *");
		header('X-Accel-Buffering: no');

		echo ":" . str_repeat(" ", 2048) . "\n"; // 2 kB padding
		echo "retry: 2000\n";

		// Initial count of logged in users
		$rows = $this->getUserSessions();
		$lastCount = count($rows);

		// Start stream
		while (true) {
			if (connection_aborted()) {
				exit();
			} else {
				$rows = $this->getUserSessions();

				// Show count if different from last count
				if ($lastCount != count($rows)) {
					echo ":" . str_repeat(" ", 2048) . "\n"; // 2 kB padding
					echo 'data: {"msg": "' . Text::_('PLG_SYSTEM_MINITEKSYSTEMMESSAGES_TOTAL_LOGGED_IN_USERS') . ' ' . count($rows) . '"}';
					echo "\n\n";

					$lastCount = count($rows);

					ob_flush();
					flush();
				} else {
					// No new data to send
					echo ":" . str_repeat(" ", 2048) . "\n"; // 2 kB padding
					echo ": heartbeat\n\n";
					ob_flush();
					flush();
				}
			}

			// 5 second sleep then carry on
			sleep(5);
		}
	}

	/**
	 * Get a list of user session records.
	 *
	 * @param   int  $count		The number of records to retrieve.
	 * @param   int  $time  	Get records after this time.
	 *
	 * @return  mixed  An array of records, or false on error.
	 *
	 * @since   3.0.2
	 */
	public function getUserSessions()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('a.time')
			->from('#__session AS a')
			->where('a.client_id = 0')
			->where('a.userid > 0');

		$db->setQuery($query, 0);
		$rows = $db->loadObjectList();

		return $rows;
	}

	/**
	 * Before Render Event.
	 * Loads assets.
	 *
	 * @return   void
	 *
	 * @since   3.0.1
	 */
	public function onBeforeRender()
	{
		if ($this->app->isClient('site') || ($this->app->isClient('administrator') && $this->params->get('enable_backend', false))) {
			$this->loadAssets();
		}
	}

	/**
	 * Method to notify for user login success
	 *
	 * @param   array  $options  Array holding options (user, responseType)
	 *
	 * @return  void
	 *
	 * @since   3.0.1
	 */
	public function onUserAfterLogin($options)
	{
		// Login message
		if ($this->app->isClient('site') && $this->params->get('enable_login_message', false)) {
			$this->app->enqueueMessage(Text::_('PLG_SYSTEM_MINITEKSYSTEMMESSAGES_SUCCESSFUL_LOGIN'), 'message');
		}
	}

	/**
	 * Loads css and js.
	 *
	 * @return   void
	 *
	 * @since   3.0.1
	 */
	public function loadAssets()
	{
		$document = Factory::getDocument();

		// Hide system-message-container 
		$hide_container_css = '#system-message-container {
			display: none;
		}';

		$document->addStyleDeclaration($hide_container_css);

		// Parse application messages
		$messages = $this->app->getMessageQueue();

		if (!empty($messages) && count($messages)) {
			$application_messages = $messages;

			// Group messages by type
			if ($this->params->get('group_messages', false)) {
				$merged_messages = array();

				foreach ($application_messages as $message) {
					if (isset($merged_messages[$message['type']])) {
						$temp = $merged_messages[$message['type']];
						$temp['message'] .= '<div class="alert-message">' . $message['message'] . '</div>';
					} else {
						$temp = $message;
						$temp['message'] = '<div class="alert-message">' . $message['message'] . '</div>';
					}

					$merged_messages[$message['type']] = $temp;
				}

				$application_messages = array_values($merged_messages);
			} else {
				// Wrap each message in div.alert-message
				foreach ($application_messages as &$message) {
					$message['message'] = '<div class="alert-message">' . $message['message'] . '</div>';
				}
			}
		} else {
			$application_messages = [];
		}

		// Session expiration
		$session_message = $this->params->get('enable_session_expiration', true) ? true : false;
		$session_redirect_link = false;

		if ($this->app->isClient('site') && $session_redirect = $this->params->get('redirect_session_expiration', '', 'INT')) {
			$menu_item = $this->app->getMenu()->getItem($session_redirect);
			$session_redirect_link = JRoute::_($menu_item->link . '&Itemid=' . $menu_item->id, false);
		}

		// Actions log
		if (($this->app->isClient('site') && $this->params->get('enable_actions_log', false) &&
				array_intersect($this->user->getAuthorisedViewLevels(), $this->params->get('actions_log_access', [], 'ARRAY'))) ||
			($this->app->isClient('administrator') && $this->params->get('enable_actions_log', false) && $this->params->get('enable_backend', false) &&
				array_intersect($this->user->getAuthorisedViewLevels(), $this->params->get('actions_log_access', [], 'ARRAY')))
		) {
			$actions_log = true;
		} else {
			$actions_log = false;
		}

		// Logged in users
		if (($this->app->isClient('site') && $this->params->get('enable_logged_users', false) &&
				array_intersect($this->user->getAuthorisedViewLevels(), $this->params->get('logged_users_access', [], 'ARRAY'))) ||
			($this->app->isClient('administrator') && $this->params->get('enable_logged_users', false) && $this->params->get('enable_backend', false) &&
				array_intersect($this->user->getAuthorisedViewLevels(), $this->params->get('logged_users_access', [], 'ARRAY')))
		) {
			$logged_users = true;
		} else {
			$logged_users = false;
		}

		// Load css
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->registerAndUseStyle('plg_system_miniteksystemmessages', 'plg_system_miniteksystemmessages/miniteksystemmessages.css');

		// Polipop options 
		$appendTo = $this->params->get('appendTo', 'body');
		$position = $this->params->get('position', 'center');
		$layout = $this->params->get('layout', 'popups');
		$theme = $this->params->get('theme', 'default');
		$icons = $this->params->get('icons', 1) ? true : false;
		$insert = $this->params->get('insert', 'before');
		$spacing = $this->params->get('spacing', 10);
		$pool = $this->params->get('pool', 0);
		$sticky = $this->params->get('sticky', 0) ? true : false;
		$progressbar = $this->params->get('progressbar', 1) ? true : false;
		$pauseOnHover = $this->params->get('pauseOnHover', 1) ? true : false;
		$closer = $this->params->get('closer', 1) ? true : false;
		$hideEmpty = $this->params->get('hideEmpty', 0) ? true : false;
		$effect = $this->params->get('effect', 'fade');
		$easing = $this->params->get('easing', 'linear');
		$effectDuration = $this->params->get('effectDuration', 250);

		// Load Polipop
		$wa->registerAndUseStyle('plg_system_miniteksystemmessages.polipop.core', 'plg_system_miniteksystemmessages/polipop.core.css');

		if ($theme === 'default')
			$wa->registerAndUseStyle('plg_system_miniteksystemmessages.polipop.default', 'plg_system_miniteksystemmessages/polipop.default.css');
		else if ($theme === 'compact')
			$wa->registerAndUseStyle('plg_system_miniteksystemmessages.polipop.default', 'plg_system_miniteksystemmessages/polipop.compact.css');
		else if ($theme === 'minimal')
			$wa->registerAndUseStyle('plg_system_miniteksystemmessages.polipop.default', 'plg_system_miniteksystemmessages/polipop.minimal.css');

		$wa->registerAndUseScript('plg_system_miniteksystemmessages.polipop', 'plg_system_miniteksystemmessages/polipop.js', [], ['defer' => true]);

		// Add script options
		$document->addScriptOptions('miniteksystemmessages', array(
			'token' => Session::getFormToken(),
			'site_path' => Uri::root(),
			'is_site' => $this->app->isClient('site') ? true : false,
			'user_id' => $this->user->id,
			'application_messages' => $application_messages,
			'session_message' => $session_message,
			'session_redirect_link' => $session_redirect_link,
			'lifetime' => Factory::getConfig()->get('lifetime'),
			'actions_log' => $actions_log,
			'logged_users' => $logged_users,
			'error_text' => Text::_('ERROR'),
			'success_text' => Text::_('MESSAGE'),
			'notice_text' => Text::_('NOTICE'),
			'warning_text' => Text::_('WARNING'),
			// Polipop options
			'appendTo' => $appendTo,
			'position' => $position,
			'layout' => $layout,
			'theme' => $theme,
			'icons' => $icons,
			'insert' => $insert,
			'spacing' => $spacing,
			'pool' => $pool,
			'sticky' => $sticky,
			'life' => $this->params->get('life', 3000),
			'progressbar' => $progressbar,
			'pauseOnHover' => $pauseOnHover,
			'headerText' => Text::_('PLG_SYSTEM_MINITEKSYSTEMMESSAGES_HEADER_TEXT'),
			'closer' => $closer,
			'closeText' => Text::_('PLG_SYSTEM_MINITEKSYSTEMMESSAGES_CLOSE_TEXT'),
			'loadMoreText' => Text::_('PLG_SYSTEM_MINITEKSYSTEMMESSAGES_LOAD_MORE_TEXT'),
			'hideEmpty' => $hideEmpty,
			'effect' => $effect,
			'easing' => $easing,
			'effectDuration' => $effectDuration
		));

		// Load js
		$wa->registerAndUseScript('plg_system_miniteksystemmessages', 'plg_system_miniteksystemmessages/miniteksystemmessages.js', [], ['defer' => true], ['core']);
	}
}
