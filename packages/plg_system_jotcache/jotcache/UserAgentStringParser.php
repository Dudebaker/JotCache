<?php
	
	/**
	 * Simple PHP User Agent string parser
	 *
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	class UserAgentStringParser
	{
		private $filters;
		
		public function parse($userAgentString = null)
		{
			if (!$userAgentString)
			{
				$userAgentString = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
			}
			$clean = $this->cleanUserAgentString($userAgentString);
			if ($this->isBot($clean))
			{
				return [
					'string'           => $clean,
					'browser_name'     => 'bot',
					'browser_version'  => null,
					'operating_system' => null,
					'engine'           => null
				];
			}
			if ($this->isIPhone($clean))
			{
				return [
					'string'           => $clean,
					'browser_name'     => 'iPhone',
					'browser_version'  => null,
					'operating_system' => null,
					'engine'           => null
				];
			}
			if ($this->isIPad($clean))
			{
				return [
					'string'           => $clean,
					'browser_name'     => 'iPad',
					'browser_version'  => null,
					'operating_system' => null,
					'engine'           => null
				];
			}
			if ($this->isMobile($clean))
			{
				if (preg_match('/ipad|viewpad|tablet|bolt|xoom|touchpad|playbook|kindle|gt-p|gt-i|sch-i|sch-t|mz609|mz617|mid7015|tf101|g-v|ct1002|transformer|silk|tab/i', $clean) || (preg_match('/android/i', $clean) && !preg_match('/mobile/i', $clean)))
				{
					return [
						'string'           => $clean,
						'browser_name'     => 'tablet',
						'browser_version'  => null,
						'operating_system' => null,
						'engine'           => null
					];
				}
				else
				{
					return [
						'string'           => $clean,
						'browser_name'     => 'phone',
						'browser_version'  => null,
						'operating_system' => null,
						'engine'           => null
					];
				}
			}
			else
			{
				$informations = $this->doParse($userAgentString);
				foreach ($this->getFilters() as $filter)
				{
					$this->$filter($informations);
				}
				if ($this->isOtherDesktop($informations['browser_name']))
				{
					$informations['browser_name'] = 'desktop';
				}
				
				return $informations;
			}
		}
		
		public function cleanUserAgentString($userAgentString)
		{
			$userAgentString = trim(strtolower($userAgentString));
			$userAgentString = strtr($userAgentString, $this->getKnownBrowserAliases());
			$userAgentString = strtr($userAgentString, $this->getKnownOperatingSystemAliases());
			$userAgentString = strtr($userAgentString, $this->getKnownEngineAliases());
			
			return $userAgentString;
		}
		
		protected function getKnownBrowserAliases()
		{
			return [
				'shiretoko'    => 'firefox',
				'namoroka'     => 'firefox',
				'shredder'     => 'firefox',
				'minefield'    => 'firefox',
				'granparadiso' => 'firefox'
			];
		}
		
		protected function getKnownOperatingSystemAliases()
		{
			return [];
		}
		
		protected function getKnownEngineAliases()
		{
			return [];
		}
		
		function isBot($clean)
		{
			if ($this->isMobile($clean))
			{
				return false;
			}
			else
			{
				return preg_match('/bot|crawler|facebookexternalhit|linkdex|slurp|spider/i', $clean);
			}
		}
		
		function isMobile($clean)
		{
			return preg_match('/android|avantgo|blackberry|blazer|compal|elaine|fennec|galaxy|hiptop|htc|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile|nexus|nitro|nokia|o2|opera mini|palm( os)?|phone|plucker|pocket|portalmmm|proxinet|pre\/|psp|samsung|smartphone|series40|series60|s60|sonyericsson|symbian|touchpad|treo|up\.(browser|link)|vodafone|wap|webos|windows\s?ce|(iemobile|ppc)|xiino|240x320|400X240/i',
			                  $clean);
		}
		
		function isIPhone($clean)
		{
			if (empty($clean))
			{
				return false;
			}
			else
			{
				$pos = stripos($clean, 'iPhone');
				
				return ($pos > 0) ? true : false;
			}
		}
		
		function isIPad($clean)
		{
			if (empty($clean))
			{
				return false;
			}
			else
			{
				$pos = stripos($clean, 'iPad');
				
				return ($pos > 0) ? true : false;
			}
		}
		
		protected function doParse($userAgentString)
		{
			$userAgent = [
				'string'           => $this->cleanUserAgentString($userAgentString),
				'browser_name'     => null,
				'browser_version'  => null,
				'operating_system' => null,
				'engine'           => null
			];
			if (empty($userAgent['string']))
			{
				return $userAgent;
			}
			$pattern = '#(' . implode('|', $this->getKnownBrowsers()) . ')[/ ]+([0-9]+(?:\.[0-9]+)?)#';
			if (preg_match_all($pattern, $userAgent['string'], $matches))
			{
				$i = count($matches[1]) - 1;
				if (isset($matches[1][$i]))
				{
					$userAgent['browser_name'] = $matches[1][$i];
				}
				if (isset($matches[2][$i]))
				{
					if ($userAgent['browser_name'] == 'msie')
					{
						$userAgent['browser_version'] = $matches[2][0];
					}
					else
					{
						$userAgent['browser_version'] = $matches[2][$i];
					}
				}
			}
			$pattern = '#' . implode('|', $this->getKnownOperatingSystems()) . '#';
			if (preg_match($pattern, $userAgent['string'], $match))
			{
				if (isset($match[0]))
				{
					$userAgent['operating_system'] = $match[0];
				}
			}
			$pattern = '#' . implode('|', $this->getKnownEngines()) . '#';
			if (preg_match($pattern, $userAgent['string'], $match))
			{
				if (isset($match[0]))
				{
					$userAgent['engine'] = $match[0];
				}
			}
			
			return $userAgent;
		}
		
		protected function getKnownBrowsers()
		{
			return [
				'msie',
				'firefox',
				'safari',
				'webkit',
				'opera',
				'netscape',
				'konqueror',
				'gecko',
				'chrome',
				'googlebot',
				'iphone',
				'msnbot',
				'applewebkit',
				'edge'
			];
		}
		
		protected function getKnownOperatingSystems()
		{
			return [
				'windows',
				'macintosh',
				'linux',
				'freebsd',
				'unix',
				'iphone'
			];
		}
		
		protected function getKnownEngines()
		{
			return [
				'gecko',
				'webkit',
				'trident',
				'presto'
			];
		}
		
		public function getFilters()
		{
			return [
				'filterGoogleChrome',
				'filterSafariVersion',
				'filterOperaVersion',
				'filterYahoo',
				'filterMsie'
			];
		}
		
		function isOtherDesktop($browser_name)
		{
			return preg_match('/applewebkit|gecko|konqueror|opera/i', $browser_name);
		}
		
		public function addFilter($filter)
		{
			$this->filters += $filter;
		}
		
		protected function filterGoogleChrome(array &$userAgent)
		{
			if ('safari' === $userAgent['browser_name'] && strpos($userAgent['string'], 'chrome/'))
			{
				$userAgent['browser_name']    = 'chrome';
				$userAgent['browser_version'] = preg_replace('|.+chrome/([0-9]+(?:\.[0-9]+)?).+|', '$1', $userAgent['string']);
			}
		}
		
		protected function filterSafariVersion(array &$userAgent)
		{
			if ('safari' === $userAgent['browser_name'] && strpos($userAgent['string'], ' version/'))
			{
				$userAgent['browser_version'] = preg_replace('|.+\sversion/([0-9]+(?:\.[0-9]+)?).+|', '$1', $userAgent['string']);
			}
		}
		
		protected function filterOperaVersion(array &$userAgent)
		{
			if ('opera' === $userAgent['browser_name'] && strpos($userAgent['string'], ' version/'))
			{
				$userAgent['browser_version'] = preg_replace('|.+\sversion/([0-9]+\.[0-9]+)\s*.*|', '$1', $userAgent['string']);
			}
		}
		
		protected function filterYahoo(array &$userAgent)
		{
			if (null === $userAgent['browser_name'] && strpos($userAgent['string'], 'yahoo! slurp'))
			{
				$userAgent['browser_name'] = 'yahoobot';
			}
		}
		
		protected function filterMsie(array &$userAgent)
		{
			if ('msie' === $userAgent['browser_name'] && empty($userAgent['engine']))
			{
				$userAgent['engine'] = 'trident';
			}
			if ($userAgent['operating_system'] == 'windows' && $userAgent['browser_name'] === null && $userAgent['engine'] == 'trident')
			{
				if (strpos($userAgent['string'], 'rv:11') >= 0)
				{
					$userAgent['browser_name'] = 'msie11';
				}
				else
				{
					$userAgent['browser_name'] = 'msie';
				}
			}
		}
	}