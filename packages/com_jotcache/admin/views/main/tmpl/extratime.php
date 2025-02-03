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
	use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
	use Joomla\CMS\Toolbar\ToolbarHelper;
	
	defined('_JEXEC') or die('Restricted access');
	HTMLHelper::_('bootstrap.tooltip');
	ToolBarHelper::title(Text::_('JOTCACHE_EXTRATIME_TITLE'), 'jotcache-logo.gif');
	$bar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar('toolbar');
	$msg = Text::_('JOTCACHE_RS_REFRESH_DESC');
	ToolBarHelper::custom('etapply', 'apply.png', 'apply.png', 'Apply', false);
	ToolBarHelper::spacer();
	ToolBarHelper::custom('etsave', 'save.png', 'save.png', 'Save', false);
	ToolBarHelper::spacer();
	ToolBarHelper::deleteList(Text::_('JOTCACHE_RS_DEL_CONFIRM'), 'etdelete');
	ToolBarHelper::spacer();
	ToolBarHelper::cancel('display', Text::_('CLOSE'));
	ToolBarHelper::spacer();
	ToolBarHelper::help('JotCacheHelp', false, $this->url . 'extra_time');
?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)): ?>
    <div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="span10">
		<?php else : ?>
        <div id="j-main-container">
			<?php endif; ?>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th nowrap="nowrap" width="120"><input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);"/>&nbsp;<?php echo Text::_('JOTCACHE_EXTRATIME_CHECK'); ?></th>
                    <th align="center"><?php echo Text::_('JOTCACHE_EXTRATIME_URI'); ?></th>
                    <th title="<?php echo Text::_('JOTCACHE_EXTRATIME_TIME'); ?>"><?php echo Text::_('JOTCACHE_EXTRATIME_TIME'); ?></th>
                </tr>
                </thead>
				<?php
					$rows = $this->data;
					$stop = count($rows);
					$k    = 0;
					for ($i = 0, $n = 20; $i < $n; $i++)
					{
						if ($i >= $stop)
						{
							$id   = 0;
							$uri  = '';
							$time = '';
							$pfx  = "ix" . $i;
							$pfy  = "iy" . $i;
						}
						else
						{
							$row  = &$rows[$i];
							$id   = $row->id;
							$uri  = $row->value;
							$time = $row->name;
							$pfx  = "ux" . $id;
							$pfy  = "uy" . $id;
						}
						$checked = '<input type="checkbox" id="cb' . $i . '" name="cid[]" value="' . $id . '" onclick="Joomla.isChecked(this.checked);" />';
						?>
                        <tr class="<?php echo "row$k"; ?>">
                            <td align="center" width="10%"><?php echo $checked; ?></td>
                            <td width="70%"><input id="<?php echo "$pfx"; ?>" name="<?php echo "$pfx"; ?>" style="width: 100%;" value="<?php echo $uri; ?>"></td>
                            <td width="20%"><input id="<?php echo "$pfy"; ?>" name="<?php echo "$pfy"; ?>" style="width:20%;" value="<?php echo $time; ?>"></td>
                        </tr>
						<?php
						$k = 1 - $k;
					} ?>
            </table>
            <br/>
            <input type="hidden" name="option" value="com_jotcache"/>
            <input type="hidden" name="view" value="main"/>
            <input type="hidden" name="task" value="extratime"/>
            <input type="hidden" name="boxchecked" value="0"/>
</form>