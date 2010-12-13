<?php
	require_once '/var/www/vhosts/slaughterballoon/applications/bandspace/libs/simple_html_dom.php';
	require_once '/var/www/vhosts/slaughterballoon/applications/bandspace/libs/yahooAPI.php';
	
	class ShowdateController extends Zend_Controller_Action
	{
		// our index actions
		function indexAction()
		{	
			$micro = microtime(true);
			$this->_helper->layout->setLayout('showdate');
			$html = new simple_html_dom();
			$link = $this->_request->getParam('link', '');
			if($link == '')
				die('');
			$html->load_file($link);
			$this->view->title = $html->find('table[bordercolor=#6699cc] td[bgcolor=#ffffff] p strong font', 0)->innertext . " - Show Details";
			//$html = $html->find('table[bordercolor=#6699cc] td[bgcolor=#ffffff]', 0); 
			$html->find('input#ctl00_ctl00_ctl00_cpMain_cpMain_Main_DetailsBox_frame_addToMyCalendar', 0)->outertext = "";
			echo $html->innertext;
		}
	}