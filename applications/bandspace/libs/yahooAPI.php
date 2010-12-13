<?php 
	class YahooAPIHelper {
		public static $appid = "7kh3dwTV34EpzQJxHlC48SnwJ7M58JDm0L7Fom45x.4QaplzJ06wagkarKRfgaY_";
		
		/**
		 *	Geocodes a location
		 */
		public static function geocode($location) {
			return unserialize(file_get_contents("http://local.yahooapis.com/MapsService/V1/geocode?appid=".YahooAPIHelper::$appid."&location=$location&output=php"));
		}
	}