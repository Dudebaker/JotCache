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
	$site_url = URI::root();
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.multiselect');
	HTMLHelper::_('formbehavior.chosen', 'select');
	ToolBarHelper::title(Text::_('JOTCACHE_TPLEX_TITLE'), 'jotcache-logo.gif');
	$msg = Text::_('JOTCACHE_RS_REFRESH_DESC');
	ToolBarHelper::custom('tplapply', 'apply.png', 'apply.png', 'JAPPLY', false);
	ToolBarHelper::spacer();
	ToolBarHelper::custom('tplsave', 'save.png', 'save.png', 'JSAVE', false);
	ToolBarHelper::spacer();
	ToolBarHelper::cancel('close', Text::_('JCANCEL'));
	ToolBarHelper::spacer();
	ToolBarHelper::help('JotCacheHelp', false, $this->url . 'exclusion');
	$rows = $this->lists['pos'];
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
            <table class="table table-striped span6">
                <thead>
                <tr>
                    <th class="span2"><input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);"/>&nbsp;<?php echo Text::_('JOTCACHE_EXCLUDE_EXCLUDED'); ?></th>
                    <th class="span4"><?php echo Text::_('JOTCACHE_TPLEX_POS'); ?></th>
                </tr>
                </thead>
				<?php
					$k = 0;
					for ($i = 0, $n = count($rows); $i < $n; $i++)
					{
						$row      = &$rows[$i];
						$checking = array_key_exists($row, $this->lists['value']) ? "checked" : "";
						$checked  = '<input type="checkbox" id="cb' . $i . '" name="cid[]" value="' . $row . '" ' . $checking . ' onclick="jotcache.valoff(this);isChecked(this.checked);" />';
						?>
                        <tr class="<?php echo "row$k"; ?>">
                            <td align="center"><?php echo $checked; ?></td>
                            <td><?php echo $row; ?></td>
                        </tr>
						<?php
						$k = 1 - $k;
					} ?>
            </table>
            <br/>
            <input type="hidden" name="option" value="com_jotcache"/>
            <input type="hidden" name="view" value="main"/>
            <input type="hidden" name="task" value="tplex"/>
            <input type="hidden" name="boxchecked" value="0"/>
</form>