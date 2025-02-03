<?php
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	defined('_JEXEC') or die('Restricted access');
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\Language\Text;
	use Joomla\CMS\MVC\View\HtmlView;
	use Joomla\CMS\Router\Route;
	use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
	use Joomla\CMS\Toolbar\ToolbarHelper;
	use Joomla\String\StringHelper;
	
	class MainViewMain extends HtmlView
	{
		protected $app;
		protected $data;
		protected $fnameExt;
		protected $lists;
		protected $status;
		protected $filters;
		protected $pars;
		protected $url = "http://kbase.jotcomponents.net/jotcache:help:direct60j3x:";
		protected $sidebar;
		protected $canManage;
		protected $statusPlugin;
		protected $statusGlobal;
		protected $statusClear;
		protected $showcookies;
		protected $showsessionvars;
		
		public function __construct($config = [])
		{
			parent::__construct($config);
			$this->app = Factory::getApplication();
			$document  = Factory::getApplication()?->getDocument();
			$document->addScript('components/com_jotcache/assets/jotcache.js?ver=6.2.1');
			$document->addStyleSheet('components/com_jotcache/assets/jotcache.css?ver=6.2.1');
			$user            = Factory::getApplication()?->getIdentity();
			$this->canManage = $user->authorise('core.manage', 'com_jotcache') && $user->authorise('jotcache.manage', 'com_jotcache');
		}
		
		function exclude()
		{
			if ($this->canManage)
			{
				$this->app->input->set('hidemainmenu', true);
				$this->data = $this->get('ExList', 'Main');
				$this->setLayout("exclude");
				$this->sidebar = $this->renderSidebar('exclude');
				parent::display();
			}
		}
		
		protected function renderSidebar($task = 'display')
		{
			$this->pars = $this->get('PluginParams', 'Main');
			if ($task == 'display')
			{
				$this->assignFilters();
			}
			$sidebar = '<div id="sidebar"><div class="sidebar-nav">';
			if ($this->canManage)
			{
				$this->assignAdminLinks($sidebar, $task);
			}
			if ($task == 'display')
			{
				$sidebar .= '<div class="filter-select">'
				            . '<h4 class="page-header">' . Text::_("JSEARCH_FILTER_LABEL") . '</h4>';
				foreach ($this->filters as $filter)
				{
					$sidebar .= '<label for="' . $filter["name"] . '" class="element-invisible">'
					            . $filter['label'] . '</label>';
					$sidebar .= '<select name="' . $filter["name"] . '" id="' . $filter['name'] . '" '
					            . 'class="span12 small" onchange="' . $filter["onchange"] . '">';
					if (!$filter['noDefault'])
					{
						$sidebar .= '<option value="">' . $filter["label"] . '</option>';
					}
					$sidebar .= $filter['options']
					            . '</select><hr class="hr-condensed" />';
				}
				$sidebar .= '</div>';
			}
			$sidebar .= '</div></div>';
			
			return $sidebar;
		}
		
		protected function assignFilters()
		{
			if (!isset($this->pars->urlselection))
			{
				$this->pars->urlselection = '0';
			}
			$this->filters   = [['name' => 'filter_com', 'label' => Text::_('JOTCACHE_RS_SEL_COMP'), 'options' => $this->lists['com'], 'noDefault' => '', 'onchange' => 'jotcache.resetSelect(1);'],
			                    ['name' => 'filter_view', 'label' => Text::_('JOTCACHE_RS_SEL_VIEW'), 'options' => $this->lists['view'], 'noDefault' => '', 'onchange' => 'jotcache.resetSelect(0);'],
			                    ['name' => 'filter_mark', 'label' => Text::_('JOTCACHE_RS_SEL_MARK'), 'options' => $this->lists['mark'], 'noDefault' => '', 'onchange' => 'jotcache.resetSelect(0);']];
			$this->filters[] = ['name' => 'filter_domain', 'label' => Text::_('JOTCACHE_RS_SEL_DOMAIN'), 'options' => $this->lists['domain'], 'noDefault' => '', 'onchange' => 'jotcache.resetSelect(0);'];
		}
		
		protected function assignAdminLinks(&$sidebar, $task)
		{
			$sidebar .= '<ul class="nav nav-list" id="submenu">';
			$sidebar .= '<li' . (($task == 'display') ? ' class="active"' : '') . '><a href="index.php?option=com_jotcache">' . Text::_('JOTCACHE_RS_OVERVIEW') . '</a></li>';
			$sidebar .= '<li' . (($task == 'exclude') ? ' class="active"' : '') . '><a href="index.php?option=com_jotcache&amp;view=main&amp;task=exclude&amp;boxchecked=0">' . ($this->pars->urlselection ? Text::_('COM_JOTCACHE_RS_INCL') : Text::_('COM_JOTCACHE_RS_EXCL')) . '</a></li>';
			$sidebar .= '<li' . (($task == 'tplex') ? ' class="active"' : '') . '><a href="index.php?option=com_jotcache&amp;view=main&amp;task=tplex&amp;boxchecked=0">' . Text::_('COM_JOTCACHE_RS_TPL_EXCL') . '</a></li>';
			$sidebar .= '<li' . (($task == 'bcache') ? ' class="active"' : '') . '><a href="index.php?option=com_jotcache&amp;view=main&amp;task=bcache&amp;boxchecked=0">' . Text::_('COM_JOTCACHE_RS_BCACHE') . '</a></li>';
			if (property_exists($this->pars, 'cacheextratimes') && $this->pars->cacheextratimes)
			{
				$sidebar .= '<li' . (($task == 'extratime') ? ' class="active"' : '') . '><a href="index.php?option=com_jotcache&amp;view=main&amp;task=extratime&amp;boxchecked=0">' . Text::_('COM_JOTCACHE_RS_EXTRATIME') . '</a></li>';
			}
			$sidebar .= '</ul><hr/>';
		}
		
		function display($tpl = null)
		{
			$this->data               = $this->get('Data');
			$this->lists              = $this->get('Lists');
			$this->status             = $this->get('Status');
			$model                    = $this->getModel();
			$search                   = $this->app->getUserStateFromRequest('com_jotcache.search', 'filter_search', '', 'string');
			$search                   = StringHelper::strtolower($search);
			$this->lists['search']    = $search;
			$this->lists['order_Dir'] = $model->fileOrderDir;
			$this->lists['order']     = $model->fileOrder;
			$this->sidebar            = $this->renderSidebar();
			$this->addToolbar();
			parent::display();
		}
		
		protected function addToolbar()
		{
			if ($this->pars->enabled)
			{
				if (isset($this->pars->cachecookies))
				{
					$this->showcookies = $this->pars->cachecookies && $this->data->showcookies;
				}
				else
				{
					$this->showcookies = false;
				}
				if (isset($this->pars->cachesessionvars))
				{
					$this->showsessionvars = $this->pars->cachesessionvars && $this->data->showsessionvars;
				}
				else
				{
					$this->showsessionvars = false;
				}
			}
			$bar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar('toolbar');
			ToolBarHelper::title(Text::_('JOTCACHE_RS_TITLE'), 'jotcache-logo.gif');
			$bar->addButtonPath(JPATH_COMPONENT_ADMINISTRATOR . '/helpers');
			if ($this->canManage)
			{
				$this->showStatusButtons($bar);
			}
			ToolBarHelper::spacer('20px');
			$linkMark = Route::_('index.php?option=com_jotcache&view=main&task=mark');
			$markid   = $this->app->input->cookie->get('jotcachemark', '0', 'int');
			$bar->appendButton('selector', 'markid', $markid, $linkMark);
			ToolBarHelper::spacer('20px');
			ToolBarHelper::custom('refresh', 'refresh.png', 'refresh.png', Text::_('JOTCACHE_RS_REFRESH'), false);
			if ($this->data->fastdelete)
			{
				ToolBarHelper::custom('delete', 'delete.png', 'delete.png', Text::_('JOTCACHE_RS_DELETE'), false);
			}
			else
			{
				ToolBarHelper::deleteList(Text::_('JOTCACHE_RS_DEL_CONFIRM'), 'delete');
			}
			if ($this->lists['current_Domain'])
			{
				ToolBarHelper::custom('deletedomain', 'deletedomain.png', 'deletedomain.png', Text::_('JOTCACHE_RS_DELETE_DOMAIN'), false);
			}
			ToolBarHelper::custom('deleteall', 'deleteall.png', 'deleteall.png', Text::_('JOTCACHE_RS_DELETE_ALL'), false);
			ToolBarHelper::custom('recache.display', 'recache.png', 'recache.png', Text::_('JOTCACHE_RS_RECACHE'), false);
			ToolBarHelper::spacer('35px');
			if ($this->canManage)
			{
				ToolBarHelper::preferences('com_jotcache', '500');
			}
			ToolBarHelper::help('Help', false, $this->url . 'intro');
		}
		
		private function showStatusButtons($bar)
		{
			$statusTitleP       = Text::_('JOTCACHE_RS_PLUGIN_NORMAL');
			$this->statusPlugin = JOTCACHE_STATUS_NORMAL;
			$linkP              = Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $this->lists['plgid']);
			if ($this->pars->enabled == 0)
			{
				$this->statusPlugin = JOTCACHE_STATUS_WARNING;
				$statusTitleP       = Text::_('JOTCACHE_RS_PLUGIN_WARNING');
			}
			else
			{
				if ($this->lists['last'])
				{
					$this->statusPlugin = JOTCACHE_STATUS_ATTENTION;
					$statusTitleP       = Text::_('JOTCACHE_RS_PLUGIN_ATTENTION');
				}
				else
				{
					$this->statusPlugin = JOTCACHE_STATUS_NORMAL;
				}
			}
			$bar->appendButton('status', 'statplugin', 'P', $statusTitleP, $linkP);
			$linkG              = Route::_('index.php?option=com_config');
			$this->statusGlobal = $this->status['gclass'];
			$bar->appendButton('status', 'statglobal', 'G', $this->status['gtitle'], $linkG);
			$linkC       = Route::_('index.php?option=com_cache');
			$storageType = isset($this->pars->storage->type) ? $this->pars->storage->type : 'file';
			if ($storageType == 'file')
			{
				$this->statusClear = JOTCACHE_STATUS_NORMAL;
				$statusTitleC      = Text::_('JOTCACHE_RS_CLEAR_NORMAL');
				if ($this->status['clear'] === 0)
				{
					$this->statusClear = JOTCACHE_STATUS_SPECIAL;
					$statusTitleC      = Text::_('JOTCACHE_RS_CLEAR_SPECIAL');
				}
				$bar->appendButton('status', 'statclear', 'C', $statusTitleC, $linkC);
			}
			else
			{
				$statusTitleC      = Text::_('JOTCACHE_RS_CLEAR_NONE');
				$this->statusClear = '';
				$bar->appendButton('status', 'statclear', 'C', $statusTitleC, $linkC);
			}
		}
		
		function tplex()
		{
			if ($this->canManage)
			{
				$this->app->input->set('hidemainmenu', true);
				$this->lists = $this->get('TplLists', 'Main');
				$this->setLayout("tplex");
				$this->sidebar = $this->renderSidebar('tplex');
				parent::display();
			}
		}
		
		function bcache()
		{
			if ($this->canManage)
			{
				$this->app->input->set('hidemainmenu', true);
				$this->data = $this->get('BcData', 'Main');
				$this->setLayout("bcache");
				$this->sidebar = $this->renderSidebar('bcache');
				parent::display();
			}
		}
		
		function extratime()
		{
			if ($this->canManage)
			{
				$this->app->input->set('hidemainmenu', true);
				$this->data = $this->get('EtData', 'Main');
				$this->setLayout("extratime");
				$this->sidebar = $this->renderSidebar('extratime');
				parent::display();
			}
		}
		
		function debug()
		{
			$this->pars       = $this->get('PluginParams', 'Main');
			$this->data       = $this->get('CachedContent', 'main');
			$this->data->mode = $this->app->input->getWord('mode');
			$this->fnameExt   = $this->app->input->getCmd('fname') . '.php';
			$this->setLayout("debug");
			parent::display();
		}
		
		protected function getSortFields()
		{
			if ($this->data->mode)
			{
				return [
					'm.uri'      => Text::_('JOTCACHE_RS_UTITLE'),
					'm.id'       => Text::_('JOTCACHE_RS_ID'),
					'm.ftime'    => Text::_('JOTCACHE_RS_CREATED'),
					'm.language' => Text::_('JOTCACHE_RS_LANG'),
					'm.browser'  => Text::_('JOTCACHE_RS_BROWSER')
				];
			}
			else
			{
				return [
					'm.title'    => Text::_('JOTCACHE_RS_PTITLE'),
					'm.id'       => Text::_('JOTCACHE_RS_ID'),
					'm.ftime'    => Text::_('JOTCACHE_RS_CREATED'),
					'm.language' => Text::_('JOTCACHE_RS_LANG'),
					'm.browser'  => Text::_('JOTCACHE_RS_BROWSER')
				];
			}
		}
	}