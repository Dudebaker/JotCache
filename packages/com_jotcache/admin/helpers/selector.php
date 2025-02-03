<?php
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Language\Text;
	use Joomla\CMS\Toolbar\ToolbarButton;
	
	defined('JPATH_PLATFORM') or die;
	
	class JToolbarButtonSelector extends ToolbarButton
	{
		protected $_name = 'Selector';
		
		public function fetchButton($type = 'Selector', $name = '', $value = '', $link = '')
		{
			$selected = ['', '', ''];
			if ($value > 2 || $value < 0)
			{
				$value = 0;
			}
			$selected[$value] = 'selected';
			$htm              =
				'<form class="hasTooltip" data-original-title="' . Text::_('JOTCACHE_RS_SELECTOR_INFO') . '" action="' . $link . '" method="post" name="frontForm" id="jotcache-selector-form"><select name="' . $name . '" id="' . $name . '" onchange="this.form.submit()"><option value="0" ' .
				$selected[0] . '>' . Text::_('JOTCACHE_RS_SELECTOR_NORMAL') . '</option><option value="1" ' . $selected[1] . '>' . Text::_('JOTCACHE_RS_SELECTOR_MARK') . '</option><option value="2" ' . $selected[2] . '>' . Text::_('JOTCACHE_RS_SELECTOR_RENEW') . '</option></select></form>';
			
			return $htm;
		}
		
		public function fetchId($type = 'Selector', $name = '', $text = '', $task = '', $list = true, $hideMenu = false)
		{
			return $this->_parent->getName() . '-' . $name;
		}
	}