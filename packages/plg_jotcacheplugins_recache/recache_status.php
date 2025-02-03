<?php
	/**
	 * @package         JotCache
	 * @subpackage      JotCachePlugins.Recache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\Language\Text;
	use Joomla\Database\DatabaseInterface;
	
	defined('_JEXEC') or die('Restricted access');
	$lang = Factory::getApplication()->getLanguage();
	$lang->load('plg_jotcacheplugins_recache', JPATH_ADMINISTRATOR, null, false, true);
	$database = Factory::getContainer()->get(DatabaseInterface::class);
	$sql      = $database->getQuery(true);
	$sql->select('COUNT(*)')
	    ->from('#__jotcache')
	    ->where($database->quoteName('recache') . ' = ' . $database->quote(1));
	$database->setQuery($sql);
	$total = $database->loadResult();
	echo sprintf(Text::_('PLG_JCPLUGINS_RECACHE_STATUS'), $total);
