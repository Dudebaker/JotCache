<?php
	/**
	 * @package         JotCache
	 * @subpackage      Content.JotLoadModule
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\Helper\ModuleHelper;
	use Joomla\CMS\Plugin\CMSPlugin;
	
	defined('_JEXEC') or die;
	
	class PlgContentJotLoadmodule extends CMSPlugin
	{
		protected static $modules = [];
		protected static $mods = [];
		protected static $title;
		
		public function onContentPrepare($context, &$article, &$params, $page = 0)
		{
			if ($context === 'com_finder.indexer')
			{
				return true;
			}
			if (strpos($article->text, 'loadposition') === false && strpos($article->text, 'loadmodule') === false)
			{
				return true;
			}
			$regex    = '/{loadposition\s(.*?)}/i';
			$style    = $this->params->def('style', 'none');
			$regexmod = '/{loadmodule\s(.*?)}/i';
			$stylemod = $this->params->def('style', 'none');
			preg_match_all($regex, $article->text, $matches, PREG_SET_ORDER);
			if ($matches)
			{
				foreach ($matches as $match)
				{
					$matcheslist = explode(',', $match[1]);
					if (!array_key_exists(1, $matcheslist))
					{
						$matcheslist[1] = $style;
					}
					$position      = trim($matcheslist[0]);
					$style         = trim($matcheslist[1]);
					$output        = '<jot ' . $match[1] . ' s style="' . $style . '"></jot>' . $this->_load($position, $style) . '<jot ' . $match[1] . ' e></jot>';
					$article->text = preg_replace("|$match[0]|", addcslashes($output, '\\$'), $article->text, 1);
					$style         = $this->params->def('style', 'none');
				}
			}
			preg_match_all($regexmod, $article->text, $matchesmod, PREG_SET_ORDER);
			if ($matchesmod)
			{
				foreach ($matchesmod as $matchmod)
				{
					$matchesmodlist = explode(',', $matchmod[1]);
					if (!array_key_exists(1, $matchesmodlist))
					{
						$matchesmodlist[1] = null;
					}
					if (!array_key_exists(2, $matchesmodlist))
					{
						$matchesmodlist[2] = $stylemod;
					}
					$module   = trim($matchesmodlist[0]);
					$name     = htmlspecialchars_decode(trim($matchesmodlist[1]));
					$stylemod = trim($matchesmodlist[2]);
					$output   = $this->_loadmod($module, $name, $stylemod);
					if (!empty($output))
					{
						$output = '<jot jc_' . $matchesmodlist[0] . ' s title="' . $name . '" style="' . $stylemod . '"></jot>' . $output . '<jot jc_' . $matchesmodlist[0] . ' e></jot>';
					}
					$article->text = preg_replace(addcslashes("|$matchmod[0]|", '()'), addcslashes($output, '\\$'), $article->text, 1);
					$stylemod      = $this->params->def('style', 'none');
				}
			}
		}
		
		protected function _load($position, $style = 'none')
		{
			self::$modules[$position] = '';
			$document                 = Factory::getApplication()?->getDocument();
			$renderer                 = $document->loadRenderer('module');
			$modules                  = ModuleHelper::getModules($position);
			$params                   = ['style' => $style];
			ob_start();
			foreach ($modules as $module)
			{
				echo $renderer->render($module, $params);
			}
			self::$modules[$position] = ob_get_clean();
			
			return self::$modules[$position];
		}
		
		protected function _loadmod($module, $title, $style = 'none')
		{
			self::$mods[$module] = '';
			$document            = Factory::getApplication()?->getDocument();
			$renderer            = $document->loadRenderer('module');
			$mod                 = ModuleHelper::getModule($module, $title);
			if (!isset($mod))
			{
				$name = 'mod_' . $module;
				$mod  = ModuleHelper::getModule($name, $title);
			}
			$content = $mod->content;
			$params  = ['style' => $style];
			ob_start();
			if ($mod->id)
			{
				echo $renderer->render($mod, $params);
			}
			self::$mods[$module] = ob_get_clean();
			
			return self::$mods[$module];
		}
	}