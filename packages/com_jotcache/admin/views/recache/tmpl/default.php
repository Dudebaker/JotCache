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
	
	defined('_JEXEC') or die('Restricted access');
	$site_url = URI::root();
	$scope    = 'all';
	if ($this->filter['search'] || $this->filter['com'] || $this->filter['view'] || $this->filter['mark'])
	{
		$scope = 'sel';
	}
	if ($this->filter['chck'])
	{
		$scope = 'chck';
	} ?>
    <script language="javascript" type="text/javascript">
        var jotcachereq = "<?php echo Route::_($site_url . 'index.php?option=com_jotcache&view=recache&task=ajax.status&format=raw') ?>";
        var jotcacheflag = 1;
        var jotcacheform = "adminForm";
        Joomla.submitbutton = function (task) {
            if (task === 'close') {
                self.close();
            } else {
                jotcacheform = "adminForm_" + document.id('myTabTabs').getElement('li.active a').get('text');
                if (task === 'recache.start') {
                    jotcacheajax.again();
                }
                if (task === 'recache.stop') {
                    jotcacheflag = 0;
                    return;
                }
                Joomla.submitform(task, document.getElementById(jotcacheform));
            }
        };
    </script>
    <table class="statuslist">
        <tr>
            <td class="status-title"><?php echo Text::_('JOTCACHE_RECACHE_STATUS'); ?></td>
            <td>&nbsp;</td>
            <td><span><img src="/administrator/components/com_jotcache/assets/images/loader.gif" id="spinner-here" style="display:none;
  margin-right:5px;"></img></span><span id="message-here"></span></td>
        </tr>
    </table>
    <hr/>
<?php
	if (count($this->plugins) > 0)
	{
		echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', ['active' => $this->plugins[0]->element]);
		foreach ($this->plugins as $plugin)
		{
			$pluginTitle = ucfirst($plugin->element);
			echo HTMLHelper::_('bootstrap.addTab', 'myTab', $plugin->element, $pluginTitle);
			include JPATH_PLUGINS . '/jotcacheplugins/' . $plugin->element . '/' . $plugin->element . '_form.php';
			echo HTMLHelper::_('bootstrap.endTab');
		}
		echo HTMLHelper::_('bootstrap.endTabSet');
	}
	else
	{
		?>
        <div style="color:red;"><?php echo Text::_('JOTCACHE_RECACHE_NO_PLUGINS'); ?></div>
	<?php } ?>