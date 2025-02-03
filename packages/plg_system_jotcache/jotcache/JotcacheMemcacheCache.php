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
	require_once dirname(__FILE__) . '/JotcacheStorage.php';
	require_once dirname(__FILE__) . '/JotcacheStorageBase.php';
	
	class JotcacheMemcacheCache extends JotcacheStorage implements JotcacheStorageBase
	{
		protected static $_db = null;
		protected static $_lead = '';
		public $connected = false;
		protected $_persistent = true;
		protected $_compress = 0;
		protected $_clean = false;
		
		public function __construct($options = [], $params)
		{
			parent::__construct($params, $options);
			if (self::$_db === null)
			{
				$this->getConnection($params);
			}
		}
		
		protected function getConnection($params)
		{
			if ((extension_loaded('memcache') && class_exists('Memcache')) != true)
			{
				return false;
			}
			$this->_persistent = $params->get('persistent', true);
			$this->_compress   = $params->get('mcompress', false) == false ? 0 : MEMCACHE_COMPRESSED;
			$storage           = $params->get('storage', null);
			if (!isset($storage))
			{
				$this->loadLanguage();
				throw new RuntimeException(Text::_('JOTCACHE_MEMCACHED_NO_SETTINGS'), 404);
			}
			try
			{
				self::$_db = new Memcache;
				self::$_db->addServer($storage->host, $storage->port, $this->_persistent);
				self::$_db->connect($storage->host, $storage->port);
			}
			catch (Exception $e)
			{
				$this->debug(Text::_('JOTCACHE_DEBUG_ERROR'), $e->getMessage());
				self::$_db = null;
				
				return null;
			}/*    if ($memcachetest == false) {
          $this->loadLanguage();
          $msg = sprintf(Text::_('JOTCACHE_MEMCACHED_NO_CONNECT'), $storage->host, $storage->port);
          throw new RuntimeException($msg, 404);
    //      throw new RuntimeException('Could not connect to memcached server on host:'.$storage->host.'port:'.$storage->port, 404);
        }*/
			$this->connected = true;
			$config          = Factory::getApplication()->getConfig();
			$hash            = md5($config->get('secret'));
			self::$_lead     = 'jotcache-' . substr($hash, 0, 6) . '-';
			
			return true;
		}
		
		public function get()
		{
			/*    if (!is_object(self::$_db)) {
				  return;
				}*/
			$data = self::$_db->get(self::$_lead . $this->fname);
			if ($data)
			{
				$data = preg_replace('/^.*\n/', '', $data);
			}
			
			return $data;
		}
		
		protected function loadLanguage()
		{
			$lang = Factory::getApplication()->getLanguage();
			$lang->load('plg_system_jotcache', JPATH_ADMINISTRATOR, null, false, true);
		}
		
		public function store($data = null)
		{
			if ($data)
			{
				$cache_id = self::$_lead . $this->fname;
				if (!self::$_db->replace($cache_id, $data, $this->_compress, $this->lifetime))
				{
					self::$_db->set($cache_id, $data, $this->_compress, $this->lifetime);
				}
				
				return true;
			}
			
			return false;
		}
		
		public function remove($path)
		{
			return self::$_db->delete(self::$_lead . $this->fname);
		}
		
		public function autoclean()
		{
		}
		
		public function _getFilePath()
		{
			return '';
		}
	}