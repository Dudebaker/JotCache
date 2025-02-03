<?php
	/**
	 * @package         JotCache
	 * @subpackage      JotCachePlugins.Recache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	defined('_JEXEC') or die;
	
	use Joomla\CMS\Filter\InputFilter;
	use Joomla\CMS\Language\Text;
	use Joomla\CMS\MVC\Controller\BaseController;
	use Joomla\CMS\Uri\Uri;
	use Joomla\Utilities\ArrayHelper;
	
	class MainController extends BaseController
	{
		protected $model;
		
		public function __construct($config = [])
		{
			parent::__construct($config);
			$this->registerTask('apply', 'save');
			$this->registerTask('tplapply', 'tplsave');
			$this->registerTask('bcapply', 'bcsave');
			$this->registerTask('etapply', 'etsave');
			$this->model = $this->getModel();
		}
		
		public function refresh() : void
		{
			$this->model->refresh();
			parent::display();
		}
		
		public function display($cachable = false, $urlparams = false) : void
		{
			parent::display();
		}
		
		public function mark() : void
		{
			$markid = $this->input->getInt('markid');
			$line   = "option=com_jotcache&view=main";
			$this->model->resetMark();
			$uri    = Uri::getInstance();
			$domain = $uri->toString(['host']);
			$parts  = explode('.', $domain);
			$last   = count($parts) - 1;
			if ($last >= 1 && is_numeric($parts[$last]) === false)
			{
				$domain = $parts[$last - 1] . '.' . $parts[$last];
			}
			switch ($markid)
			{
				case 0:
					setcookie('jotcachemark', '0', '0', '/', $domain);
					$this->setRedirect('index.php?' . $line . "&filter_mark=", Text::_('JOTCACHE_RS_MSG_RESET'));
					break;
				case 1:
					setcookie('jotcachemark', '1', '0', '/', $domain);
					$this->setRedirect('index.php?' . $line, Text::_('JOTCACHE_RS_MSG_SET'));
					break;
				case 2:
					setcookie('jotcachemark', '2', '0', '/', $domain);
					$this->setRedirect('index.php?' . $line, Text::_('JOTCACHE_RS_MSG_RENEW'));
					break;
				default:
					break;
			}
		}
		
		public function renew() : void
		{
			$token = $this->input->getCmd('token', '');
			if (strlen($token) == 32)
			{
				$this->model->renew($token);
				$url = $_SERVER['HTTP_REFERER'];
				$this->setRedirect($url);
			}
		}
		
		public function delete() : void
		{
			$this->model->delete();
			$this->setRedirect('index.php?option=com_jotcache&view=main', Text::_('JOTCACHE_RS_DEL'));
		}
		
		public function deletedomain() : void
		{
			$this->model->deletedomain();
			$this->setRedirect('index.php?option=com_jotcache&view=main', Text::_('JOTCACHE_RS_DEL'));
		}
		
		public function deleteall() : void
		{
			$this->model->deleteall();
			$this->setRedirect('index.php?option=com_jotcache&view=main', Text::_('JOTCACHE_RS_DEL'));
		}
		
		public function exclude() : void
		{
			$view = $this->getView('Main', 'html');
			$view->setModel($this->model);
			$view->exclude();
		}
		
		public function tplex() : void
		{
			$view = $this->getView('Main', 'html');
			$view->setModel($this->model);
			$view->tplex();
		}
		
		public function bcache() : void
		{
			$view = $this->getView('Main', 'html');
			$view->setModel($this->model);
			$view->bcache();
		}
		
		public function extratime() : void
		{
			$view = $this->getView('Main', 'html');
			$view->setModel($this->model);
			$view->extratime();
		}
		
		public function debug() : void
		{
			$view = $this->getView('Main', 'html');
			$view->setModel($this->model);
			$view->debug();
		}
		
		public function save() : void
		{
			$post = $this->input->post->getArray();
			$cid  = $this->input->post->get('cid', [0], 'array');
			ArrayHelper::toInteger($cid, [0]);
			$msg = '';
			if ($this->model->store($post, $cid))
			{
				$msg = Text::_('JOTCACHE_EXCLUDE_SAVE');
			}
			if ($this->getTask() == 'save')
			{
				$this->setRedirect('index.php?option=com_jotcache&view=main&task=refresh', $msg);
			}
			else
			{
				$this->setRedirect('index.php?option=com_jotcache&view=main&task=exclude', $msg);
			}
		}
		
		public function tplsave() : void
		{
			$post   = $this->input->post->getArray();
			$cids   = $this->input->post->get('cid', [0], 'array');
			$cids   = array_map(function ($src)
			{
				return InputFilter::getInstance([], [], 1, 1)->clean($src, 'CMD');
			}, $cids);
			$tpl_id = $this->model->tplstore($post, $cids);
			$msg    = '';
			if ($tpl_id > 0)
			{
				$msg = Text::_('JOTCACHE_TPLEX_SAVE');
			}
			if ($this->getTask() == 'tplsave')
			{
				$this->setRedirect('index.php?option=com_jotcache&view=main&task=display&sel_id=' . $tpl_id, $msg);
			}
			else
			{
				$this->setRedirect('index.php?option=com_jotcache&view=main&task=tplex&sel_id=' . $tpl_id, $msg);
			}
		}
		
		public function bcsave() : void
		{
			$post = $this->input->post->getArray();
			$msg  = '';
			if ($this->model->extraStore($post, 2))
			{
				$msg = Text::_('JOTCACHE_EXCLUDE_SAVE');
			}
			if ($this->getTask() == 'bcsave')
			{
				$this->setRedirect('index.php?option=com_jotcache&view=main&task=display', $msg);
			}
			else
			{
				$this->setRedirect('index.php?option=com_jotcache&view=main&task=bcache', $msg);
			}
		}
		
		public function bcdelete() : void
		{
			$msg = '';
			if ($this->model->extraDelete(2))
			{
				$msg = Text::_('JOTCACHE_RS_DEL');
			}
			$this->setRedirect('index.php?option=com_jotcache&view=main&task=bcache', $msg);
		}
		
		public function etsave() : void
		{
			$post = $this->input->post->getArray();
			$msg  = '';
			if ($this->model->extraStore($post, 6))
			{
				$msg = Text::_('JOTCACHE_DATA_SAVED');
			}
			if ($this->getTask() == 'etsave')
			{
				$this->setRedirect('index.php?option=com_jotcache&view=main&task=display', $msg);
			}
			else
			{
				$this->setRedirect('index.php?option=com_jotcache&view=main&task=extratime', $msg);
			}
		}
		
		public function etdelete() : void
		{
			$msg = '';
			if ($this->model->extraDelete(6))
			{
				$msg = Text::_('JOTCACHE_DATA_DELETED');
			}
			$this->setRedirect('index.php?option=com_jotcache&view=main&task=extratime', $msg);
		}
	}