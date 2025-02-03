<?php
	/**
	 * @package         JotCache
	 * @subpackage      JotMarker
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\Plugin\CMSPlugin;
	use Joomla\Database\DatabaseInterface;
	
	defined('_JEXEC') or die;
	
	class plgSystemJotmarker extends CMSPlugin
	{
		protected static $rules;
		
		public static function onAfterRenderModules(&$buffer, &$params)
		{
			if (!defined('JOTCACHE_DISPATCH'))
			{
				return;
			}
			$app = Factory::getApplication();
			if ($app->isClient('administrator') || JDEBUG || $_SERVER['REQUEST_METHOD'] == 'POST')
			{
				return;
			}
			$user = Factory::getApplication()->getIdentity();
			if (!$user->guest)
			{
				return;
			}
			if (empty(self::$rules))
			{
				$database = Factory::getContainer()->get(DatabaseInterface::class);
				$query    = $database->getQuery(true);
				$tpl_id   = 1;
				$query->select('value')
				      ->from('#__jotcache_exclude')
				      ->where($database->quoteName('type') . ' = 4')
				      ->where($database->quoteName('name') . ' = ' . (int) $tpl_id);
				$value       = $database->setQuery($query)->loadResult();
				
				if($value !== null)
				{
					self::$rules = unserialize($value);
				}
			}
			if (is_array(self::$rules) && is_array($params) && array_key_exists("name", $params) && key_exists($params["name"], self::$rules) && strlen($buffer) > 0)
			{
				$prefix = '<jot ' . $params["name"] . ' s';
				if (array_key_exists('style', $params))
				{
					$prefix .= ' style="' . $params["style"] . '"';
				}
				if (count($params) > 2)
				{
					foreach ($params as $key => $value)
					{
						if ($key == 'name' || $key == 'style')
						{
							continue;
						}
						else
						{
							$prefix .= ' ' . $key . '="' . $value . '"';
						}
					}
				}
				$buffer = $prefix . '></jot>' . $buffer . '<jot ' . $params["name"] . ' e></jot>';
			}
		}
		
		public function onAfterInitialise()
		{
			if (version_compare(JVERSION, '3.7', 'lt'))
			{
				$app = Factory::getApplication();
				if ($app->isClient('administrator') || JDEBUG || $_SERVER['REQUEST_METHOD'] == 'POST')
				{
					return;
				}
				if (version_compare(JVERSION, '3.5', 'ge'))
				{
					JLoader::register('JDocumentRendererHtmlModules', __DIR__ . '/modules.php', true);
				}
				else
				{
					JLoader::register('JDocumentRendererModules', __DIR__ . '/modules.php', true);
				}
			}
		}
		
		public function onAfterDispatch()
		{
			define('JOTCACHE_DISPATCH', 1);
		}
	}