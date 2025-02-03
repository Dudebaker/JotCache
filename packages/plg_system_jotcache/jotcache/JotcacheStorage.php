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
	
	defined('JPATH_BASE') or die;
	
	class JotcacheStorage
	{
		public $fname;
		public $options;
		public $params;
		protected $id;
		protected $application;
		protected $hash;
		protected $language;
		protected $root;
		protected $now;
		protected $lifetime;
		protected $group;
		protected $locking;
		
		public function __construct($params, $options = [])
		{
			$this->options             = $options;
			$this->params              = $params;
			$config                    = Factory::getApplication()->getConfig();
			$this->root                = $config->get('cache_path', JPATH_ROOT . '/cache');
			$this->language            = $config->get('language', 'en-GB');
			$this->options['language'] = $this->language;
			$this->hash                = $config->get('secret');
			$this->group               = $options['defaultgroup'];
			$this->locking             = (isset($options['locking'])) ? $options['locking'] : true;
			$this->lifetime            = (isset($options['lifetime'])) ? $options['lifetime'] : null;
			$this->now                 = (isset($options['now'])) ? $options['now'] : time();
			if (empty($this->lifetime))
			{
				$this->lifetime = 60;
			}
			$this->id    = md5($options['uri'] . '-' . $options['browser'] . $options['cookies'] . $options['sessionvars']);
			$this->fname = md5($this->application . '-' . $this->id . '-' . $this->hash . '-' . $this->language);
		}
		
		public function setLifeTime($lt)
		{
			$this->lifetime = $lt;
		}
		
		public function debug($message, $value = '', $level = 2)
		{
			if ($this->params->get('cachedebug', '0') >= $level)
			{
				if (is_null($value))
				{
					$value = 'null';
				}
				else if (is_bool($value))
				{
					$value = $value ? 'true' : 'false';
				}
				if ($value)
				{
					$message .= " [$value]";
				}
				Log::add($message, Log::DEBUG, 'jotcache_debug');
			}
		}
	}