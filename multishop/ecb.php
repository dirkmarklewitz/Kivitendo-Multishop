<?php
	function exchangerates()
	{
		$waehrungsurl = "http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml";
		$waehrungsdatei = "./currency.xml";
		
		$xml = false;
		if (file_exists($waehrungsdatei))
		{
			if (strtotime("-1 day") > @filemtime($waehrungsdatei))
			{
		    	$xml = simplexml_load_file($waehrungsurl);
		    	if($xml != false)
		    	{
						if (@unlink($waehrungsdatei) != false)
					{
						$xml->asXML($waehrungsdatei);
					}
					else
					{
						$xml->asXML($waehrungsdatei);
					}
		    	}
		    	else
		    	{
			    	$xml = simplexml_load_file($waehrungsdatei);
		    	}
			}
			else
			{
				$xml = simplexml_load_file($waehrungsdatei);
			}
		}
		else
		{
			$xml = simplexml_load_file($waehrungsurl);
			if($xml != false)
			{
				$xml->asXML($waehrungsdatei);
			}
		}
		
		if ($xml != false)
		{
			$list = array();
			
			$xml->registerXPathNamespace('d', 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref');
			
			foreach ($xml->xpath('//d:Cube[@currency and @rate]') as $cube)
			{
				$list[(string)$cube->attributes()['currency']] = floatval($cube->attributes()['rate']);
			}
			
			return $list;
		}
		else
		{
			return false;
		}
	}
	
	function exchangerate($currencyname, $waehrungskorrektur = 0)
	{
		$list = exchangerates();
		
		if ($list != false)
		{
			if(array_key_exists(trim(strtoupper($currencyname)), $list))
			{
				return round($list[trim(strtoupper($currencyname))] * (1.0 + ($waehrungskorrektur / 100)), 6, PHP_ROUND_HALF_UP);
			}
			else
			{
				return 1.0;
			}
		}
		else
		{
			return 1.0;
		}
	}
?>