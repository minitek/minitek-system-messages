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
	 * Method to notify for user login success.
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
		$pauseOnHover = $this->params->get('pauseOnHover', 1) ? true : false;
		$closer = $this->params->get('closer', 1) ? true : false;
		$hideEmpty = $this->params->get('hideEmpty', 0) ? true : false;
		$effect = $this->params->get('effect', 'fade');
		$easing = $this->params->get('easing', 'linear');
		$effectDuration = $this->params->get('effectDuration', 250);

		// Colors 
		$message_color = $this->params->get('message_color', '#0ec47d');
		$message_text = $this->params->get('message_text', '#ffffff');
		$info_color = $this->params->get('info_color', '#00b1fe');
		$info_text = $this->params->get('info_text', '#ffffff');
		$warning_color = $this->params->get('warning_color', '#ffc107');
		$warning_text = $this->params->get('warning_text', '#555555');
		$error_color = $this->params->get('error_color', '#f76860');
		$error_text = $this->params->get('error_text', '#ffffff');
		$css = '';

		if ($theme === 'default' || $theme === 'compact') {
			$css .= '
			.polipop_theme_default .polipop__notification_type_success,
			.polipop_theme_compact .polipop__notification_type_success {
				background-color: ' . $message_color . ';
				color: ' . $message_text . ';
			}
			.polipop_theme_default .polipop__notification_type_success .polipop__notification-icon svg,
			.polipop_theme_compact .polipop__notification_type_success .polipop__notification-icon svg {
				fill: ' . $message_text . ';
			}
			.polipop_theme_default .polipop__notification_type_info,
			.polipop_theme_compact .polipop__notification_type_info {
				background-color: ' . $info_color . ';
				color: ' . $info_text . ';
			}
			.polipop_theme_default .polipop__notification_type_info .polipop__notification-icon svg,
			.polipop_theme_compact .polipop__notification_type_info .polipop__notification-icon svg {
				fill: ' . $info_text . ';
			}
			.polipop_theme_default .polipop__notification_type_warning,
			.polipop_theme_compact .polipop__notification_type_warning {
				background-color: ' . $warning_color . ';
				color: ' . $warning_text . ';
			}
			.polipop_theme_default .polipop__notification_type_warning .polipop__notification-icon svg,
			.polipop_theme_compact .polipop__notification_type_warning .polipop__notification-icon svg {
				fill: ' . $warning_text . ';
			}
			.polipop_theme_default .polipop__notification_type_error,
			.polipop_theme_compact .polipop__notification_type_error {
				background-color: ' . $error_color . ';
				color: ' . $error_text . ';
			}
			.polipop_theme_default .polipop__notification_type_error .polipop__notification-icon svg,
			.polipop_theme_compact .polipop__notification_type_error .polipop__notification-icon svg {
				fill: ' . $error_text . ';
			}
			';
		}

		$document->addStyleDeclaration($css);

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
			'pauseOnHover' => $pauseOnHover,
			'headerText' => Text::_('PLG_SYSTEM_MINITEKSYSTEMMESSAGES_HEADER_TEXT'),
			'closer' => $closer,
			'closeText' => Text::_('PLG_SYSTEM_MINITEKSYSTEMMESSAGES_CLOSE_TEXT'),
			'loadMoreText' => Text::_('PLG_SYSTEM_MINITEKSYSTEMMESSAGES_LOAD_MORE_TEXT'),
			'effect' => $effect,
			'easing' => $easing,
			'effectDuration' => $effectDuration
		));

		// Load js
		$wa->registerAndUseScript('plg_system_miniteksystemmessages', 'plg_system_miniteksystemmessages/miniteksystemmessages.js', [], ['defer' => true], ['core']);
	}
}
