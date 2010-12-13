<?php
	require_once '/var/www/vhosts/slaughterballoon/applications/bandspace/libs/simple_html_dom.php';
	require_once '/var/www/vhosts/slaughterballoon/applications/bandspace/libs/yahooAPI.php';
	
	class RssController extends Zend_Controller_Action
	{
		// our index actions
		function indexAction()
		{	
			$micro = microtime(true);
			$this->_helper->layout->setLayout('blank');
			$p		= $this->_request->getParams();
			$n 		= count($p);
			$links	= array();
			for ($i=0; $i < $n-3; $i++) { 
				array_push($links, $p[$i]);
			}
			
			if(count($links) > 0) {
				$sites	 = new Site();
				$params	 = explode(".", $links[0]);
				$siteID	 = $params[0];
				$daysWI	 = $params[1];
				$dwiStamp= strtotime("+$daysWI days");
				$milesWI = $params[2];
				$ofZip	 = $params[3];
				$testing = isset($params[4]) && $params[4] == "testing";
				$geo	 = YahooAPIHelper::geocode($ofZip);
				
				RssItemHelper::$fromLatLong = array($geo['ResultSet']['Result']['Latitude'], $geo['ResultSet']['Result']['Longitude']);
				RssItemHelper::$withinTime 	= (float) $dwiStamp;
				RssItemHelper::$withinMiles	= (float) $milesWI;
				
				$site	 = $sites->fetchRow($sites->select()->where("id = ?", $params[0]))->toArray();
				$html	 = array();
				$items	 = array();
				for ($i=1; $i < count($links); $i++) { 
					$myspace		= str_replace(" ", "+", urldecode($site['name_long'].$links[$i]));
					?><pre>
						myspqce
					<?php
						print_r($myspace);			
					?></pre>
					<?php
					$html[$i-1]		= new simple_html_dom();
					$html[$i-1]->load_file($myspace);
					echo $html[$i-1]->outertext;
					
					die(' ');
					$band = $html[$i-1]->find('span.nametext', 0)->innertext;
					$html[$i-1]		= $html[$i-1]->find('#profile_bandschedule td td');
					
					array_splice($html[$i-1], 0, 2);
					$infos = array();
					$j = 0;
					foreach($html[$i-1] as $td) {
						
						// set target for all links to _blank
						foreach($td->find('a') as $a) { 
							$a->target = "_blank";
						}
						
						if($j%5 != 0) {
							array_push($infos, $td->find('font', 0)->innertext);
						}
						$j++;
					}
					//echo "Time at item creation: " . $micro;
					$rssList = RssItemHelper::from_myspace_td_list($infos);
					foreach($rssList as $rss) {
						$rss->band = $band;
					}
					$items[$i-1] = $rssList;
				}
				
				// take the items and stager them from closest to today.
				$rssList = array();
				while(count($items) > 0) {
					for ($j=0; $j < count($items); $j++) { 
						$shift = array_shift($items[$j]);
						if($shift != NULL)
							array_push($rssList, $shift);
						else
							array_splice($items, $j, 1);
					}
				}
				
				//echo " Time at rss creation: " . microtime(true) . " diff: $micro";
				$feed = new RssHelper($rssList);
				$feed->title = "$band's shows in $daysWI days < ".$milesWI."mi from $ofZip";
				if($testing)
					echo '<pre>'.htmlentities($feed->generateRSS()).'\n\n\nexecution time: '.(microtime(true) - $micro).'</pre>';
				else
					echo $feed->generateRSS();
			}
		}
	}
	
	class RssItemHelper {
		
		public static $withinTime 	= -1;
		public static $withinMiles	= -1;
		public static $fromLatLong	= -1;
		
		public $timestamp 	= 0;
		public $zip			= 0;
		public $venue		= "";
		public $city		= "";
		public $state		= "";
		public $band		= "";
		public $description = "";
		public $link 		= "";
		
		
		public function generateRSS() {
			$time 	= date('D, d M Y g:ia', $this->timestamp);
			$pubDate= date('D, d M Y H:i:s T');
			$text 	= "<item>\n	<title>$this->band - $time</title>\n	<link>$this->link</link>\n	<description><![CDATA[$this->description]]></description>\n	<guid isPermaLink=\"false\">$this->link</guid>\n</item>";
			return $text;
		}
		/**
		 *	Return an array of RssHelpers, based on a myspace list 
		 */
		public static function from_myspace_td_list($list) {
			$rssArray 	= array();
			$html 		= new simple_html_dom();
			while(count($list) > 0) {
				
				$rss			= new RssItemHelper();
				$elements 		= array_splice($list, 0, 4);
				$cityState			= explode(", ", $elements[3]);
				$rss->timestamp 	= strtotime($elements[0]." ".$elements[1]."M");
				$rss->city			= isset($cityState[0]) ? $cityState[0] : "";
				$rss->state			= isset($cityState[1]) ? $cityState[1] : "";
				
				// if the show is passed when we're interested, abort		
				if($rss->timestamp < time() || (RssItemHelper::$withinTime != -1 && $rss->timestamp > RssItemHelper::$withinTime))
					continue;
					
				// load the show desc page
				if(isset($elements[2])) {
					$html->load($elements[2]);
					$link 			= $html->find('a', 0);
					$rss->link 		= "http://slaughterballoon.com/bandspace/showdate?link=".urlencode($link->href);
					$rss->venue		= $link->innertext;
				}
				
				// get the details
				$html->load_file($link->href);
				$rss->zip			= $html->find('input[name=calEvtZip]', 0)->value;
				// if the show isn't in our vicinity 
				if(RssItemHelper::$withinMiles != -1) {
					$d = RssItemHelper::distanceLatLong(urlencode("$rss->city $rss->state $rss->zip"));
					if($d > RssItemHelper::$withinMiles) {
						continue;
					}
				}
				$rss->description 	= trim(strip_tags($html->find('table[bordercolor=#6699cc]', 0)));
				
				array_push($rssArray, $rss);
			}
			
			return $rssArray;
		}
		/**
		 *	Calcs distance between zipTo and the main zip
		 */
		public static function distanceLatLong($zipTo) {
			$radCon	= (float) pi()/180;
			$geo	= YahooAPIHelper::geocode($zipTo);
			$lat1	= RssItemHelper::$fromLatLong[0] * $radCon;
			$lon1	= RssItemHelper::$fromLatLong[1] * $radCon;
			$lat2	= $geo['ResultSet']['Result']['Latitude'] * $radCon;
			$lon2	= $geo['ResultSet']['Result']['Longitude'] * $radCon;
			
			$d		= acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lon2 - $lon1)) * 3958.75587;
			return $d;
		}
	}
	
	class RssHelper {
		
		public $title 			= "Band Shows RSS";
		public $link            = "http://slaughterballoon.com/bandspace";
		public $description     = "All your bands shows in one RSS feed.";
		public $language        = "en-us";
		public $docs            = "http://blogs.law.harvard.edu/tech/rss";
		public $generator       = "slaughterballoon's bands rss maker";
		public $webMaster       = "webmaster@slaughterballoon.com (webmaster)";
		
		public $items;
		public $pubDate;      
		public $lastBuildDate;
		
		/**
		 *
		 */
		public function RssHelper($itemArray = array()) {
			$this->items 		= $itemArray;
			$this->pubDate 		= date('D, d M Y H:i:s T');
			$this->lastBuildDate= date('D, d M Y H:i:s T');
		}
		/**
		 *
		 */
		public function generateRSS() {
			$itemText = "";
			foreach($this->items as $item) {
				$itemText .= $item->generateRSS();
			}
			return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
			<rss version=\"2.0\">
<channel>
	<title>$this->title</title>
	<link>$this->link</link>
	<description>$this->description</description>
	<language>$this->language</language>
	<pubDate>$this->pubDate</pubDate>
	<lastBuildDate>$this->lastBuildDate</lastBuildDate>
	<docs>$this->docs</docs>
	<generator>$this->generator</generator>
	<webMaster>$this->webMaster</webMaster>
	$itemText
</channel>
</rss>";
		}
	}
	
	function array_msort($array, $cols)
	{
	    $colarr = array();
	    foreach ($cols as $col => $order) {
	        $colarr[$col] = array();
	        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
	    }
	    $params = array();
	    foreach ($cols as $col => $order) {
	        $params[] =& $colarr[$col];
	        $params = array_merge($params, (array)$order);
	    }
	    call_user_func_array('array_multisort', $params);
	    $ret = array();
	    $keys = array();
	    $first = true;
	    foreach ($colarr as $col => $arr) {
	        foreach ($arr as $k => $v) {
	            if ($first) { $keys[$k] = substr($k,1); }
	            $k = $keys[$k];
	            if (!isset($ret[$k])) $ret[$k] = $array[$k];
	            $ret[$k][$col] = $array[$k][$col];
	        }
	        $first = false;
	    }
	    return $ret;

	}