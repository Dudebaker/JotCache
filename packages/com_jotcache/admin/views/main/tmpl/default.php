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
	use Joomla\CMS\Router\Route;
	use Joomla\CMS\Uri\Uri;
	
	defined('_JEXEC') or die;
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.multiselect');
	HTMLHelper::_('formbehavior.chosen', 'select');
	HTMLHelper::_('bootstrap.modal');
	$site_url   = URI::root();
	$mode       = $this->data->mode;
	$listOrder  = $this->lists['order'];
	$listDirn   = $this->lists['order_Dir'];
	$sortFields = $this->getSortFields();
?>
<script language="javascript" type="text/javascript">
    Joomla.submitbutton = function (task) {
        if (task === 'deleteall' || task === 'deletedomain') {
            if (confirm("<?php echo Text::_('JOTCACHE_RS_DELETE_ALL_CONFIRM'); ?>") !== true) {
                return;
            }
        }
        if (task === 'recache.display' && jQuery('#jotcache-refresh-hint').length == 0) {
            jQuery('#system-message-container').append('<button data-dismiss="alert" class="close" type="button">×</button><div class="alert alert-info"><h4 id="jotcache-refresh-hint" class="alert-heading"><?php echo Text::_('JOTCACHE_RS_MSG_INFO'); ?></h4><p class="alert-message"><?php echo Text::_('JOTCACHE_RS_MSG_HINT'); ?></p></div>');
        }
        jotcache.submitform(task);
        /*    Joomla.submitform(task); */
    };
    Joomla.submitform = function (task, form) {
        jotcache.submitform(task, form);
    };
    orderTable = function () {
        var table = document.getElementById("sortTable");
        var direction = document.getElementById("directionTable");
        var order = table.options[table.selectedIndex].value;
        var dirn;
        if (order != '<?php echo $listOrder; ?>') {
            dirn = 'asc';
        } else {
            dirn = direction.options[direction.selectedIndex].value;
        }
        Joomla.tableOrdering(order, dirn, '');
    };
    alertBox = function (qs) {
        var box1 = SqueezeBox.initialize();
        box1.resize({x: 850, y: 100});
        var newElem = new Element('div');
        newElem.setStyle('font-weight', 'bold');
        newElem.setStyle('padding-bottom', '10px');
        newElem.set('html', '<div><p><?php echo Text::_('JOTCACHE_RS_QS_TITLE') . ' ' .
		                                        Text::_('JOTCACHE_RS_QS_NOTE'); ?></p><input type="text" style="width:800px;" value="' + qs + '"><p style="font-weight:400;font-style:italic;text-align:right;padding-right:50px;"><?php echo Text::_('JOTCACHE_RS_QS_DESC'); ?></p></div>');
        box1.setContent('adopt', newElem);
    }
</script>
<style type="text/css">
    #toolbar-statplugin button.btn {
        background-image: linear-gradient(to bottom, <?php echo $this->statusPlugin; ?>);
    }

    #toolbar-statglobal button.btn {
        background-image: linear-gradient(to bottom, <?php echo $this->statusGlobal; ?>);
    }

    #toolbar-statclear button.btn {
        background-image: linear-gradient(to bottom, <?php echo $this->statusClear; ?>);
    }
</style>
<form action="<?php echo Route::_('index.php?option=com_jotcache'); ?>" method="post" name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)): ?>
    <div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="span10">
		<?php else : ?>
        <div id="j-main-container">
			<?php endif; ?>
			
			<?php
				if ($this->pars->enabled == 0 || $this->lists['last'])
				{
					?>
                    <span style="color:red;"><?php
							echo Text::_('JOTCACHE_RS_PLUGIN') . " ";
							echo ($this->pars->enabled == 0) ? Text::_('JOTCACHE_RS_DISABLED') : "";
						?><?php
							echo ($this->lists['last']) ? Text::_('JOTCACHE_RS_NOT_LAST') . " " : "";
							echo ($this->lists['plgid']) ? '<a href="' . Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $this->lists['plgid']) . '"> ' . Text::_('JOTCACHE_RS_PLG_LINK') . '</a>' : "";
						?></span>
				<?php } ?>
            <div id="filter-bar" class="btn-toolbar">
                <div class="filter-search btn-group pull-left">
					<?php
						if ($mode)
						{
							$search_text = 'JOTCACHE_RS_USEARCH';
						}
						else
						{
							$search_text = 'JOTCACHE_RS_PSEARCH';
						} ?>
                    <label for="filter_search" class="element-invisible"><?php echo Text::_($search_text); ?></label>
                    <input type="text" name="filter_search" id="filter_search" placeholder="<?php echo Text::_($search_text); ?>"
                           value="<?php echo $this->escape($this->lists['search']); ?>"
                           title="<?php echo Text::_($search_text); ?>" onChange="jotcache.resetSelect(0);"/>
                </div>
                <div class="btn-group pull-left">
                    <button class="btn hasTooltip" type="button" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"
                            onclick="jotcache.resetSelect(0);"><i class="icon-search"></i></button>
                    <button class="btn hasTooltip" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.id('filter_search').value = '';
              jotcache.resetSelect(0);"><i class="icon-remove"></i></button>
                </div>
                <div class="btn-group pull-right hidden-phone">
                    <label for="limit"
                           class="element-invisible"><?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?></label>
					<?php echo $this->data->pageNav->getLimitBox(); ?>
                </div>
                <div class="btn-group pull-right hidden-phone">
                    <label for="directionTable" class="element-invisible"><?php echo Text::_('JFIELD_ORDERING_DESC'); ?></label>
                    <select name="directionTable" id="directionTable" class="input-medium" onchange="orderTable()">
                        <option value=""><?php echo Text::_('JFIELD_ORDERING_DESC'); ?></option>
                        <option
                                value="asc" <?php if ($listDirn == 'asc')
						{
							echo 'selected="selected"';
						} ?>><?php echo Text::_('JGLOBAL_ORDER_ASCENDING'); ?></option>
                        <option
                                value="desc" <?php if ($listDirn == 'desc')
						{
							echo 'selected="selected"';
						} ?>><?php echo Text::_('JGLOBAL_ORDER_DESCENDING'); ?></option>
                    </select>
                </div>
                <div class="btn-group pull-right">
                    <label for="sortTable" class="element-invisible"><?php echo Text::_('JGLOBAL_SORT_BY'); ?></label>
                    <select name="sortTable" id="sortTable" class="input-medium" onchange="orderTable()">
                        <option value=""><?php echo Text::_('JGLOBAL_SORT_BY'); ?></option>
						<?php echo HTMLHelper::_('select.options', $sortFields, 'value', 'text', $listOrder); ?>
                    </select>
                </div>
            </div>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th width="50">#</th>
                    <th width="5"><input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);"/></th>
                    <th class="title"><?php
							if ($mode)
							{
								echo HTMLHelper::_('grid.sort', 'JOTCACHE_RS_UTITLE', 'm.uri', @$this->lists['order_Dir'], @$this->lists['order']);
							}
							else
							{
								echo HTMLHelper::_('grid.sort', 'JOTCACHE_RS_PTITLE', 'm.title', @$this->lists['order_Dir'], @$this->lists['order']);
							} ?></th>
					<?php if ($this->data->showfname) { ?>
                        <th nowrap="true"><?php echo Text::_('JOTCACHE_RS_FNAME'); ?></th>
					<?php } ?>
                    <th nowrap="nowrap"><?php echo Text::_('JOTCACHE_RS_COMP'); ?></th>
                    <th nowrap="true"><?php echo Text::_('JOTCACHE_RS_VIEW'); ?></th>
                    <th><?php echo HTMLHelper::_('grid.sort', 'JOTCACHE_RS_ID', 'm.id', $this->lists['order_Dir'], $this->lists['order']); ?></th>
                    <th nowrap="nowrap"><?php echo HTMLHelper::_('grid.sort', 'JOTCACHE_RS_CREATED', 'm.ftime', $this->lists['order_Dir'], $this->lists['order']); ?></th>
                    <th nowrap="nowrap"><?php echo HTMLHelper::_('grid.sort', 'JOTCACHE_RS_LANG', 'm.language', @$this->lists['order_Dir'], @$this->lists['order']); ?></th>
                    <th nowrap="nowrap"><?php echo HTMLHelper::_('grid.sort', 'JOTCACHE_RS_BROWSER', 'm.browser', @$this->lists['order_Dir'], @$this->lists['order']); ?></th>
					<?php if ($this->showcookies) { ?>
                        <th nowrap="true"><?php echo Text::_('JOTCACHE_RS_COOKIES'); ?></th>
					<?php } ?>
					<?php if ($this->showsessionvars) { ?>
                        <th nowrap="true"><?php echo Text::_('JOTCACHE_RS_SESSIONVARS'); ?></th>
					<?php } ?>
                    <th nowrap="nowrap"><?php echo Text::_('JOTCACHE_RS_MARK'); ?></th>
                </tr>
                </thead>
				<?php
					$rows = $this->data->rows;
					$k    = 0;
					for ($i = 0, $n = count($rows); $i < $n; $i++)
					{
						$row     = $rows[$i];
						$checked = '<input type="checkbox" onclick="Joomla.isChecked(this.checked);" value="' . $row->fname . '" name="cid[]" id="cb' . $i . '">';
						$expired = strlen($row->ftime) > 20 ? ' style="font-style: italic;"' : '';
						$mark_qs = '';
						$raw     = unserialize($row->qs);
						$qs      = urldecode(http_build_query($raw, '', '&'));
						if ($row->mark == 1)
						{
							if (strlen($row->qs) > 0)
							{
								$mark_qs = $site_url . 'index.php?' . $qs;
								$mark_qs = '<a href="' . $mark_qs . '" target="_blank">' . Text::_('JOTCACHE_RS_SEL_MARK_YES') . '</a>';
							}
							else
							{
								$mark_qs = Text::_('JOTCACHE_RS_SEL_MARK_YES');
							}
						} ?>
                        <tr class="<?php echo "row$k"; ?>" <?php echo $expired; ?>>
                            <td align="right"><?php echo $this->data->pageNav->getRowOffset($i); ?></td>
                            <td align="center"><?php echo $checked; ?></td>
                            <td><a href="#" class="modal" onclick="alertBox('<?php echo $qs; ?>');"
                                   title="<?php echo Text::_('JOTCACHE_RS_QS_TITLE'); ?>"><i class="icon-eye"></i></a>
								<?php if ($mode) { ?>
                                <a href="<?php echo $row->uri; ?>" target="_blank"
                                   title="<?php echo $row->title; ?>"><?php echo $row->uri; ?></a></td>
							<?php } else { ?>
                                <a href="<?php echo $row->uri; ?>" target="_blank"><?php echo $row->title; ?></a></td>
							<?php } ?>
							<?php if ($this->data->showfname) { ?>
                                <td><a
                                            href="<?php echo Route::_('index.php?option=com_jotcache&view=main&task=debug&mode=preview&fname=' . $row->fname); ?>"
                                            target="_top" title="<?php echo $row->title; ?>"><?php echo $row->fname; ?></a></td>
							<?php } ?>
                            <td><?php echo $row->com; ?></td>
                            <td><?php echo $row->view; ?></td>
                            <td align="right" style="padding-right:30px;"><?php echo $row->id; ?></td>
                            <td align="center"><?php echo $row->ftime; ?></td>
                            <td align="center"><?php echo $row->language; ?></td>
                            <td align="center"><?php echo $row->browser; ?></td>
							<?php
								if ($this->showcookies)
								{
									$rcookies = substr($row->cookies, 1);
									$cookies  = explode('#', $rcookies);
									?>
                                    <td align="center" style="padding-top:0;padding-left:0;">
                                        <table class="showcookies"><?php
												foreach ($cookies as $cookie)
												{
													echo '<tr><td  style="border:0;">' . $cookie . '</td></tr>';
												} ?></table>
                                    </td>
								<?php } ?>
							<?php
								if ($this->showsessionvars)
								{
									$rvars = substr($row->sessionvars, 1);
									$vars  = explode('#', $rvars);
									?>
                                    <td align="center" style="padding-top:0;padding-left:0;">
                                        <table class="showcookies"><?php
												foreach ($vars as $var)
												{
													echo '<tr><td  style="border:0;">' . $var . '</td></tr>';
												} ?></table>
                                    </td>
								<?php } ?>
                            <td align="center"><?php echo $mark_qs; ?></td>
                        </tr>
						<?php
						$k = 1 - $k;
					} ?>
            </table>
            <br/>
			<?php echo $this->data->pageNav->getListFooter(); ?>
            <input type="hidden" id="form_view" name="view" value="main"/>
            <input type="hidden" id="form_task" name="task" value=""/>
            <input type="hidden" name="boxchecked" value="0"/>
            <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
            <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
        </div>
</form>