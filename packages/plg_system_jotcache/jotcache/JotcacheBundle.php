<?php
	
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\Log\Log;
	use Joomla\CMS\Uri\Uri;
	use Joomla\Database\DatabaseInterface;
	
	defined('JPATH_BASE') or die;
	
	class JotcacheBundle
	{
		protected $params;
		protected $cache;
		protected $db;
		protected $app;
		protected $insertList;
		protected $updateList;
		protected $com;
		protected $view;
		
		function __construct($params, $cache)
		{
			$this->params = $params;
			$this->cache  = $cache;
			$this->db     = Factory::getContainer()->get(DatabaseInterface::class);
			$this->app    = Factory::getApplication();
			$this->com    = $this->app->input->getCmd('option', '');
			$this->view   = $this->app->input->getCmd('view', '');
		}
		
		function checkExclude()
		{
			$query = $this->db->getQuery(true);
			$query->select($this->db->quoteName('value'))
			      ->from('#__jotcache_exclude')
			      ->where('name=' . $this->db->quote($this->com))
			      ->where('type=0');
			try
			{
				$value = $this->db->setQuery($query)->loadResult();
			}
			catch (RuntimeException $ex)
			{
				echo $ex->getMessage();
				
				return false;
			}
			$expart       = $this->exclude($this->view, $value);
			$exclude      = ($value == '1' or $expart) ? true : false;
			$urlSelection = $this->params->get('urlselection', '0');
			if ($urlSelection)
			{
				$exclude = !$exclude;
			}
			
			return $exclude;
		}
		
		protected function exclude($view, $value)
		{
			if(empty($value))
			{
				return false;
			}
			
			$value = str_replace(' ', '', $value);
			$divs  = explode(',', $value);
			if ($view != '' && array_search($view, $divs) !== false)
			{
				return true;
			}
			$queryArray = $this->app->input->getArray();
			foreach ($divs as $div)
			{
				if (@strpos($div, '&') !== false)
				{
					$chunks = explode('&', $div);
					$cnt    = 0;
					foreach ($chunks as $chunk)
					{
						if (@strpos($chunk, '=') !== false)
						{
							$parts = explode('=', $chunk);
							if (array_key_exists($parts[0], $queryArray) && $queryArray[$parts[0]] == $parts[1])
							{
								$cnt++;
							}
						}
						else
						{
							if ($chunk == $view)
							{
								$cnt++;
							}
						}
					}
					if ($cnt == count($chunks))
					{
						return true;
					}
				}
				else
				{
					if (@strpos($div, '=') !== false)
					{
						$parts = explode('=', $div);
						if (array_key_exists($parts[0], $queryArray) && $queryArray[$parts[0]] == $parts[1])
						{
							return true;
						}
					}
				}
			}
			
			return false;
		}
		
		function storeBundle($app,
		                     $uri,
		                     $mark, $agent)
		{
			$id = $this->app->input->getInt('id', 0);
			if ($id == 0)
			{
				$id = $this->app->input->getInt('cid', 0);
			}
			$fname = $this->cache->fname;
			if (version_compare(JVERSION, '3.2') >= 0)
			{
				$qsRaw = $app->input->getArray();
			}
			else
			{
				$qsRaw = $_REQUEST;
			}
			$qs       = serialize($qsRaw);
			$browser  = $this->cache->options['browser'];
			$language = $this->cache->options['language'];
			$document = Factory::getDocument();
			$title    = $document->title;
			$query    = $this->db->getQuery(true);
			$query->select('COUNT(*)')
			      ->from('#__jotcache')
			      ->where('fname=' . $this->db->quote($fname));
			try
			{
				$found = $this->db->setQuery($query)->loadResult();
			}
			catch (RuntimeException $ex)
			{
				$app->enqueueMessage($ex->getMessage(), 'error');
				
				return;
			}
			$query->clear();
			$ftime       = date($this->db->getDateFormat());
			$cookies     = $this->cache->options['cookies'];
			$sessionvars = $this->cache->options['sessionvars'];
			$agent       = $agent ? '1' : '0';
			$uri2        = Uri::getInstance();
			$domain      = $uri2->toString(['scheme', 'host', 'port']);
			if ($this->params->get('cacheextratimes', ''))
			{
				$lt = $this->parseLifeTime($qsRaw);
				if ($lt)
				{
					$this->cache->setLifeTime($lt);
				}
			}
			if (!$found)
			{
				$com        = $this->com;
				$view       = $this->view;
				$cols       = [];
				$vals       = [];
				$insertList = ['domain', 'com', 'view', 'id', 'fname', 'title', 'uri', 'browser', 'language', 'agent', 'ftime', 'mark', 'qs', 'cookies', 'sessionvars'];
				foreach ($insertList as $item)
				{
					$cols[] = $this->db->quoteName($item);
					$vals[] = $this->db->quote($$item);
				}
				$columns = implode(",", $cols);
				$values  = implode(",", $vals);
				$query->insert('#__jotcache')
				      ->columns($columns)
				      ->values($values);
				try
				{
					$this->db->setQuery($query)->execute();
				}
				catch (RuntimeException $ex)
				{
					$app->enqueueMessage($ex->getMessage(), 'error');
					
					return;
				}
			}
			else
			{
				$updateList = ['title', 'ftime', 'mark', 'agent', 'qs', 'cookies', 'sessionvars'];
				$query->update($this->db->quoteName('#__jotcache'));
				foreach ($updateList as $item)
				{
					$query->set($this->db->quoteName($item) . ' = ' . $this->db->quote($$item));
				}
				$query->where($this->db->quoteName('fname') . ' = ' . $this->db->quote($fname));
				try
				{
					$this->db->setQuery($query)->execute();
				}
				catch (RuntimeException $ex)
				{
					if ($this->params->get('errlog', '0'))
					{
						Log::add($ex->getMessage(), Log::ERROR, 'jotcache_err');
					}
					
					return;
				}
			}
		}
		
		public function parseLifeTime($qsRaw)
		{
			$query = $this->db->getQuery(true);
			$query->select('value')
			      ->from('#__jotcache_exclude')
			      ->where($this->db->quoteName('type') . ' = 7');
			$data  = $this->db->setQuery($query, 0, 1)->loadResult();
			$packs = unserialize($data);
			if (is_array($packs))
			{
				foreach ($packs as $key => $expireTime)
				{
					$qs     = unserialize($key);
					$result = array_diff_assoc($qs, $qsRaw);
					if (count($result) === 0)
					{
						return 60 * $expireTime;
					}
				}
			}
			
			return false;
		}
	}