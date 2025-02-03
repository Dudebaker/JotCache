<?php
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Form\FormField;
	use Joomla\CMS\Language\Text;
	
	defined('JPATH_PLATFORM') or die;
	include_once JPATH_ADMINISTRATOR . '/components/com_jotcache/helpers/browseragents.php';
	
	class JFormFieldBrowsers extends FormField
	{
		protected $type = 'Browsers';
		protected $browsers;
		
		public function __construct()
		{
			$this->browsers = BrowserAgents::getBrowserAgents();
			parent::__construct();
		}
		
		protected function getInput()
		{
			$html  = [];
			$flags = ['JOTCACHE_EXCLUDED', 'JOTCACHE_COMMON', 'JOTCACHE_INDIVIDUAL'];
			foreach ($this->browsers as $key => $value)
			{
				$key    = str_replace(".", "", $key);
				$inputs = '';
				for ($i = 0; $i < 3; $i++)
				{
					if (is_array($this->value) && array_key_exists($key, $this->value))
					{
						$checked = ((string) $i == (string) $this->value[$key]) ? ' checked="checked"' : '';
					}
					else
					{
						$checked = ($i == 2) ? ' checked="checked"' : '';
					}
					$inputs .= '<input type="radio" value="' . $i . '" name="jform[params][cacheclient][' . $key . ']" id="jform_params_cacheclient_' . $key . $i . '"' . $checked . '>
      <label for="jform_params_cacheclient_' . $key . $i . '" class="btn">' . Text::_($flags[$i]) . '</label>';
				}
				$html[] = '<div class="control-group" style="margin-bottom: 0;">
  <div class="control-label">
    <label title="' . $value[0] . '" for="jform_params_cacheclient_' . $key . '" id="jform_params_cacheclient_' . $key . '-lbl">' . $value[0] . '</label>
  </div>
  <div class="controls">
    <fieldset class="radio btn-group" id="jform_params_cacheclient_' . $key . '">' .
				          $inputs . '
    </fieldset>
  </div>
</div>';
			}
			
			return implode($html);
		}
	}