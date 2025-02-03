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
	use Joomla\Database\DatabaseInterface;
	
	defined('JPATH_PLATFORM') or die;
	
	final class BrowserAgents
	{
		private static $defaultBrowser = 'Opera/9.80 (Windows NT 6.1; U; es-ES) Presto/2.9.181 Version/12.00';
		private static $browsers;
		private static $transpose = true;
		
		public static function getDefaultAgent($cacheclient)
		{
			if ($cacheclient->desktop == 1)
			{
				return self::$browsers['desktop'][1] . ' jotdef';
			}
			if ($cacheclient->phone == 1)
			{
				return self::$browsers['phone'][1] . ' jotdef';
			}
			if ($cacheclient->tablet == 1)
			{
				return self::$browsers['tablet'][1] . ' jotdef';
			}
		}
		
		public static function getBrowserAgents()
		{
			if (!isset(self::$browsers))
			{
				self::loadAgents();
			}
			
			return self::$browsers;
		}
		
		private static function loadAgents()
		{
			$lang = Factory::getApplication()?->getLanguage();
			$lang->load('com_jotcache', JPATH_ADMINISTRATOR, null, false, true);
			if (self::$transpose)
			{
				self::$browsers = [
					"desktop" => [Text::_('JOTCACHE_CLIENT_DESKTOP'), 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.13 (KHTML, like Gecko) Chrome/24.0.1284.0 Safari/537.13']
				];
			}
			else
			{
				self::$browsers = [
					"chrome"  => ['Chrome', 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.13 (KHTML, like Gecko) Chrome/24.0.1284.0 Safari/537.13'],
					"firefox" => ['Firefox', 'Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1'],
					"msie6"   => ['IE 6.0', 'Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.1)'],
					"msie7"   => ['IE 7.0', 'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; en-US)'],
					"msie8"   => ['IE 8.0', 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 5.1; SLCC1; .NET CLR 1.1.4322)'],
					"msie9"   => ['IE 9.0', 'Mozilla/5.0 (Windows; U; MSIE 9.0; Windows NT 9.0; en-US)'],
					"msie10"  => ['IE 10.0', 'Mozilla/5.0 (compatible; MSIE 10.6; Windows NT 6.1; Trident/5.0; InfoPath.2; SLCC1; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 2.0.50727) 3gpp-gba UNTRUSTED/1.0'],
					"msie11"  => ['IE 11.0', 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko'],
					"safari"  => ['Safari', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2'],
					"desktop" => [Text::_('JOTCACHE_CLIENT_DESKTOP'), 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.13 (KHTML, like Gecko) Chrome/24.0.1284.0 Safari/537.13'],
					"iPad"    => ['iPad', 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25'],
					"iPhone"  => ['iPhone', 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/531.22.7']
				];
			}
			self::$browsers['tablet'] = [Text::_('JOTCACHE_CLIENT_TABLET'), 'Mozilla/5.0 (Linux; U; Android 4.0.3; en-us; KFTT Build/IML74K) AppleWebKit/535.19 (KHTML, like Gecko) Silk/2.1 Mobile Safari/535.19 Silk-Accelerated=true'];
			self::$browsers['phone']  = [Text::_('JOTCACHE_CLIENT_PHONE'), 'Mozilla/5.0 (Linux; U; Android 4.0.3; de-de; Galaxy S II Build/GRJ22) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30'];
		}
		
		public static function getActiveBrowserAgents()
		{
			if (!isset(self::$browsers))
			{
				self::loadAgents();
			}
			$clients = [];
			$db      = Factory::getContainer()->get(DatabaseInterface::class);
			$query   = $db->getQuery(true);
			$query->select('params')
			      ->from('#__extensions')
			      ->where('type =' . $db->Quote('plugin'))
			      ->where('folder =' . $db->Quote('system'))
			      ->where('element =' . $db->Quote('jotcache'));
			$cacheplg = $db->setQuery($query)->loadResult();
			if ($cacheplg && stripos($cacheplg, 'cacheclient') !== false)
			{
				$pars    = json_decode($cacheplg);
				$clients = get_object_vars($pars->cacheclient);
			}
			$activeBrowsers        = [];
			$catchFirstNonsplitted = true;
			foreach (self::$browsers as $key => $value)
			{
				if ($clients[$key] == 2)
				{
					$activeBrowsers[$key] = self::$browsers[$key];
				}
				else
				{
					if ($catchFirstNonsplitted && $clients[$key] == 1)
					{
						$activeBrowsers[$key]  = self::$browsers[$key];
						$catchFirstNonsplitted = false;
					}
				}
			}
			
			return $activeBrowsers;
		}
	}