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
	use Joomla\Database\DatabaseInterface;
	
	defined('JPATH_BASE') or die;
	require_once dirname(__FILE__) . '/JotcacheStorage.php';
	require_once dirname(__FILE__) . '/JotcacheStorageBase.php';
	
	class JotcacheFileCache extends JotcacheStorage implements JotcacheStorageBase
	{
		public $connected = false;
		protected $_clean = false;
		
		public function __construct($params, $options = [])
		{
			parent::__construct($params, $options);
			$this->connected = true;
		}
		
		public function store($data = null)
		{
			if ($data)
			{
				$written    = false;
				$path       = $this->_getFilePath();
				$expirePath = $path . '_expire';
				$die        = '<?php die("Access Denied"); ?>' . "\n";
				$data       = $die . $data;
				$fp         = @fopen($path, "wb");
				if ($fp)
				{
					if ($this->locking)
					{
						@flock($fp, LOCK_EX);
					}
					$len = strlen($data);
					@fwrite($fp, $data, $len);
					if ($this->locking)
					{
						@flock($fp, LOCK_UN);
					}
					@fclose($fp);
					$written = true;
				}
				if ($written && ($data == file_get_contents($path)))
				{
					@file_put_contents($expirePath, ($this->now + $this->lifetime));
					
					return true;
				}
				else
				{
					return false;
				}
			}
			
			return false;
		}
		
		public function _getFilePath()
		{
			$folder = $this->group;
			$dir    = $this->root . '/' . $folder;
			if (!is_dir($dir))
			{
				$indexFile = $dir . '/' . 'index.html';
				@ mkdir($dir, 0755) && file_put_contents($indexFile, '<html><body bgcolor="#FFFFFF"></body></html>');
			}
			if (!is_dir($dir))
			{
				return false;
			}
			
			return $dir . '/' . $this->fname . '.php';
		}
		
		public function autoclean()
		{
			$vector    = @file_get_contents($this->getRootDir() . '.autoclean');
			$cleantime = @explode('|', $vector);
			if (!(is_array($cleantime) && count($cleantime)) == 3)
			{
				$cleantime = [0, 0, ''];
			}
			$this->_clean = false;
			if ($cleantime[0] > 0)
			{
				if (time() > $cleantime[0])
				{
					require_once(__DIR__ . '/autoclean.php');
					$clean = new JotcacheClean();
					jimport('joomla.filesystem.file');
					$dir = $this->root . '/page/';
					if ($clean->setDir($dir))
					{
						$cleanmode = $this->params->get('cleanmode', 1);
						if ($cleanmode > 0)
						{
							$clean->setGradeId($this->params->get('cleanmode', 1) - 1);
						}
						$ret = [$cleantime[1], $cleantime[2]];
						$ret = $clean->run($ret);
						if ($this->params->get('cleanlog', 0))
						{
							$stat = $clean->getStat();
							$msg1 = ($ret[0] > -1) ? "interrupted on : (" . $ret[0] . "|" . $ret[1] . ") " : "";
							$line = $msg1 . "last deleted : " . $stat[1];
							jimport('joomla.log.log');
							Log::addLogger(['text_file' => "plg_jotcache.autoclean.log.php", 'text_entry_format' => "{DATE} {TIME}\t{MESSAGE}"], Log::INFO, 'jotcache');
							Log::add($line, Log::INFO, 'jotcache');
						}
						$database   = Factory::getContainer()->get(DatabaseInterface::class);
						$query      = $database->getQuery(true);
						$expired    = time() - $this->params->get('cachetime', 15) * 60;
						$db_expired = date("Y-m-d H:i:s", $expired);
						$query->delete()
						      ->from($database->quoteName('#__jotcache'))
						      ->where("ftime < '$db_expired'");
						try
						{
							$database->setQuery($query)->execute();
						}
						catch (RuntimeException $ex)
						{
							if ($this->params->get('errlog', '0'))
							{
								Log::add($ex->getMessage(), Log::ERROR, 'jotcache_err');
							}
						}
						if (is_array($ret))
						{
							$this->_clean = true;
							$this->_paramsUpdate(false, $ret);
						}
						else
						{
							$this->_paramsUpdate(false, [0, '']);
						}
					}
				}
			}
			else
			{
				$this->_paramsUpdate(true, [0, '']);
			}
		}
		
		protected function getRootDir()
		{
			return $this->root . '/' . $this->group . '/';
		}
		
		public function get()
		{
			$data = false;
			$path = $this->_getFilePath();
			$this->_setExpire($path);
			if (file_exists($path))
			{
				$data = file_get_contents($path);
				if ($data)
				{
					$data = preg_replace('/^.*\n/', '', $data);
				}
			}
			
			return $data;
		}
		
		private function _setExpire($path)
		{
			if (file_exists($path . '_expire'))
			{
				$time = @file_get_contents($path . '_expire');
				if ($time < $this->now || empty($time))
				{
					$this->remove($path);
				}
			}
			else if (file_exists($path))
			{
				$this->remove($path);
			}
		}
		
		public function remove($path)
		{
			@unlink($path . '_expire');
			if (!@unlink($path))
			{
				return false;
			}
			
			return true;
		}
		
		private function _paramsUpdate(
			$init, $ret)
		{
			$ret_string = '|' . implode('|', $ret);
			if ($this->_clean)
			{
				$cleantime = '1' . $ret_string;
			}
			else
			{
				$delay     = (int) $this->params->get('autoclean', 0) * 60;
				$cleantime = (time() + $delay) . $ret_string;
			}
			@file_put_contents($this->getRootDir() . '.autoclean', $cleantime);
		}
	}