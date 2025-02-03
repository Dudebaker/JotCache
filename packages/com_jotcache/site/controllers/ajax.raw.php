<?php
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\MVC\Controller\BaseController;
	use Joomla\Database\DatabaseInterface;
	
	defined('_JEXEC') or die;
	
	class JotcacheControllerAjax extends BaseController
	{
		public function __construct($config = [])
		{
			parent::__construct($config);
		}
		
		public function status()
		{
			$flag = $this->input->getWord('flag', '');
			if ($flag == 'stop')
			{
				$this->controlRecache(0);
			}
			else
			{
				$plugin = strtolower($this->input->getWord('plugin'));
				include JPATH_PLUGINS . '/jotcacheplugins/' . $plugin . '/' . $plugin . '_status.php';
			}
		}
		
		protected function controlRecache($flag)
		{
			$cacheDir = JPATH_ROOT . '/cache/page';
			if (!file_exists($cacheDir))
			{
				if (!mkdir($cacheDir, 0755) && !is_dir($cacheDir))
				{
					throw new \RuntimeException(sprintf('Directory "%s" was not created', $cacheDir));
				}
			}
			$flagPath = $cacheDir . '/jotcache_recache_flag_tmp.php';
			if ($flag)
			{
				file_put_contents($flagPath, "defined('_JEXEC') or die;", LOCK_EX);
			}
			else
			{
				if (file_exists($flagPath))
				{
					unlink($flagPath);
					$db  = Factory::getContainer()->get(DatabaseInterface::class);
					$sql = $db->getQuery(true);
					$sql->update($db->quoteName('#__jotcache'))
					    ->set($db->quoteName('recache') . ' = ' . $db->quote(0));
					$db->setQuery($sql)->execute();
				}
			}
		}
	}