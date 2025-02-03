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
	
	defined('JPATH_BASE') or die;
	
	class JotcacheMemcache
	{
		protected static $db = null;
		protected static $lead = '';
		protected $persistent = true;
		protected $compress = 0;
		
		public function __construct($pars)
		{
			if (self::$db === null)
			{
				$this->getConnection($pars);
			}
		}
		
		protected function getConnection($pars)
		{
			if ((extension_loaded('memcache') && class_exists('Memcache')) != true)
			{
				return false;
			}
			$this->persistent = $pars->storage->persistent == 1 ? true : false;
			$this->compress   = $pars->storage->mcompress == 1 ? MEMCACHE_COMPRESSED : 0;
			if (!isset($pars->storage->host))
			{
				throw new RuntimeException(Text::_('JOTCACHE_MEMCACHED_NO_SETTINGS'), 404);
			}
			self::$db = new Memcache;
			self::$db->addServer($pars->storage->host, $pars->storage->port, $this->persistent);
			$memcachetest = @self::$db->connect($pars->storage->host, $pars->storage->port);
			if ($memcachetest == false)
			{
				$msg = sprintf(Text::_('JOTCACHE_MEMCACHED_NO_CONNECT'), $pars->storage->host, $pars->storage->port);
				throw new RuntimeException($msg, 404);
			}
			$config     = Factory::getApplication()?->getConfig();
			$hash       = md5($config->get('secret'));
			self::$lead = 'jotcache-' . substr($hash, 0, 6) . '-';
			
			return true;
		}
		
		public function get($fname)
		{
			$data = self::$db->get(self::$lead . $fname);
			if ($data)
			{
				$data = preg_replace('/^.*\n/', '', $data);
			}
			
			return $data;
		}
		
		public function remove($key)
		{
			return self::$db->delete(self::$lead . $key);
		}
		
		public function autoclean()
		{
		}
	}