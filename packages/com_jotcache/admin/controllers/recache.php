<?php
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Language\Text;
	use Joomla\CMS\MVC\Controller\BaseController;
	use Joomla\CMS\Session\Session;
	
	defined('_JEXEC') or die;
	
	class MainControllerRecache extends BaseController
	{
		protected $model;
		
		public function __construct($config = [])
		{
			parent::__construct($config);
			$this->model = $this->getModel('recache');
		}
		
		function display($cachable = false, $urlparams = false)
		{
			$view = $this->getView('recache', 'html');
			$view->setModel($this->model, true);
			$cids = $this->input->get('cid', [0], 'array');
			if (count($cids) > 0)
			{
				$this->model->flagRecache($cids);
			}
			$view->display();
		}
		
		function close()
		{
			$this->setRedirect('index.php?option=com_jotcache&view=main&task=display');
		}
		
		function start()
		{
			Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
			$this->model->runRecache();
			$this->model->controlRecache(0);
			$view = $this->getView('recache', 'html');
			$view->stopRecache();
		}
		
		function stop()
		{
			$this->model->controlRecache(0);
		}
	}