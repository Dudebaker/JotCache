<?php /** @noinspection AccessModifierPresentedInspection */
	
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Component\ComponentHelper;
	use Joomla\CMS\Factory;
	use Joomla\CMS\Language\Text;
	use Joomla\CMS\MVC\Model\BaseDatabaseModel;
	use Joomla\CMS\Uri\Uri;
	
	defined('_JEXEC') or die;
	require_once JPATH_ADMINISTRATOR . '/components/com_jotcache/helpers/recacherunner.php';
	
	class MainModelRecache extends BaseDatabaseModel
	{
		var $_db;
		var $_sql = "";
		var $stopped = true;
		
		function __construct()
		{
			parent::__construct();
		}
		
		function runRecache()
		{
			$app     = Factory::getApplication();
			$params  = ComponentHelper::getParams("com_jotcache");
			$timeout = (int) $params->get('recachetimeout', 300);
			register_shutdown_function([$this, 'recacheShutdown']);
			ini_set('max_execution_time', $timeout);
			$scopeAllow = ['none', 'chck', 'sel', 'all', 'direct'];
			$scope      = $app->input->getWord('scope', '');
			if (in_array($scope, $scopeAllow, true))
			{
				$this->_sql = $this->_db->getQuery(true);
				switch ($scope)
				{
					case 'none':
						return;
					case 'chck':
						$this->checkedRecache();
						break;
					case 'sel':
						$this->selectedRecache($app);
						break;
					default:
						break;
				}
				if ($scope != 'direct')
				{
					$this->_sql->update($this->_db->quoteName('#__jotcache'))
					           ->set($this->_db->quoteName('recache') . ' = ' . $this->_db->quote(1));
					$sql = $this->_db->setQuery($this->_sql);
					$sql->execute();
				}
				$this->controlRecache(1);
				define('JOTCACHE_RECACHE_BROWSER', true);
				$this->executeRunner($app);
			}
			$this->stopped = false;
		}
		
		private function checkedRecache() : void
		{
			$this->_sql->update($this->_db->quoteName('#__jotcache'))
			           ->set($this->_db->quoteName('recache') . ' = ' . $this->_db->quote(1))
			           ->set($this->_db->quoteName('recache_chck') . ' = ' . $this->_db->quote(0))
			           ->where("recache_chck='1'");
			$sql = $this->_db->setQuery($this->_sql);
			$sql->execute();
		}
		
		private function selectedRecache($app) : void
		{
			$input  = $app->input;
			$search = $input->getString('search');
			$com    = $input->getCmd('com');
			$view   = $input->getCmd('pview');
			$mark   = $input->getInt('mark');
			$params = ComponentHelper::getParams("com_jotcache");
			$mode   = (bool) $params->get('mode');
			if ($com)
			{
				$this->_sql->where("com='$com'");
			}
			if ($view)
			{
				$part = $this->_db->quoteName('view');
				$this->_sql->where("$part='$view'");
			}
			if ($mark)
			{
				$this->_sql->where("mark='$mark'");
			}
			if ($search)
			{
				if ($mode)
				{
					$this->_sql->where('LOWER(uri) LIKE ' . $this->_db->quote('%' . $this->_db->escape($search, true) . '%', false));
				}
				else
				{
					$this->_sql->where('LOWER(title) LIKE ' . $this->_db->quote('%' . $this->_db->escape($search, true) . '%', false));
				}
			}
		}
		
		public function controlRecache($flag) : void
		{
			$config   = Factory::getApplication()?->getConfig();
			$cacheDir = $config->get('cache_path', JPATH_ROOT . '/cache') . '/page';
			if (!file_exists($cacheDir))
			{
				if (!mkdir($cacheDir, 0755) && !is_dir($cacheDir))
				{
					throw new RuntimeException(sprintf('Directory "%s" was not created', $cacheDir));
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
					$this->_sql = $this->_db->getQuery(true);
					$this->_sql->update($this->_db->quoteName('#__jotcache'))
					           ->set($this->_db->quoteName('recache') . ' = ' . $this->_db->quote(0));
					$this->_db->setQuery($this->_sql)->execute();
				}
			}
		}
		
		private function executeRunner($app) : void
		{
			$main     = new RecacheRunner();
			$input    = $app->input;
			$jcplugin = strtolower($input->getWord('jotcacheplugin'));
			$jcparams = $input->get->get('jcparams', [], 'array');
			$states   = $input->post->get('jcstates', [], 'array');
			$jcstates = [];
			if (count($states) > 0)
			{
				foreach ($states as $key => $value)
				{
					$curState = $app->getUserState('jotcache.' . $jcplugin . '.' . $key, null);
					$newState = $states[$key];
					if ($curState !== $newState)
					{
						$jcstates[$key] = $newState;
						$app->setUserState('jotcache.' . $jcplugin . '.' . $key, $newState);
					}
					else
					{
						$jcstates[$key] = $curState;
					}
				}
			}
			$starturl = URI::root();
			if (substr($starturl, -1) == '/')
			{
				$starturl = substr($starturl, 0, strlen($starturl) - 1);
			}
			$main->doExecute($starturl, $jcplugin, $jcparams, $jcstates);
		}
		
		public function recacheShutdown() : void
		{
			if ($this->stopped)
			{
				echo Text::_('JOTCACHE_RECACHE_SHUTDOWN'), PHP_EOL;
			}
			else
			{
				echo Text::_('JOTCACHE_RECACHE_NORMAL'), PHP_EOL;
			}
		}
		
		public function flagRecache($cids) : void
		{
			$list       = implode("','", $cids);
			$this->_sql = $this->_db->getQuery(true);
			$this->_sql->update($this->_db->quoteName('#__jotcache'))
			           ->set($this->_db->quoteName('recache_chck') . ' = ' . $this->_db->quote(1))
			           ->where("fname IN ('$list')");
			$this->_db->setQuery($this->_sql)->execute();
		}
		
		public function getPlugins()
		{
			$query = $this->_db->getQuery(true);
			$query->select('p.*')
			      ->from('#__extensions AS p')
			      ->where('p.enabled = 1')
			      ->where('p.type = ' . $this->_db->quote('plugin'))
			      ->where('p.folder = ' . $this->_db->quote('jotcacheplugins'))
			      ->order('p.ordering');
			$this->_db->setQuery($query);
			$plugins = $this->_db->loadObjectList();
			
			return $plugins;
		}
	}