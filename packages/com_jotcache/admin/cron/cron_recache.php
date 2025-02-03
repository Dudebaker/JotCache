<?php
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	/**
	 * PURPOSE
	 * cron_recache.php file is cron job starting point for JotCache RECACHE execution.
	 * Here are prepared DB and logging tasks in the class CronRecache
	 * as well as registration and triggering jotcache plugins which are processing
	 * the `onJotcacheRecache` event.
	 */
	
	/**
	 * Joomla security named constant
	 */
	
	use Joomla\CMS\Factory;
	
	define('_JEXEC', 1);
	
	/***************************
	 * Settings for cron job run :
	 * 1. Move and rename this file cron_recache.php to the directory outside site public access.
	 * 2. The constant 'JPATH_BASE' have to contain full server path to the server file directory
	 *    - the root of Joomla installation
	 * 3. Set constant 'JOTCACHE_ROOT_URL' which shall point to Joomla root URL
	 * 4. Set cron job run conditions with following settings :
	 *    'JOTCACHE_PLUGIN_NAME' string
	 *         - have to contain name of processed jotcacheplugin
	 *           (in JotCache core are two plugins available - recache, crawler)
	 *    $JotcachePluginParams  array
	 *         - for future usage in custom jotcacheplugins
	 *    $JotcachePluginStates  array
	 *         - attributes for controlling the script run
	 *    $JotcacheRecacheWhere
	 *         - database WHERE conditions glued with 'AND'
	 *           applied on table #__jotcache
	 *           available column names (displayed also in JotCache Management View) :
	 *           com,view,id,ftime,title,uri,language,browser
	 *           !!USED only for plugins performing recache based on previous database content!!
	 ***************************/
	define('JPATH_BASE', '/.....');
	// Use ONLY for testing - when this file located in original directory :
	//define('JPATH_BASE', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
	// echo JPATH_BASE;
	define('JOTCACHE_ROOT_URL', 'http://localhost/joomla32');
	define('JOTCACHE_PLUGIN_NAME', 'crawler');
	//define('JOTCACHE_PLUGIN_NAME', 'recache');
	$JotcachePluginParams = [];
	$JotcachePluginStates = ['depth' => '2'];
	//$JotcacheRecacheWhere = array("view='article'","id=42");
	$JotcacheRecacheWhere = [];
	/***************************/
	
	require_once JPATH_BASE . '/includes/defines.php';
	require_once JPATH_BASE . '/includes/framework.php';
	require_once JPATH_BASE . '/administrator/components/com_jotcache/helpers/recacherunner.php';
	
	try
	{
		$tmp = php_sapi_name();
		if (php_sapi_name() != 'cli')
		{
			throw new RuntimeException('cron script is not called with CLI');
		}
		$main = new RecacheRunner();
		if (!is_object($main))
		{
			throw new RuntimeException('RecacheRunner object was not created');
		}
		$db = $main->getDbo();
		if (!is_object($db))
		{
			throw new RuntimeException('database object was not created');
		}
		$query = $db->getQuery(true);
		$query->clear();
		$query->update($db->quoteName('#__jotcache'))
		      ->set('recache=1');
		foreach ($JotcacheRecacheWhere as $jcwhere)
		{
			$query->where($jcwhere);
		}
		$cursor = $db->setQuery($query)->execute();
		if ($cursor === false)
		{
			throw new RuntimeException('database query on #__jotcache table not successfull');
		}
		$config   = Factory::getApplication()?->getConfig();
		$cacheDir = $config->get('cache_path', JPATH_ROOT . '/cache') . '/page';
		//  $cacheDir = JPATH_ROOT . '/cache/page';
		if (!file_exists($cacheDir))
		{
			if (!mkdir($cacheDir, 0755) && !is_dir($cacheDir))
			{
				throw new RuntimeException(sprintf('Directory "%s" was not created', $cacheDir));
			}
		}
		
		$flagPath  = $cacheDir . '/jotcache_recache_flag_tmp.php';
		$num_bytes = file_put_contents($flagPath, "defined('_JEXEC') or die;", LOCK_EX);
		if ($num_bytes === false)
		{
			throw new RuntimeException('cannot write into /cache/page directory');
		}
		$main->doExecute(JOTCACHE_ROOT_URL, JOTCACHE_PLUGIN_NAME, $JotcachePluginParams, $JotcachePluginStates);
		if (file_exists($flagPath))
		{
			unlink($flagPath);
		}
	}
	catch (Exception $e)
	{
		// An exception has been caught, echo the message.
		exit('[JotCache recache] cron script error : ' . $e->getMessage());
	}
