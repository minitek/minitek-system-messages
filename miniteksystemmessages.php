<?php
/**
* @title		Minitek System Messages
* @copyright   	Copyright (C) 2011-2020 Minitek, All rights reserved.
* @license   	GNU General Public License version 3 or later.
* @author url   https://www.minitek.gr/
* @developers   Minitek.gr / Yannis Maragos
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\String\StringHelper;

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
	 * Before Render Event.
	 * Loads assets.
	 *
	 * @return   void
	 *
	 * @since   3.0.1
	 */
	public function onBeforeRender()
	{
		if (($this->app->isClient('administrator') && $this->params->get('enable_backend', false)) ||
			($this->app->isClient('administrator') && $this->params->get('enable_validation_backend', false)) ||
			($this->app->isClient('site') && $this->params->get('enable_frontend', true)) ||
			($this->app->isClient('site') && $this->params->get('enable_validation_frontend', true)) ||
			(\JComponentHelper::isInstalled('com_contentnotifications')))
		{
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
		if ($this->app->isClient('site') && $this->params->get('login_message', false))
		{
			$this->app->enqueueMessage(Text::_('PLG_SYSTEM_MINITEK_SYSTEM_MESSAGES_SUCCESSFUL_LOGIN'), 'message');
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

		$is_site = $this->app->isClient('site') ? 'true' : 'false';

		// Application messages
		if (($this->app->isClient('administrator') && $this->params->get('enable_backend', false)) ||
			($this->app->isClient('site') && $this->params->get('enable_frontend', true)))
		{
			$application_messages = $this->app->getMessageQueue();

			if (!empty($application_messages))
			{
				$hide_container_css = '#system-message-container {
					display: none;
				}';
				$document->addStyleDeclaration($hide_container_css);
			}
		}
		else
		{
			// If there are no messages, application messages will be disabled in js
			$application_messages = false;
		}

		if (empty($application_messages) || !count($application_messages))
		{
			$application_messages = 'false';
		}
		else if ($application_messages)
		{
			// Group messages by type
			if ($this->params->get('group_messages', false))
			{
				$merged_messages = array();

				foreach ($application_messages as $message)
				{
					if (isset($merged_messages[$message['type']]))
					{
						$temp = $merged_messages[$message['type']];
						$temp['message'] .= '<br>'.$message['message'];
						$merged_messages[$message['type']] = $temp;
					}
					else
					{
						$merged_messages[$message['type']] = $message;
					}
				}

				$application_messages = array_values($merged_messages);
			}

			$application_messages = "'".addslashes(json_encode($application_messages))."'";
		}

		// Form validation messages
		if (($this->app->isClient('administrator') && $this->params->get('enable_validation_backend', false)) ||
			($this->app->isClient('site') && $this->params->get('enable_validation_frontend', true)))
		{
			$validation_messages = 'true';
		}
		else
		{
			$validation_messages = 'false';
		}

		// Load css
		$document->addStyleSheet(Uri::root(true).'/plugins/system/miniteksystemmessages/assets/css/style.css');

		$layout_mode = 'popups';

		// Popups params
		if ($layout_mode == 'popups')
		{
			$theme = $this->params->get('mg_theme', 'default');
			$closer = $this->params->get('mg_closer', 1);
			$pool = $this->params->get('mg_pool', 0);
			$sticky = $this->params->get('mg_sticky', 0);
			$position = $this->params->get('mg_position', 'center');
			$glue = $this->params->get('mg_glue', 'after');
			$closeDuration = $this->params->get('mg_closeduration', 500);
			$openDuration = $this->params->get('mg_openduration', 500);
			$group_messages = $this->params->get('group_messages', 0);
			$on_new_message = '';

			// Colors
			// Message
			$message_background = $this->params->get('message_background', '#28c88a');
			$message_color = $this->params->get('message_color', '#ffffff');
			$message_icon = $this->params->get('message_icon', '#ffffff');
			$message_border = $this->params->get('message_border', '#2fad7f');

			if ($this->params->get('mg_theme', 'default') == 'default')
			{
				$message_css = '.msm-message {
					background-color: '.$message_background.';
					color: '.$message_color.';
					border-color: '.$message_border.';
				}
				.msm-message .mGrowl-header i.fa {
					color: '.$message_icon.';
				}';
			}
			else if ($this->params->get('mg_theme', 'default') == 'minimal')
			{
				$message_css = '.msm-message {
					background-color: '.$message_border.';
					color: '.$message_color.';
				}
				.msm-message .mGrowl-message {
					background-color: '.$message_background.';
				}
				.msm-message .mGrowl-header i.fa {
					color: '.$message_icon.';
				}';
			}

			$document->addStyleDeclaration($message_css);

			// Notice
			$notice_background = $this->params->get('notice_background', '#e1f4ff');
			$notice_color = $this->params->get('notice_color', '#4f90bf');
			$notice_icon = $this->params->get('notice_icon', '#4f90bf');
			$notice_border = $this->params->get('notice_border', '#87ccfa');

			if ($this->params->get('mg_theme', 'default') == 'default')
			{
				$notice_css = '.msm-notice {
					background-color: '.$notice_background.';
					color: '.$notice_color.';
					border-color: '.$notice_border.';
				}
				.mGrowl-notification.msm-alert.msm-notice .mGrowl-close {
					border-color: '.$notice_border.';
				}
				.msm-notice .mGrowl-header i.fa {
					color: '.$notice_icon.';
				}';
			}
			else if ($this->params->get('mg_theme', 'default') == 'minimal')
			{
				$notice_css = '.msm-notice {
					background-color: '.$notice_border.';
					color: '.$notice_color.';
				}
				.msm-notice .mGrowl-message {
					background-color: '.$notice_background.';
				}
				.msm-notice .mGrowl-header i.fa {
					color: '.$notice_icon.';
				}';
			}

			$document->addStyleDeclaration($notice_css);

			// Warning
			$warning_background = $this->params->get('warning_background', '#ed7248');
			$warning_color = $this->params->get('warning_color', '#ffffff');
			$warning_icon = $this->params->get('warning_icon', '#ffffff');
			$warning_border = $this->params->get('warning_border', '#d66437');

			if ($this->params->get('mg_theme', 'default') == 'default')
			{
				$warning_css = '.msm-warning {
					background-color: '.$warning_background.';
					color: '.$warning_color.';
					border-color: '.$warning_border.';
				}
				.msm-warning .mGrowl-header i.fa {
					color: '.$warning_icon.';
				}';
			}
			else if ($this->params->get('mg_theme', 'default') == 'minimal')
			{
				$warning_css = '.msm-warning {
					background-color: '.$warning_border.';
					color: '.$warning_color.';
				}
				.msm-warning .mGrowl-message {
					background-color: '.$warning_background.';
				}
				.msm-warning .mGrowl-header i.fa {
					color: '.$warning_icon.';
				}';
			}

			$document->addStyleDeclaration($warning_css);

			// Error
			$error_background = $this->params->get('error_background', '#eb6f57');
			$error_color = $this->params->get('error_color', '#ffffff');
			$error_icon = $this->params->get('error_icon', '#ffffff');
			$error_border = $this->params->get('error_border', '#f04124');

			if ($this->params->get('mg_theme', 'default') == 'default')
			{
				$error_css = '.msm-error {
					background-color: '.$error_background.';
					color: '.$error_color.';
					border-color: '.$error_border.';
				}
				.msm-error .mGrowl-header i.fa {
					color: '.$error_icon.';
				}';
			}
			else if ($this->params->get('mg_theme', 'default') == 'minimal')
			{
				$error_css = '.msm-error {
					background-color: '.$error_border.';
					color: '.$error_color.';
				}
				.msm-error .mGrowl-message {
					background-color: '.$error_background.';
				}
				.msm-error .mGrowl-header i.fa {
					color: '.$error_icon.';
				}';
			}

			$document->addStyleDeclaration($error_css);

			// [Close all] button
			$closer_background = $this->params->get('closer_background', '#333333');
			$closer_color = $this->params->get('closer_color', '#ffffff');
			$closer_border = $this->params->get('closer_border', '#000000');
			$closer_css = '.mGrowl .mGrowl-closer {
				background-color: '.$closer_background.';
				color: '.$closer_color.';
				border-color: '.$closer_border.';
			}';
			$document->addStyleDeclaration($closer_css);
		}

		// Load FontAwesome
		if ($this->params->get('load_fontawesome', true))
		{
			$document->addStyleSheet('https://netdna.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.css');
		}

		// Load jQuery
		JHtml::_('jquery.framework');

		// Load mGrowl
		$document->addStyleSheet(Uri::root(true).'/plugins/system/miniteksystemmessages/assets/css/jquery.mgrowl.css');
		$document->addScript(Uri::root(true).'/plugins/system/miniteksystemmessages/assets/js/jquery.mgrowl.js');

		// Add javascript variables
		$document->addScriptDeclaration('window.miniteksystemmessages = {
			close_all: "'.Text::_('PLG_SYSTEM_MINITEK_SYSTEM_MESSAGES_CLOSE_ALL_TEXT').'",
			mg_pool: "'.$pool.'",
			mg_closer: "'.$closer.'",
			mg_sticky: "'.$sticky.'",
			mg_theme: "'.$theme.'",
			mg_position: "'.$position.'",
			mg_glue: "'.$glue.'",
			mg_life: "'.$this->params->get('mg_life', 3000).'",
			mg_closeDuration: "'.$closeDuration.'",
			mg_openDuration: "'.$openDuration.'",
			group_messages: "'.$group_messages.'",
			joomla_container: "'.$this->params->get('joomla_container', 'system-message-container').'",
			application_messages: '.$application_messages.',
			validation_messages: '.$validation_messages.',
			error_text: "'.Text::_('ERROR').'",
			message_text: "'.Text::_('MESSAGE').'",
			notice_text: "'.Text::_('NOTICE').'",
			warning_text: "'.Text::_('WARNING').'",
		};');

		// Load js
		$document->addScript(Uri::root(true).'/plugins/system/miniteksystemmessages/assets/js/script.js');
	}
}
