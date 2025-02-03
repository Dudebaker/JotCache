<?php
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	
	defined('_JEXEC') or die('Restricted access');
	
	class com_jotcacheInstallerScript
	{
		/**
		 * @throws \Exception
		 */
		public function uninstall() : void
		{
			if (count(Factory::getApplication()?->getMessageQueue()) > 0)
			{
				echo "Error condition - Uninstallation not successful! You have to manually remove com_jotcache from '.._extensions' table as well as to drop '.._jotcache' and '.._jotcache_exclude' tables";
			}
			else
			{
				echo "Uninstallation successful!";
			}
		}
	}