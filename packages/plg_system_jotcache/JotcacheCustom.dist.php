<?php
	
	/**
	 * @package         JotCache
	 *
	 * @copyright   (C) 2010-2018 Vladimir Kanich
	 * @copyright   (C) 2025+ Open Source Matters, Inc.
	 * @license         GNU General Public License version 2 or later
	 */
	
	use Joomla\CMS\Factory;
	use Joomla\Database\DatabaseInterface;
	
	defined('JPATH_BASE') or die;
	
	class JotcacheCustom
	{
		public function modifyDataFromCache(&$data)
		{
			$data = preg_replace_callback('#([<]span\s+class="\w*DateCreated"[>])([^<]*)([<]/span[>])#',
			                              [$this, 'timeRelative'], $data);
			$this->incrementHit($data);
		}
		
		private function incrementHit(&$data)
		{
			$input     = Factory::getApplication()->input;
			$xcom      = $input->get('option', '');
			$xview     = $input->get('view', '');
			$xid       = $input->get('id', 0);
			$xoffset   = $input->get('limitstart', 0, 'uint');
			$xhitcount = $input->get('hitcount', 1, 'int');
			if ($xcom == 'com_content' && $xview == 'article' && $xid > 0 && $xoffset == 0 && $xhitcount)
			{
				$xcache = Factory::getCache($xcom, 'view');
				$xcache->clean();
				$xdb = Factory::getContainer()->get(DatabaseInterface::class);
				$xdb->setQuery(
					'UPDATE #__content' .
					' SET hits = hits + 1' .
					' WHERE id = ' . (int) $xid);
				$xdb->query();
				$xdb->setQuery('SELECT hits FROM #__content WHERE id =' . (int) $xid);
				$hits = $xdb->loadResult();
				$data = preg_replace('#(<dd[^>]*class="hits"[^>]*>\s*\w*:)([^<]*)(<\/dd>)#', '${1}' . $hits . ' ${3}', $data);
			}
			if ($xcom == 'com_k2' && ($xview == 'item' || $xview == 'itemlist') && $xoffset == 0 && $xhitcount)
			{
				$xid       = $input->get('id', 0);
				$xid_array = explode(':', $xid);
				$xid       = $xid_array[0];
				$xcache    = Factory::getCache('com_k2_extended', 'view');
				$xcache->clean();
				$xdb = Factory::getContainer()->get(DatabaseInterface::class);
				$xdb->setQuery(
					'UPDATE #__k2_items' .
					' SET hits = hits + 1' .
					' WHERE id = ' . (int) $xid);
				$xdb->query();
				$xdb->setQuery('SELECT hits FROM #__k2_items WHERE id =' . (int) $xid);
				$hits = $xdb->loadResult();
				if ($xview == 'item')
				{
					$data = preg_replace('#(<span\s+class="itemHits">[^<]*<b>)(\d*)(<\/b>)#', '${1}' . $hits . ' ${3}', $data);
				}
				else
				{
					preg_match_all('#<h3\s+class="catItemTitle">\s*<a\s+href="(\/[^"]*)*#', $data, $matches);
					$ids  = [];
					$ids2 = [];
					foreach ($matches[1] as $match)
					{
						$pos  = strrpos($match, '/');
						$last = substr($match, $pos);
						preg_match('#\/(\d*)#', $last, $mat);
						if ($mat[1])
						{
							$ids[]  = $mat[1];
							$ids2[] = $mat[1];
						}
						else
						{
							$ids[] = 0;
						}
					}
					$idsImpl = implode("','", $ids2);
					$xdb->setQuery("SELECT hits,id FROM #__k2_items WHERE id IN ('" . $idsImpl . "')");
					$rows = $xdb->loadObjectList();
					$hits = [];
					foreach ($rows as $row)
					{
						$hits[$row->id] = $row->hits;
					}
					foreach ($ids as $id)
					{
						if ($id > 0)
						{
							$data = preg_replace('#(<span\s+class="catItemHits">[^<]*<b>)(\d*)(<\/b>)#', '${1}' . $hits[$id] . ' ${3}', $data, 1);
						}
						else
						{
							$data = preg_replace('#(<span\s+class="catItemHits">[^<]*<b>)(\d*)(<\/b>)#', '${1} ${2} ${3}', $data, 1);
						}
					}
				}
			}
		}
		
		public function modifyDataAfterSave(&$data)
		{
			$data = preg_replace_callback('#([<]span\s+class="\w*DateCreated"[>])([^<]*)([<]/span[>])#',
			                              [$this, 'timeRelative'], $data);
		}
		
		private function timeRelative($matches)
		{
			$format   = 'l, j F Y H:i';
			$timezone = 'GMT';
			$zone     = new DateTimeZone($timezone);
			$now      = new DateTime('now', $zone);
			$input    = trim($matches[2]);
			$date     = DateTime::createFromFormat($format, $input, $zone);
			if (is_object($date) && $now > $date)
			{
				$difference = $date->diff($now);
				$part       = explode('"', $matches[1]);
				$class      = substr($part[1], 0, 3);
				$ago        = ' ago';
				$result2    = '';
				switch ($class)
				{
					case 'ite':
						$result2 = $date->format('j F Y');
						if ($difference->y > 0)
						{
							$result2 = '';
							$result  = $date->format('j F Y');
							$ago     = '';
							break;
						}
						if ($difference->m > 0)
						{
							$result = $difference->format('%m') . $this->pluralize($difference->m, 'month');
							break;
						}
						if ($difference->d > 0)
						{
							$result = $difference->format('%a') . $this->pluralize($difference->d, 'day');
							break;
						}
						if ($difference->h > 0)
						{
							$result = $difference->format('%h') . $this->pluralize($difference->h, 'hour');
						}
						else
						{
							$result = $difference->format('%i') . $this->pluralize($difference->i, 'minute');
						}
						break;
					case 'cat':
					case 'lat':
					case 'tag':
					case 'use':
						if ($difference->y > 0)
						{
							$result = $difference->format('%y') . $this->pluralize($difference->y, 'year');
							break;
						}
						if ($difference->m > 0)
						{
							$result = $difference->format('%m') . $this->pluralize($difference->m, 'month');
							break;
						}
						if ($difference->d > 0)
						{
							$result = $difference->format('%a') . $this->pluralize($difference->d, 'day');
							break;
						}
						if ($difference->h > 0)
						{
							$result = $difference->format('%h') . $this->pluralize($difference->h, 'hour');
						}
						else
						{
							$result = $difference->format('%i') . $this->pluralize($difference->i, 'minute');
						}
						break;
					default:
						break;
				}
				if ($result2)
				{
					$replacement = ' title="' . $result2 . '">';
					$matches[1]  = str_replace(">", $replacement, $matches[1]);
					
					return $matches[1] . $result . $ago . $matches[3];
				}
				else
				{
					return $matches[1] . $result . $ago . $matches[3];
				}
			}
			else
			{
				return $matches[1] . $matches[2] . $matches[3];
			}
		}
		
		private function pluralize($count, $text)
		{
			if ($text == 'month' || $text == 'year')
			{
				return (($count == 1) ? (" $text") : (" ${text}s"));
			}
			else
			{
				return $text[0];
			}
		}
	}