<?php
/**
* @title				Minitek System Messages
* @copyright   	Copyright (C) 2011-2021 Minitek, All rights reserved.
* @license   		GNU General Public License version 3 or later.
* @author url   https://www.minitek.gr/
* @developers   Minitek.gr / Yannis Maragos
*/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class JFormFieldAsset extends JFormField
{
  protected $type = 'Asset';

  protected function getInput()
	{
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->registerAndUseStyle('plg_system_miniteksystemmessages', 'plg_system_miniteksystemmessages/admin-miniteksystemmessages.css');

		return null;
  }
}
