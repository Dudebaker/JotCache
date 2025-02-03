<?php
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\HTML\HTMLHelper;
	use Joomla\CMS\Language\Text;
	use Joomla\CMS\MVC\View\HtmlView;
	use Joomla\CMS\Toolbar\ToolbarHelper;
	
	defined('_JEXEC') or die('Restricted access');
	
	class MainViewRecache extends HtmlView
	{
		protected $url = "http://kbase.jotcomponents.net/jotcache:help:direct60j3x:";
		protected $filter = [];
		protected $plugins = null;
		
		function stopRecache()
		{
			$this->setLayout("stop");
			parent::display();
		}
		
		function display($tpl = null)
		{
			$input    = Factory::getApplication()?->input;
			$document = Factory::getApplication()?->getDocument();
			$document->addScript('components/com_jotcache/assets/jotcache.js?ver=6.2.1');
			$document->addStyleSheet('components/com_jotcache/assets/jotcache.css?ver=6.2.1');
			$this->plugins          = $this->get('Plugins');
			$cid                    = $input->get('cid', null, 'array');
			$this->filter['chck']   = (isset($cid)) ? true : false;
			$this->filter['search'] = $input->getString('filter_search', '');
			$this->filter['com']    = $input->getString('filter_com', '');
			$this->filter['view']   = $input->getString('filter_view', '');
			$this->filter['mark']   = ($input->getString('filter_mark', '')) ? 'Yes' : '';
			$this->addToolbar();
			parent::display($tpl);
		}
		
		protected function addToolbar()
		{
			HTMLHelper::_('behavior.tooltip');
			HTMLHelper::_('behavior.keepalive');
			ToolBarHelper::title(Text::_('JOTCACHE_RECACHE_TITLE'), 'jotcache-logo.gif');
			ToolBarHelper::custom('recache.start', 'start.png', 'start.png', Text::_('JOTCACHE_RECACHE_START'), false);
			ToolBarHelper::spacer();
			ToolBarHelper::custom('recache.stop', 'stop.png', 'stop.png', Text::_('JOTCACHE_RECACHE_STOP'), false);
			ToolBarHelper::spacer();
			ToolBarHelper::spacer();
			ToolBarHelper::spacer();
			ToolBarHelper::cancel('close', Text::_('JCANCEL'));
			ToolBarHelper::spacer();
			ToolbarHelper::help('Help', false, $this->url . 'recache_use');
		}
	}