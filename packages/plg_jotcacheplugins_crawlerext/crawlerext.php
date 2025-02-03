<?php
	/**
	 * @package         JotCache
	 * @subpackage      JotCachePlugins.CrawlerExt
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Component\ComponentHelper;
	use Joomla\CMS\Factory;
	use Joomla\CMS\Filter\InputFilter;
	use Joomla\CMS\Log\Log;
	use Joomla\CMS\Plugin\CMSPlugin;
	use Joomla\Database\DatabaseInterface;
	use Joomla\Database\DatabaseQuery;
	use Joomla\Event\DispatcherInterface;
	
	defined('_JEXEC') or die;
	include_once JPATH_ADMINISTRATOR . '/components/com_jotcache/helpers/browseragents.php';
	
	class plgJotcachepluginsCrawlerext extends CMSPlugin
	{
		public $logging;
		private $baseUrl;
		private $hits;
		private $runner;
		private $root;
		
		function __construct(DispatcherInterface $dispatcher, array $config = [])
		{
			$config2    = Factory::getApplication()?->getConfig();
			$this->root = $config2->get('cache_path', JPATH_ROOT . '/cache') . '/page/';
			
			parent::__construct($dispatcher, $config);
		}
		
		function onJotcacheRecache($starturl, $jcplugin,
		                           $jcparams, $jcstates)
		{
			if ($jcplugin != 'crawlerext')
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
			foreach ($activeBrowsers as $browser => $def)
			{
				$agent = $def[1] . ' jotcache';
				$ret   = $this->crawl_page($starturl, $browser, $agent, $depth);
				if ($ret == 'STOP')
				{
					break;
				}
			}
			
			return ["crawlerext", $ret, $this->hits];
		}
		
		function crawl_page($url, $browser, $agent, $depth = 5)
		{
			$seen                 = [];
			$this->hits[$browser] = 0;
			$hrefs                = [[]];
			$hrefs[0][0]          = $url;
			$this->runner         = new RecacheRunner();
			for ($i = 0; $i < $depth; $i++)
			{
				if ($this->logging && $i > 0)
				{
					Log::add(sprintf('....for browser %s returned %d links on level %d', $browser, count($hrefs[$i]), $i), Log::INFO, 'jotcache.recache');
				}
				foreach ($hrefs[$i] as $href)
				{
					$href = htmlspecialchars_decode($href);
					$html = $this->runner->getData($href, $agent);
					/*        if ($this->logging && empty($html) && ($url == $this->baseUrl)) {
									  Log::add(sprintf('....inside crawlerext plugin - starting page is empty >> server cannot reach external URL', $html), Log::INFO, 'jotcache.recache');
									}*/
					if ($this->logging)
					{
						if ($href == $this->baseUrl)
						{
							if (empty($html))
							{
								Log::add(sprintf('....inside crawlerext plugin - starting page is empty >> server cannot reach external URL %s', $href), Log::INFO, 'jotcache.recache');
							}
							else
							{
								Log::add(sprintf('....inside crawlerext plugin - starting page URL %s', $href), Log::INFO, 'jotcache.recache');
								file_put_contents(JPATH_ROOT . '/logs/jotcache.crawlerext_initial_page.html', $html);
							}
						}
					}
					preg_match_all('#<a.*?href="?([^>" ]*)"?.*?>#', $html, $matches);
					foreach ($matches[1] as $link)
					{
						$this->hits[$browser]++;
						if (!file_exists($this->root . 'jotcache_recache_flag_tmp.php'))
						{
							return 'STOP';
						}
						if (strpos($link, '#') !== false)
						{
							continue;
						}
						if (0 !== strpos($link, 'http'))
						{
							if (preg_match('#^(\w*:)#', trim($link)))
							{
								continue;
							}
							$path  = '/' . ltrim($link, '/');
							$parts = parse_url($url);
							$link  = $parts['scheme'] . '://';
							$link  .= $parts['host'];
							if (isset($parts['port']))
							{
								$link .= ':' . $parts['port'];
							}
							$link .= $path;
						}
						if (stripos($link, $this->baseUrl) !== 0)
						{
							continue;
						}
						$hash = md5(strtolower($link) . $browser);
						if (isset($seen[$hash]))
						{
							continue;
						}
						$seen[$hash]     = true;
						$hrefs[$i + 1][] = $link;
					}
				}
			}
			
			return 'DONE';
		}
	}