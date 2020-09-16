<?php
/**
* @title				Minitek System Messages
* @copyright   	Copyright (C) 2011-2020 Minitek, All rights reserved.
* @license   		GNU General Public License version 3 or later.
* @author url   https://www.minitek.gr/
* @developers   Minitek.gr / Yannis Maragos
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class JFormFieldAsset extends JFormField
{
  protected $type = 'Asset';

  protected function getInput()
	{
    $document = Factory::getDocument();
    $document->addStyleSheet(Uri::root().$this->element['path'].'style.css');

		return null;
  }
}
