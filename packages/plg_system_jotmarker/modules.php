<?php
	/**
	 * @package         JotCache
	 * @subpackage      JotMarker
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Document\DocumentRenderer;
	use Joomla\CMS\Event\Module\AfterRenderModulesEvent;
	use Joomla\CMS\Factory;
	use Joomla\CMS\Helper\ModuleHelper;
	use Joomla\CMS\Layout\LayoutHelper;
	
	defined('JPATH_PLATFORM') or die;
	
	class JDocumentRendererHtmlModules extends DocumentRenderer
	{
		public function render($position, $params = [], $content = null)
		{
			$renderer     = $this->_doc->loadRenderer('module');
			$buffer       = '';
			$app          = Factory::getApplication();
			$user         = Factory::getApplication()->getIdentity();
			$frontediting = ($app->isClient('site') && $app->get('frontediting', 1) && !$user->guest);
			$menusEditing = ($app->get('frontediting', 1) == 2) && $user->authorise('core.edit', 'com_menus');
			foreach (ModuleHelper::getModules($position) as $mod)
			{
				$moduleHtml = $renderer->render($mod, $params, $content);
				if ($frontediting && trim($moduleHtml) != '' && $user->authorise('module.edit.frontend', 'com_modules.module.' . $mod->id))
				{
					$displayData = ['moduleHtml' => &$moduleHtml, 'module' => $mod, 'position' => $position, 'menusediting' => $menusEditing];
					LayoutHelper::render('joomla.edit.frontediting_modules', $displayData);
				}
				$buffer .= $moduleHtml;
			}
			
			$event = new AfterRenderModulesEvent('onAfterRenderModules', [
				'content'    => &$buffer,
				'attributes' => $params,
			]);
			
			$dispatcher = Factory::getApplication()->getDispatcher();
			$dispatcher->dispatch('onAfterRenderModules', $event);
			
			return $buffer;
		}
	}
	
	class JDocumentRendererModules extends DocumentRenderer
	{
		public function render($position, $params = [], $content = null)
		{
			$renderer     = $this->_doc->loadRenderer('module');
			$buffer       = '';
			$app          = Factory::getApplication();
			$user         = Factory::getApplication()->getIdentity();
			$frontediting = ($app->isClient('site') && $app->get('frontediting', 1) && !$user->guest);
			$menusEditing = ($app->get('frontediting', 1) == 2) && $user->authorise('core.edit', 'com_menus');
			foreach (ModuleHelper::getModules($position) as $mod)
			{
				$moduleHtml = $renderer->render($mod, $params, $content);
				if ($frontediting && trim($moduleHtml) != '' && $user->authorise('module.edit.frontend', 'com_modules.module.' . $mod->id))
				{
					$displayData = ['moduleHtml' => &$moduleHtml, 'module' => $mod, 'position' => $position, 'menusediting' => $menusEditing];
					LayoutHelper::render('joomla.edit.frontediting_modules', $displayData);
				}
				$buffer .= $moduleHtml;
			}
			
			$event = new AfterRenderModulesEvent('onAfterRenderModules', [
				'content'    => &$buffer,
				'attributes' => $params,
			]);
			
			$dispatcher = Factory::getApplication()->getDispatcher();
			$dispatcher->dispatch('onAfterRenderModules', $event);
			
			return $buffer;
		}
	}