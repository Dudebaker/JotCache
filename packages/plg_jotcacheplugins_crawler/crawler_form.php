<?php
	/**
	 * @package         JotCache
	 * @subpackage      JotCachePlugins.Crawler
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	use Joomla\CMS\HTML\HTMLHelper;
	use Joomla\CMS\Language\Text;
	
	defined('_JEXEC') or die('Restricted access');
	HTMLHelper::_('behavior.tooltip');
	$app      = Factory::getApplication();
	$depth    = $app->getUserStateFromRequest('jotcache.crawler.depth', 'depth', $app->input->getInt('depth'), 'int');
	$maxDepth = 5;
	$lang     = Factory::getApplication()->getLanguage();
	$lang->load('plg_jotcacheplugins_crawler', JPATH_ADMINISTRATOR, null, false, false);
	$depthOptions = [];
	for ($i = 1; $i < $maxDepth + 1; $i++)
	{
		$depthOptions[$i] = $i;
	} ?>
<form action="<?php echo JRoute::_('index.php?option=com_jotcache'); ?>" method="post" name="adminForm_crawler" id="adminForm_Crawler">
    <h3><?php echo Text::_('PLG_JCPLUGINS_CRAWLER_TITLE'); ?></h3>
    <table class="adminlist" style="width:400px;">
        <tr>
            <td style="padding-left: 0;" class="hasTip" title="<?php echo Text::_('PLG_JCPLUGINS_CRAWLER_DEPTH_DESC'); ?>"><?php echo Text::_('PLG_JCPLUGINS_CRAWLER_DEPTH'); ?> </td>
            <td style="padding-left: 0;"><?php echo HTMLHelper::_('select.genericlist', $depthOptions, 'jcstates[depth]', 'style="width:100px;"', 'value', 'text', $depth); ?></td>
        </tr>
    </table>
    <input type="hidden" name="view" value="recache"/>
    <input type="hidden" name="task" value="display"/>
    <input type="hidden" name="scope" value="direct"/>
    <input type="hidden" name="jotcacheplugin" value="crawler"/>
    <input type="hidden" name="boxchecked" value="0"/>
    <input type="hidden" name="hidemainmenu" value="0"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>