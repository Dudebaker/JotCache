<?php
	
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\Language\Text;
	use Joomla\Database\DatabaseInterface;
	
	class Pkg_JotcacheInstallerScript
	{
		private $app;
		
		public function preflight(
			$type,
			$parent)
		{
			$this->app = Factory::getApplication();
		}
		
		public function install()
		{
			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true);
			$query->update($db->quoteName('#__extensions'))
			      ->set('enabled=1')
			      ->where($db->quoteName('element') . ' = ' . $db->quote('jotmarker'));
			try
			{
				$db->setQuery($query)->execute();
			}
			catch (RuntimeException $ex)
			{
				$this->app->enqueueMessage($ex->getMessage(), 'error');
			}
		}
		
		public function update()
		{
			$lang = Factory::getApplication()?->getLanguage();
			$lang->load('com_jotcache.sys');
			$lang->load('com_jotcache');
			$dbChangeMessages = [];
			$db               = Factory::getContainer()->get(DatabaseInterface::class);
			$ret              = $this->jotcacheUpgrade($db);
			if ($ret)
			{
				$dbChangeMessages[] = $ret;
			}
			$dbname = $db->name;
			switch ($dbname)
			{
				case 'sqlsrv':
				case 'sqlzure':
					$items = ['jotcache,cookies,NVARCHAR(2000) NOT NULL',
					          'jotcache,sessionvars,NVARCHAR(2000) NOT NULL',
					          'jotcache,domain,NVARCHAR(255) NOT NULL',
					          'jotcache,recache,tinyint',
					          'jotcache,recache_chck,tinyint',
					          'jotcache,agent,tinyint'
					];
					foreach ($items as $item)
					{
						[$table, $column, $type] = explode(',', $item, 3);
						$sql    = $db->getQuery(true);
						$prefix = $db->getPrefix();
						$sql->select('column_name')
						    ->from('information_schema.columns')
						    ->where('table_name = ' . $db->quote($prefix . $table))
						    ->where('column_name =' . $db->quote($column));
						$res = $db->setQuery($sql)->loadResult();
						if (!$res)
						{
							$query = 'ALTER TABLE' . ' ' . $prefix . $table . ' ADD ' . $column . ' ' . $type;
							$db->setQuery($query);
							$dbChangeMessages[] = substr($db->getQuery(), 15);
							$db->execute();
						}
					}
					break;
				default:
					$items = ['jotcache,mark,TINYINT(1)',
					          'jotcache,title,varchar(255) NOT NULL AFTER `fname`',
					          'jotcache,domain,varchar(255) NOT NULL AFTER `fname`',
					          'jotcache,uri,TEXT NOT NULL AFTER `title`',
					          'jotcache,recache,TINYINT(1) NOT NULL AFTER `mark`',
					          'jotcache,recache_chck,TINYINT(1) NOT NULL AFTER `recache`',
					          'jotcache,agent,TINYINT(1) NOT NULL AFTER `recache_chck`',
					          'jotcache,language,VARCHAR(5) NOT NULL AFTER `uri`',
					          'jotcache,browser,VARCHAR(50) NOT NULL AFTER `language`',
					          'jotcache,qs,TEXT NOT NULL',
					          'jotcache,cookies,TEXT NOT NULL',
					          'jotcache,sessionvars,TEXT NOT NULL',
					          'jotcache_exclude,type,TINYINT(4) NOT NULL'
					];
					foreach ($items as $item)
					{
						[$table, $column, $type] = explode(',', $item, 3);
						$sql = 'DESCRIBE `#__' . $table . '` `' . $column . '`';
						$db->setQuery($sql);
						if (!$db->loadResult())
						{
							$sql = 'ALTER TABLE' . ' #__' . $table . ' ADD `' . $column . '` ' . $type . ';';
							$db->setQuery($sql);
							$dbChangeMessages[] = substr($db->getQuery(), 15);
							$db->execute();
						}
					}
					break;
			} ?>
			<?php if (count($dbChangeMessages)) { ?>
            <h3><?php echo Text::_('PKG_JOTCACHE_ALTER_DB'); ?></h3>
            <table class="table table-striped">
                <tbody>
				<?php
					$k = 0;
					foreach ($dbChangeMessages as $msg)
					{
						?>
                        <tr class="row<?php echo $k; ?>">
                            <td class="key" colspan="3" style="border-bottom:1px solid #DDDDDD;"><?php echo $msg; ?></td>
                        </tr>
						<?php
						$k = 1 - $k;
					} ?>
                </tbody>
            </table>
			<?php
		}
			
			return true;
		}
		
		protected function jotcacheUpgrade($db)
		{
			$query = $db->getQuery(true);
			$query->select('COUNT(*)')
			      ->from('#__jotcache_exclude')
			      ->where('type=1');
			$tplexCount = $db->setQuery($query)->loadResult();
			if ($tplexCount === 0)
			{
				return false;
			}
			$query->clear('where');
			$query->where('type=4');
			$count = $db->setQuery($query)->loadResult();
			if ($count === 0)
			{
				$query->clear('select')
				      ->clear('where');
				$query->select($db->quoteName('value'))
				      ->from($db->quoteName('#__template_styles', 's'))
				      ->where('name=s.id')
				      ->where('type=1')
				      ->order('s.home');
				$defs      = $db->setQuery($query)->loadColumn();
				$positions = [];
				foreach ($defs as $def)
				{
					$defArray  = unserialize($def);
					$positions = array_merge($positions, $defArray);
				}
				$query->clear();
				$query->select('position')
				      ->from('#__modules')
				      ->where('client_id = 0')
				      ->where('published = 1')
				      ->where('position <>' . $db->quote(''))
				      ->group('position')
				      ->order('position');
				$db->setQuery($query);
				$items            = $db->loadColumn();
				$cleanedPositions = [];
				foreach ($items as $item)
				{
					if (array_key_exists($item, $positions))
					{
						$cleanedPositions[$item] = $positions[$item];
					}
				}
				$defs = serialize($cleanedPositions);
				$query->clear();
				$query->insert('#__jotcache_exclude')
				      ->columns('name,value,type')
				      ->values('1,' . $db->quote($defs) . ',4');
				try
				{
					$db->setQuery($query)->execute();
					$message = 'TABLE #__jotcache_exclude has been upgraded. Check JotCache TPL exclude definitions for correct values.';
					
					return $message;
				}
				catch (RuntimeException $ex)
				{
					$this->app->enqueueMessage($ex->getMessage());
				}
			}
			
			return false;
		}
		
		public function postflight($type, $parent)
		{
			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true);
			$query->select('ordering')
			      ->from('#__extensions')
			      ->where('type =' . $db->quote('plugin'))
			      ->where('folder =' . $db->quote('system'))
			      ->where($db->quoteName('element') . ' <> ' . $db->quote('jotmarker'))
			      ->order('ordering');
			$minOrder = $db->setQuery($query)->loadResult() - 1;
			$query->clear();
			$query->update($db->quoteName('#__extensions'))
			      ->set("ordering=$minOrder")
			      ->where($db->quoteName('element') . ' = ' . $db->quote('jotmarker'));
			try
			{
				$db->setQuery($query)->execute();
			}
			catch (RuntimeException $ex)
			{
				$this->app->enqueueMessage($ex->getMessage());
			}
			$query->clear();
			$query->select('ordering')
			      ->from('#__extensions')
			      ->where('type =' . $db->quote('plugin'))
			      ->where('folder =' . $db->quote('system'))
			      ->where($db->quoteName('element') . ' <> ' . $db->quote('jotcache'))
			      ->order('ordering desc');
			$maxOrder = $db->setQuery($query)->loadResult() + 1;
			$query->clear();
			$query->update($db->quoteName('#__extensions'))
			      ->set("ordering=$maxOrder")
			      ->where($db->quoteName('element') . ' = ' . $db->quote('jotcache'));
			try
			{
				$db->setQuery($query)->execute();
			}
			catch (RuntimeException $ex)
			{
				$this->app->enqueueMessage($ex->getMessage());
			}
			$query->clear();
			$query->update($db->quoteName('#__extensions'))
			      ->set('enabled=1')
			      ->where('type =' . $db->quote('plugin'))
			      ->where('folder =' . $db->quote('jotcacheplugins'));
			try
			{
				$db->setQuery($query)->execute();
			}
			catch (RuntimeException $ex)
			{
				$this->app->enqueueMessage($ex->getMessage());
			}
			$this->pluginInit();
			$this->showPlugins();
			$config        = Factory::getApplication()?->getConfig();
			$page_dir      = $config->get('cache_path', JPATH_ROOT . '/cache');
			$installedCurl = extension_loaded('curl');
			$this->checkPrerequisites($page_dir, PHP_OS, $installedCurl);
		}
		
		protected function pluginInit()
		{
			$initialDef =
				'{"browsercache":"0","cachetime":"15","cacheextratimes":"0","editdelete":"0","autoclean":"","cleanlog":"0","cachecookies":"","cachesessionvars":"","domain":"0","domainfilter":"","urlselection":"0","cacheexclude":"","cacheclient":{"desktop":"1","tablet":"1","phone":"1"},"botexclude":"0","storage":{"type":"file","persistent":"1","mcompress":"0","host":"localhost","port":"11211"}}';
			$db         = Factory::getContainer()->get(DatabaseInterface::class);
			$query      = $db->getQuery(true);
			$query->select('params')
			      ->from('#__extensions')
			      ->where('type =' . $db->quote('plugin'))
			      ->where('folder =' . $db->quote('system'))
			      ->where($db->quoteName('element') . '= ' . $db->quote('jotcache'));
			$dbDef = $db->setQuery($query)->loadResult();
			if (empty($dbDef))
			{
				$dbDef = '{}';
			}
			$initialObj = json_decode($initialDef);
			$dbObj      = json_decode($dbDef);
			foreach ($initialObj as $key => $value)
			{
				if (is_object($value))
				{
					foreach ($value as $key2 => $value2)
					{
						if (!property_exists($dbObj, $key))
						{
							$dbObj->$key = new stdClass();
						}
						if (!property_exists($dbObj->$key, $key2))
						{
							if (is_object($dbObj->$key))
							{
								$dbObj->$key->$key2 = $value2;
							}
							else
							{
								$dbObj->$key        = new stdClass();
								$dbObj->$key->$key2 = $value2;
							}
						}
					}
				}
				else
				{
					if (!property_exists($dbObj, $key))
					{
						$dbObj->$key = $value;
					}
				}
			}
			$updatedParams = json_encode($dbObj);
			$query->clear('select')->clear('from')
			      ->update($db->quoteName('#__extensions'))
			      ->set($db->quoteName('params') . ' = ' . $db->quote($updatedParams));
			try
			{
				$db->setQuery($query)->execute();
			}
			catch (RuntimeException $ex)
			{
				$this->app->enqueueMessage($ex->getMessage());
			}
		}
		
		protected function showPlugins()
		{
			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true);
			$query->select('name,folder,enabled')
			      ->from('#__extensions')
			      ->where('type =' . $db->quote('plugin'))
			      ->where('(folder =' . $db->quote('system') . ' OR folder =' . $db->quote('jotcacheplugins') . ' OR folder =' . $db->quote('content') . ')')
			      ->where($db->quoteName('element') . " IN ('jotcache','jotmarker','crawler','crawlerext','recache','jotloadmodule')")
			      ->order('name');
			$rows = $db->setQuery($query)->loadObjectList();
			echo '<div><h2>' . Text::_('PKG_JOTCACHE_LIST_PLUGINS') . '</h2><table class="table table-striped">';
			echo '<thead><tr><th width="150">Plugin name</th><th width="150">Type</th><th width="20">Status</th></tr></thead><tbody>';
			foreach ($rows as $row)
			{
				$status = '<i class="icon-unpublish"></i>';
				if ($row->enabled)
				{
					$status = '<i class="icon-publish"></i>';
				}
				echo '<tr><td>' . $row->name . '</td><td>' . $row->folder . '</td><td>' . $status . '</td></tr>';
			}
			echo '</tbody></table></div>';
		}
		
		protected function checkPrerequisites($page_dir, $os, $installedCurl)
		{
			if (file_exists($page_dir) && !(strtoupper(substr($os, 0, 3)) === 'WIN'))
			{
				chmod($page_dir, 0755);
				$permission = fileperms($page_dir) & 0777;
				if (0755 !== $permission)
				{
					echo '<p style="color:red;">' . Text::sprintf('PKG_JOTCACHE_PAGE_DIR_PERMISSION_WARN', $page_dir) . '</p>';
				}
			}
			echo '<div><h2>' . Text::_('PKG_JOTCACHE_LIST_PREREQUISITES') . '</h2>';
			echo '<p>' . Text::_('PKG_JOTCACHE_LIST_PREREQUISITES_DESC') . '</p><table class="table table-striped">';
			echo '<thead><tr><th width="150">Requirement</th><th width="150">Current value</th><th width="20">Status</th></tr></thead><tbody>';
			$cacheDirPermission       = fileperms($page_dir) & 0777;
			$cacheDirPermissionStatus = ($cacheDirPermission >= 0755) ? '<i class="icon-publish"></i>' : '<i class="icon-unpublish"></i>';
			$pageDirPermission        = 0;
			$pageDirPermissionStatus  = '';
			if (file_exists($page_dir))
			{
				$pageDir = $page_dir . '/page';
				if (file_exists($pageDir))
				{
					chmod($pageDir, 0755);
				}
				else
				{
					if (!mkdir($pageDir, 0755) && !is_dir($pageDir))
					{
						throw new \RuntimeException(sprintf('Directory "%s" was not created', $pageDir));
					}
				}
				$pageDirPermission       = fileperms($pageDir) & 0777;
				$pageDirPermissionStatus = ($pageDirPermission >= 0755) ? '<i class="icon-publish"></i>' : '<i class="icon-unpublish"></i>';
			}
			$installedCurlStatus = $installedCurl ? '<i class="icon-publish"></i>' : '<i class="icon-unpublish"></i>';
			echo '<tr><td>' . Text::_('PKG_JOTCACHE_CACHE_DIR_PERMISSION_DESC') . '</td><td>' . sprintf('%04o', $cacheDirPermission) . '</td><td>' . $cacheDirPermissionStatus . '</td></tr>';
			if (file_exists($page_dir))
			{
				echo '<tr><td>' . Text::_('PKG_JOTCACHE_PAGE_DIR_PERMISSION_DESC') . '</td><td>' . sprintf('%04o', $pageDirPermission) . '</td><td>' . $pageDirPermissionStatus . '</td></tr>';
			}
			echo '<tr><td>' . Text::_('PKG_JOTCACHE_INSTALLED_CURL_DESC') . '</td><td>' . $installedCurl . '</td><td>' . $installedCurlStatus . '</td></tr>';
			echo '</tbody></table></div>';
		}
	}