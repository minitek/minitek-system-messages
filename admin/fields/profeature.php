<?php
/**
* @title		Minitek System Messages
* @copyright   	Copyright (C) 2011-2020 Minitek, All rights reserved.
* @license   	GNU General Public License version 3 or later.
* @author url   https://www.minitek.gr/
* @developers   Minitek.gr / Yannis Maragos
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

class JFormFieldProFeature extends JFormField
{
	public $type = 'ProFeature';
	private $params = null;

	protected function getInput()
	{
		$this->params = $this->element->attributes();
		$title = $this->get('title');
		$class = $this->get('class');

		$html = '<div class="alert alert-'.$class.'">
		<i class="fa fa-lock"></i>&nbsp;&nbsp;'.Text::_($title).'
		<a href="https://www.minitek.gr/joomla/extensions/minitek-system-messages">
		'.Text::_('PLG_SYSTEM_MINITEK_SYSTEM_MESSAGES_CONFIG_UPGRADE_TO_PRO').'
		</a>
		</div>';

		return $html;
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
