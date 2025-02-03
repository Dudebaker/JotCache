<?php
	
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	class JotcacheClean
	{
		private $_cache_dir;
		private $_grade_id;
		private $_grades;
		private $_total;
		private $_deleted;
		private $_del_fnames;
		
		function __construct()
		{
			$this->_grades   = [[16, "", 0], [256, "0", -2], [4096, "00", -3]];
			$this->_grade_id = 0;
		}
		
		public function run($init)
		{
			$this->_total      = 0;
			$this->_deleted    = 0;
			$this->_del_fnames = [];
			$start             = microtime(true);
			if (!isset($this->_grade_id))
			{
				$this->_grade_id = 0;
			}
			for ($i = $init[0]; $i < $this->_grades[$this->_grade_id][0]; $i++)
			{
				if ($this->_grade_id == 0)
				{
					$hex = dechex($i);
				}
				else
				{
					$hex = substr($this->_grades[$this->_grade_id][1] . dechex($i), $this->_grades[$this->_grade_id][2]);
				}
				$files = glob($this->_cache_dir . $hex . "*.php_expire");
				if (is_array($files))
				{
					$actual = array_search($this->_cache_dir . $init[1] . '.php_expire', $files);
					$actual = ($actual === false) ? 0 : $actual;
					for ($j = $actual; $j < count($files); $j++)
					{
						$this->removeExpired($files[$j]);
						$this->_total += 1;
						if ((microtime(true) - $start) > 0.5)
						{
							return [$i, basename($files[$j], '.php_expire')];
						}
					}
				}
			}
			
			return -1;
		}
		
		private function removeExpired($file)
		{
			$time = @file_get_contents($file);
			if ($time < microtime(true))
			{
				@chmod($file, 0777);
				@unlink($file);
				$file2 = str_replace('_expire', '', $file);
				@chmod($file2, 0777);
				@unlink($file2);
				$this->_del_fnames[] = basename($file2, '.php');
				$this->_deleted++;
			}
		}
		
		public function setDir($dir)
		{
			$this->_cache_dir = $dir;
			
			return (is_dir($this->_cache_dir)) ? true : false;
		}
		
		public function getDir()
		{
			return $this->_cache_dir;
		}
		
		public function getGradeId()
		{
			return $this->_grade_id;
		}
		
		public function setGradeId($id)
		{
			$id = (int) $id;
			if ($id >= 0 || $id < count($this->_grades))
			{
				$this->_grade_id = $id;
			}
		}
		
		public function getDeletedFnames()
		{
			return $this->_del_fnames;
		}
		
		public function getStat()
		{
			return [$this->_total, $this->_deleted];
		}
	}