<?php
	/**
	 * @package         JotCache
	 * @subpackage      JotCachePlugins.Crawler
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\Filter\InputFilter;
	use Joomla\CMS\Log\Log;
	use Joomla\CMS\Plugin\CMSPlugin;
	use Joomla\Database\DatabaseInterface;
	use Joomla\Database\DatabaseQuery;
	use Joomla\Event\DispatcherInterface;
	
	defined('_JEXEC') or die;
	include_once JPATH_ADMINISTRATOR . '/components/com_jotcache/helpers/browseragents.php';
	
	class plgJotcachepluginsCrawler extends CMSPlugin
	{
		public $logging;
		private $baseUrl;
		private $hits;
		private $runner;
		private $root;
		private $logPath;
		
		function __construct(DispatcherInterface $dispatcher, array $config = [])
		{
			$config2       = Factory::getApplication()?->getConfig();
			$this->root    = $config2->get('cache_path', JPATH_ROOT . '/cache') . '/page/';
			$this->logPath = $config2->get('log_path', JPATH_ROOT . '/logs/');
			
			parent::__construct($dispatcher, $config);
		}
		
		function onJotcacheRecache($starturl, $jcplugin,
		                           $jcparams, $jcstates)
		{
			if ($jcplugin != 'crawler')
			{
				return;
			}
			$this->baseUrl = $starturl;
			$params        = ComponentHelper::getParams('com_jotcache');
			$database      = Factory::getContainer()->get(DatabaseInterface::class);
			/* @var $query DatabaseQuery */
			$query = $database->getQuery(true);
			$query->update($database->quoteName('#__jotcache'))
			      ->set($database->quoteName('agent') . ' = ' . $database->quote(0));
			$database->setQuery($query)->execute();
			$this->logging = $params->get('recachelog', 0) == 1 ? true : false;
			if ($this->logging)
			{
				Log::add(sprintf('....running in plugin %s', $jcplugin), Log::INFO, 'jotcache.recache');
			}
			$noHtmlFilter = InputFilter::getInstance();
			$depth        = $noHtmlFilter->clean($jcstates['depth'], 'int');
			$depth++;
			$activeBrowsers = BrowserAgents::getActiveBrowserAgents();
			$this->hits     = [];
			$ret            = '';
			$this->runner   = new RecacheRunner();
			foreach ($activeBrowsers as $browser => $def)
			{
				$agent                = $def[1] . ' jotcache';
				$this->hits[$browser] = 0;
				$ret                  = $this->crawl_page($starturl, $browser, $agent, $depth);
				if ($ret == 'STOP')
				{
					break;
				}
			}
			
			return ["crawler", $ret, $this->hits];
		}
		
		function crawl_page($url, $browser, $agent, $depth = 5)
		{
			static $seen = [];
			$url = htmlspecialchars_decode($url);
			if ($this->hits[$browser] == 0)
			{
				$seen = null;
			}
			$hash = md5(strtolower($url) . $browser);
			if (isset($seen[$hash]) || $depth === 0 || (stripos($url, $this->baseUrl) !== 0))
			{
				return;
			}
			$seen[$hash] = true;
			$html        = $this->runner->getData($url, $agent);
			if ($this->logging)
			{
				if ($url == $this->baseUrl)
				{
					if (empty($html))
					{
						Log::add(sprintf('....inside crawler plugin - starting page is empty >> server cannot reach external URL %s', $url), Log::INFO, 'jotcache.recache');
					}
					else
					{
						Log::add(sprintf('....inside crawler plugin - starting page URL %s', $url), Log::INFO, 'jotcache.recache');
						file_put_contents($this->logPath . 'jotcache.crawler_initial_page.html', $html);
					}
				}
			}
			preg_match_all('#<a.*?href="?([^>" ]*)"?.*?>#', $html, $matches);
			foreach ($matches[1] as $href)
			{
				$this->hits[$browser]++;
				if (!file_exists($this->root . 'jotcache_recache_flag_tmp.php'))
				{
					return 'STOP';
				}
				if (strpos($href, '#') !== false)
				{
					continue;
				}
				if (0 !== strpos($href, 'http'))
				{
					if (preg_match('#^(\w*:)#', trim($href)))
					{
						continue;
					}
					$path  = '/' . ltrim($href, '/');
					$parts = parse_url($url);
					$href  = $parts['scheme'] . '://';
					$href  .= $parts['host'];
					if (isset($parts['port']))
					{
						$href .= ':' . $parts['port'];
					}
					$href .= $path;
				}
				$this->crawl_page($href, $browser, $agent, $depth - 1);
			}
			
			return 'DONE';
		}
	}