<?php
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	defined('JPATH_BASE') or die;
	
	interface JotcacheStorageBase
	{
		function get();
		
		function store($data);
		
		function remove($path);
		
		function autoclean();
	}