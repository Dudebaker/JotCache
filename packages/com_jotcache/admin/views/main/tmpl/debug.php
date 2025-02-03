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
	use Joomla\CMS\Router\Route;
	use Joomla\CMS\Session\Session;
	use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
	use Joomla\CMS\Toolbar\ToolbarHelper;
	use Joomla\CMS\Uri\Uri;
	
	defined('_JEXEC') or die;
	HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.keepalive');
	ToolbarHelper::title(Text::_('JOTCACHE_DEBUG_TITLE'), 'jotcache-logo.gif');
	$site_url = URI::root();
	$bar      = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar('toolbar');
	$msg      = Text::_('JOTCACHE_RS_REFRESH_DESC');
	ToolbarHelper::cancel('display', Text::_('CLOSE'));
	ToolbarHelper::spacer();
	ToolbarHelper::help('Help', false, $this->url . 'check');
	$msg        = ($this->data->error) ? '<span style="color:red;"> ' . Text::_('ERROR') . ' </span>' : ' ' . Text::_('JOTCACHE_DEBUG_COUNT') . '[' . $this->data->count . '] ';
	$config     = Factory::getApplication()?->getConfig();
	$cache_dir  = $config->get('cache_path', JPATH_ROOT . '/cache');
	$data_path  = $cache_dir . '/page/' . $this->fnameExt;
	$token      = Session::getFormToken();
	$data_title = (file_exists($data_path)) ? '<a href="' . Route::_("index.php?option=com_jotcache&view=reset&task=getcachedfile&$token=1&fname=" . $this->fnameExt) . '">' . $this->data->title . '</a>' : $this->data->title;
?>
<form action="<?php echo Route::_('index.php'); ?>" method="post" name="adminForm" id="adminForm">
    <h3><?php echo sprintf(Text::_('JOTCACHE_DEBUG_INFO'), $data_title, $msg); ?> : </h3>
    <textarea style="width:100%;" rows="25">
    <?php echo $this->data->content; ?>
  </textarea>
	<?php
		if (isset($this->data->parts))
		{
			foreach ($this->data->parts as $key => $value)
			{
				echo "<p>$key</p>";
				?>
                <textarea style="width:100%;" rows="25">
      <?php echo $value; ?>
      </textarea>
			<?php }
		} ?>
	
	<?php ?>
    <input type="hidden" name="option" value="com_jotcache"/>
    <input type="hidden" name="view" value="main"/>
    <input type="hidden" name="task" value="debug"/>
    <input type="hidden" name="boxchecked" value="0"/>
    <input type="hidden" name="hidemainmenu" value="0"/>
</form>
