<?php
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\HTML\HTMLHelper;
	use Joomla\CMS\Language\Text;
	use Joomla\CMS\Toolbar\ToolbarHelper;
	use Joomla\CMS\Uri\Uri;
	
	defined('_JEXEC') or die('Restricted access');
	HTMLHelper::_('bootstrap.tooltip');
	if ($this->pars->urlselection)
	{
		ToolBarHelper::title(Text::_('JOTCACHE_INCLUDE_TITLE'), 'jotcache-logo.gif');
	}
	else
	{
		ToolBarHelper::title(Text::_('JOTCACHE_EXCLUDE_TITLE'), 'jotcache-logo.gif');
	}
	$site_url = URI::root();
	ToolBarHelper::custom('apply', 'apply.png', 'apply.png', 'JAPPLY', false);
	ToolBarHelper::spacer();
	ToolBarHelper::custom('save', 'save.png', 'save.png', 'JSAVE', false);
	ToolBarHelper::spacer();
	ToolBarHelper::cancel('display', Text::_('JCANCEL'));
	ToolBarHelper::spacer();
	ToolBarHelper::help('JotCacheHelp', false, $this->url . 'url-rules');
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
                    <th nowrap="nowrap" width="120"><input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);"/>&nbsp;<?php echo($this->pars->urlselection ? Text::_('JOTCACHE_EXCLUDE_INCLUDED') : Text::_('JOTCACHE_EXCLUDE_EXCLUDED')); ?></th>
                    <th nowrap="nowrap"><?php echo Text::_('JOTCACHE_EXCLUDE_CN'); ?></th>
                    <th><?php echo Text::_('JOTCACHE_EXCLUDE_OPTION'); ?></th>
                    <th title="<?php echo Text::_('JOTCACHE_EXCLUDE_VIEWS_DESC'); ?>"><?php echo($this->pars->urlselection ? Text::_('JOTCACHE_INCLUDE_VIEWS') : Text::_('JOTCACHE_EXCLUDE_VIEWS')); ?></th>
                </tr>
                </thead>
				<?php
					$rows = $this->data->rows;
					$k    = 0;
					for ($i = 0, $n = count($rows); $i < $n; $i++)
					{
						$row      = $rows[$i];
						$checking = array_key_exists($row->option, $this->data->exclude) ? "checked" : "";
						$checked  = '<input type="checkbox" id="cb' . $i . '" name="cid[]" value="' . $row->id . '" ' . $checking . ' onclick="Joomla.isChecked(this.checked);" />';
						?>
                        <tr class="<?php echo "row$k"; ?>">
                            <td align="center"><?php echo $checked; ?></td>
                            <td><?php echo $row->name; ?></td>
                            <td><?php echo $row->option; ?></td>
                            <td width="70%"><?php if ($checking and $this->data->exclude[$row->option] != 1) { ?>
                                    <input name="<?php echo "ex_$row->option"; ?>" style="width:90%;" value="<?php echo $this->data->exclude[$row->option]; ?>">
								<?php } else { ?>
                                    <input name="<?php echo "ex_$row->option"; ?>" style="width:90%;" value="">
								<?php } ?>
                            </td>
                        </tr>
						<?php
						$k = 1 - $k;
					} ?>
            </table>
            <br/>
            <input type="hidden" name="option" value="com_jotcache"/>
            <input type="hidden" name="view" value="main"/>
            <input type="hidden" name="task" value="exclude"/>
            <input type="hidden" name="boxchecked" value="0"/>
</form>