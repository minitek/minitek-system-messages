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

class JFormFieldSeparator extends JFormField
{
	protected $type = 'Separator';

	protected function getLabel()
	{
		return;
	}

	protected function getInput()
	{
		$text = (string)$this->element['text'];

		return '<div id="'.$this->id.'" class="mmSeparator'.(($text != '') ? ' hasText' : '').'" title="'. Text::_($this->element['desc']) .'"><span>' . JText::_($text) . '</span></div>';
	}
}
