<?php
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\MVC\Controller\BaseController;
	
	defined('_JEXEC') or die;
	$controller = BaseController::getInstance('Jotcache');
	$controller->execute(Factory::getApplication()->input->get('task'));
	$controller->redirect();
