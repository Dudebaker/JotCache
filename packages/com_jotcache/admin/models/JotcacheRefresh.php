<?php
	
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	
	class JotcacheRefresh
	{
		private $app;
		private $db;
		private $deleted;
		private $existed;
		private $root;
		private $storage;
		
		public function __construct($db, $storage)
		{
			$this->app     = Factory::getApplication();
			$this->db      = $db;
			$config        = Factory::getApplication()?->getConfig();
			$this->root    = $config->get('cache_path', JPATH_ROOT . '/cache') . '/page';
			$this->storage = $storage;
		}
		
		public function refreshMemcache() : void
		{
			$query = $this->db->getQuery(true);
			$query->select('fname')->from('#__jotcache');
			$existed = [];
			$deleted = [];
			try
			{
				$rows = $this->db->setQuery($query)->loadObjectList();
				foreach ($rows as $row)
				{
					$hit = $this->storage->get($row->fname);
					if ($hit === false)
					{
						$deleted[] = $row->fname;
					}
					else
					{
						$existed[$row->fname] = 1;
					}
				}
			}
			catch (RuntimeException $ex)
			{
				$this->app->enqueueMessage($ex->getMessage(), 'error');
			}
			if (count($deleted) > 0)
			{
				$list = implode("','", $deleted);
				$query->clear();
				$query->delete()
				      ->from('#__jotcache')
				      ->where("fname IN ('$list')");
				try
				{
					$this->db->setQuery($query)->execute();
				}
				catch (RuntimeException $ex)
				{
					$this->app->enqueueMessage($ex->getMessage(), 'error');
				}
			}
			if (is_a($this->storage, 'JotcacheMemcached'))
			{
				$keys = $this->storage->getAllKeys();
				foreach ($keys as $key)
				{
					if (!array_key_exists($key, $existed))
					{
						$this->storage->remove($key);
					}
				}
			}
			else
			{
				foreach ($deleted as $key)
				{
					$this->storage->remove($key);
				}
			}
			
			return;
		}
		
		public function refreshFileCache() : void
		{
			$query = $this->db->getQuery(true);
			if (!file_exists($this->root))
			{
				$query->delete()->from('#__jotcache');
				try
				{
					$this->db->setQuery($query)->execute();
				}
				catch (RuntimeException $ex)
				{
					$this->app->enqueueMessage($ex->getMessage(), 'error');
				}
				
				return;
			}
			$query->clear();
			$query->select('fname')->from('#__jotcache');
			$this->deleted = [];
			$this->existed = [];
			try
			{
				$rows = $this->db->setQuery($query)->loadObjectList();
				$this->checkFileCache($rows);
			}
			catch (RuntimeException $ex)
			{
				$this->app->enqueueMessage($ex->getMessage(), 'error');
				
				return;
			}
			$this->removeFromDb();
			$this->removeFromFs();
		}
		
		public function checkFileCache($rows) : void
		{
			foreach ($rows as $row)
			{
				$filename = $this->root . '/' . $row->fname . '.php_expire';
				if (file_exists($filename))
				{
					$exp = file_get_contents($filename);
					if (time() - $exp > 0)
					{
						$this->deleted[] = $row->fname;
					}
					else
					{
						$this->existed[$row->fname] = 1;
					}
				}
				else
				{
					$this->deleted[] = $row->fname;
				}
			}
		}
		
		public function removeFromDb() : void
		{
			if (count($this->deleted) > 0)
			{
				$list  = implode("','", $this->deleted);
				$query = $this->db->getQuery(true);
				$query->delete()
				      ->from('#__jotcache')
				      ->where("fname IN ('$list')");
				try
				{
					$this->db->setQuery($query)->execute();
				}
				catch (RuntimeException $ex)
				{
					$this->app->enqueueMessage($ex->getMessage(), 'error');
				}
			}
		}
		
		public function removeFromFs() : void
		{
			if ($handle = opendir($this->root))
			{
				while (false !== ($file = readdir($handle)))
				{
					if ($file != "." && $file != "..")
					{
						$ext   = strrchr($file, ".");
						$fname = substr($file, 0, -strlen($ext));
						if (!array_key_exists($fname, $this->existed) && ($ext == ".php" || $ext == ".php_expire"))
						{
							unlink($this->root . '/' . $file);
						}
					}
				}
				closedir($handle);
			}
		}
	}