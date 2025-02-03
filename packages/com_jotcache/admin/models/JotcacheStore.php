<?php
	
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
	
	class JotcacheStore
	{
		public $exclude = [];
		private $app;
		private $db;
		private $root;
		private $storage;
		
		public function __construct($db, $storage)
		{
			$this->app     = Factory::getApplication();
			$this->db      = $db;
			$config        = Factory::getApplication()?->getConfig();
			$this->root    = $config->get('cache_path', JPATH_ROOT . '/cache');
			$this->storage = $storage;
		}
		
		public function storeUrlInsDel($excludeJc, $excludeDb) : bool
		{
			$query = $this->db->getQuery(true);
			$del   = array_diff_key($excludeDb, $excludeJc);
			$ins   = array_diff_key($excludeJc, $excludeDb);
			if (count($del) > 0)
			{
				$delList = implode("','", array_keys($del));
				$query->clear();
				$query->delete()
				      ->from($this->db->quoteName('#__jotcache_exclude'))
				      ->where("name IN ('$delList')");
				try
				{
					$this->db->setQuery($query)->execute();
				}
				catch (RuntimeException $ex)
				{
					$this->app->enqueueMessage($ex->getMessage(), 'error');
				}
			}
			foreach ($ins as $option => $views)
			{
				$query->clear();
				$query->insert('#__jotcache_exclude')
				      ->columns('name,value,type')
				      ->values("'$option', '$views','0'");
				try
				{
					$this->db->setQuery($query)->execute();
				}
				catch (RuntimeException $ex)
				{
					$this->app->enqueueMessage($ex->getMessage(), 'error');
					
					return false;
				}
			}
			
			return true;
		}
		
		public function getExcludePost($post) : mixed
		{
			$nameList = "";
			$cnt      = 0;
			foreach ($post as $key => $value)
			{
				if (substr($key, 0, 3) == "ex_")
				{
					if ($cnt > 0)
					{
						$nameList .= ",";
					}
					$nameList .= "'" . substr($key, 3) . "'";
					$cnt++;
				}
			}
			$query = $this->db->getQuery(true);
			$query->select('id,name,value')
			      ->from('#__jotcache_exclude')
			      ->where('name IN (' . $nameList . ')');
			try
			{
				$rows = $this->db->setQuery($query)->loadObjectList();
			}
			catch (RuntimeException $ex)
			{
				$this->app->enqueueMessage($ex->getMessage(), 'error');
			}
			if (!empty($rows))
			{
				foreach ($rows as $row)
				{
					$this->exclude[$row->name] = $row->value;
				}
			}
			
			return $this->exclude;
		}
		
		public function storeUrlUpdate($upd) : bool
		{
			$query   = $this->db->getQuery(true);
			$updList = implode("','", array_keys($upd));
			$query->clear();
			$query->select($this->db->quoteName('fname'))
			      ->from('#__jotcache')
			      ->where("com IN ('$updList')");
			try
			{
				$names = $this->db->setQuery($query)->loadObjectList();
				foreach ($names as $name)
				{
					if (file_exists($this->root . '/' . $name->fname . '.php'))
					{
						unlink($this->root . '/' . $name->fname . '.php');
						unlink($this->root . '/' . $name->fname . '.php_expire');
					}
				}
			}
			catch (RuntimeException $ex)
			{
				$this->app->enqueueMessage($ex->getMessage(), 'error');
			}
			foreach ($upd as $option => $views)
			{
				$query->clear();
				$query->update($this->db->quoteName('#__jotcache_exclude'))
				      ->set($this->db->quoteName('value') . "='$views'")
				      ->where($this->db->quoteName('name') . " = '$option'");
				try
				{
					$this->db->setQuery($query)->execute();
				}
				catch (RuntimeException $ex)
				{
					$this->app->enqueueMessage($ex->getMessage(), 'error');
					
					return false;
				}
			}
			
			return true;
		}
		
		public function storeTplInsDel($tplStored, $packed) : bool
		{
			$query = $this->db->getQuery(true);
			if ($tplStored == 1)
			{
				$query->update($this->db->quoteName('#__jotcache_exclude'))
				      ->set($this->db->quoteName('value') . ' = ' . $this->db->quote($packed))
				      ->where($this->db->quoteName('type') . ' = 4')
				      ->where($this->db->quoteName('name') . ' = 1');
				try
				{
					$this->db->setQuery($query)->execute();
				}
				catch (RuntimeException $ex)
				{
					$this->app->enqueueMessage($ex->getMessage(), 'error');
					
					return false;
				}
			}
			else
			{
				$query->insert('#__jotcache_exclude')
				      ->columns('name,value,type')
				      ->values("'1','$packed','4'");
				try
				{
					$this->db->setQuery($query)->execute();
				}
				catch (RuntimeException $ex)
				{
					$this->app->enqueueMessage($ex->getMessage(), 'error');
					
					return false;
				}
			}
			
			return true;
		}
		
		public function getBrowserPost($post, $type) : stdClass
		{
			$defs      = new stdClass();
			$defs->upd = [];
			$defs->ins = [];
			$defbtime  = 0;
			if ($type == 2)
			{
				$params   = ComponentHelper::getParams('com_jotcache');
				$defbtime = (int) $params->get('defbtime');
			}
			foreach ($post as $key => $value)
			{
				$part = substr($key, 0, 2);
				switch ($part)
				{
					case 'ux':
						$id    = (int) substr($key, 2);
						$value = $this->uriFilter($value, $type);
						if ($value === false)
						{
							break;
						}
						$defs->upd[] = [$id, $value, (int) $post['uy' . $id]];
						break;
					case 'ix':
						if ($value != "")
						{
							$id    = (int) substr($key, 2);
							$value = $this->uriFilter($value, $type);
							if ($value === false)
							{
								break;
							}
							if ($post['iy' . $id] == 0 && $type == 2)
							{
								$post['iy' . $id] = $defbtime;
							}
							$defs->ins[] = [$value, (int) $post['iy' . $id]];
						}
						break;
					default:
						break;
				}
			}
			
			return $defs;
		}
		
		public function uriFilter($value, $type) : mixed
		{
			if ($value == "")
			{
				return false;
			}
			if ((substr($value, 0, 1) != "/") && ($type == 2))
			{
				$value = "/" . $value;
			}
			if ($type == 2)
			{
				$pattern = '#^[\/]([a-zA-Z0-9-_:\.]*[\/]?)*[?]?([a-zA-Z0-9-_:\.]*[=]?[a-zA-Z0-9-_:\.]*[&]?)*#';
			}
			else
			{
				$pattern = '#^([a-zA-Z0-9-_:\.]*[\/]?)*[?]?([a-zA-Z0-9-_:\.]*[=]?[a-zA-Z0-9-_:\.]*[&]?)*#';
			}
			preg_match($pattern, (string) $value, $matches);
			if (is_array($matches))
			{
				$filtered = @ (string) $matches[0];
				if ($value == $filtered)
				{
					return $value;
				}
			}
			$this->app->enqueueMessage(Text::_('JOTCACHE_BCACHE_NEW_URI') . ' ' . $value . ' ' . Text::_('JOTCACHE_BCACHE_BLOCKED'), 'warning');
			
			return false;
		}
		
		public function storeExtraInsDel($defs, $type) : bool
		{
			$set   = [];
			$query = $this->db->getQuery(true);
			foreach ($defs->upd as $item)
			{
				$query->clear();
				$query->update($this->db->quoteName('#__jotcache_exclude'))
				      ->set($this->db->quoteName('name') . ' = ' . (int) $item[2])
				      ->set($this->db->quoteName('value') . ' = ' . $this->db->quote($item[1]))
				      ->where($this->db->quoteName('type') . " = '$type'")
				      ->where($this->db->quoteName('id') . ' = ' . (int) $item[0]);
				try
				{
					$this->db->setQuery($query)->execute();
				}
				catch (RuntimeException $ex)
				{
					$this->app->enqueueMessage($ex->getMessage(), 'error');
					
					return false;
				}
				$set[] = $item[1];
			}
			foreach ($defs->ins as $item)
			{
				if (!in_array($item[0], $set))
				{
					$query->clear();
					$name  = $item[1];
					$value = $item[0];
					$query->insert('#__jotcache_exclude')
					      ->columns('name,value,type')
					      ->values("'$name', '$value', '$type'");
					try
					{
						$this->db->setQuery($query)->execute();
					}
					catch (RuntimeException $ex)
					{
						$this->app->enqueueMessage($ex->getMessage(), 'error');
						
						return false;
					}
				}
				else
				{
					$this->app->enqueueMessage(Text::_('JOTCACHE_BCACHE_NEW_URI') . ' ' . $item[0] . ' ' . Text::_('JOTCACHE_BCACHE_DUPL'), 'warning');
				}
				$set[] = $item[0];
			}
			
			return true;
		}
	}