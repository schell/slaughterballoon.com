<?php
	include_once('../lib/simple_html_dom.php');

	function searchURL($query) {
		$url =  'http://searchservice.myspace.com/index.cfm?fuseaction=sitesearch.results&qry=';
		$url .= urlencode($query);
		$url .= '&type=music&musictype=2';
		return $url;
	}
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
	"http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Bandspace Show RSS</title>
	</head>
	<body>
		<form action="" method="get" accept-charset="utf-8">
			<label for="">Search Myspace Music</label><input type="text" name="search" value="" id="" />
			<p><input type="submit" value="Continue &rarr;"></p>
		</form>
		<div id="searchContent">
			<?php
				if(! empty($_GET)) {
					
					if(isset($_GET['search'])) {
						$url = strpos($_GET['search'], 'http://') === false ? searchURL(urldecode($_GET['search'])) : $_GET['search'];
						// get the search
						$html = file_get_html($url);
						//
						foreach($html->find('div[class="artistSearchResult artistPageResult"]') as $div) {
							echo '<div>';
							echo '<div class="result">'.$div->innertext.'</div>';
							$bandPage 	= file_get_html($div->find('a', 0)->getAttribute('href'));
							$sched		= $bandPage->find('div[id="profile_bandschedule"]', 0)->outertext;
							echo "<div>$sched</div>";
							echo '</div><br />';
						}
					
						$pagination = $html->find('div[class="pagination"]', 0);
						foreach($pagination->find('a') as $a) {
							$a->setAttribute('href', '/bandspace/?search='.urlencode($a->getAttribute('href')));
						}
						echo $pagination->outertext;
					}
				}
			?>
		</div>
		
	</body>
</html>