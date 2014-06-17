<?php

function getAmazonOrders($fulfillmentchannel, $versandstatus, $suchdatum, $erledigtesanzeigen, $bestellungvom, $bestellungbis, $domain)
{
	$amazonApiCall = new DhListOrders();
	$amazonApiCall->_domain = $domain;
	$amazonApiCall->_timestamp = gmdate("Y-m-d\TH:i:s\Z");
	// Bestellungen vom
	$date_from = explode("-", $bestellungvom);
	$amazonApiCall->_dateAfter = date("Y-m-d\TH:i:s\Z", mktime(0, 0, 0, $date_from[1], $date_from[0], $date_from[2]));
	
	// Bestellungen bis
	if ($bestellungbis != "")
	{
		$zeit = "0:0:0";
		if(gmdate("d-m-Y", time()-120) == $bestellungbis)
		{
			$zeit = gmdate("H:i:s", time()-120);
		}
		else
		{
			$zeit = "23:59:59";
		}
		$date_bis = explode("-", $bestellungbis);
		$zeit_bis = explode(":", $zeit);
		$amazonApiCall->_dateBefore = date("Y-m-d\TH:i:s\Z", mktime($zeit_bis[0], $zeit_bis[1], $zeit_bis[2], $date_bis[1], $date_bis[0], $date_bis[2]));
	}
	else
	{
		$amazonApiCall->_dateBefore=gmdate("Y-m-d\TH:i:s\Z", time()-120); // 120 mu� sein!!!
	}

	$amazonApiCall->callAmazon($amazonApiCall->prepareOrderListRequest($fulfillmentchannel, $versandstatus, $suchdatum));
	$output = $amazonApiCall->handleOrderListResponse();
	
	foreach($output as $lfdNr => $opSet1)
	{
		$bearbeitungsstatus = checkAmazonOrderId($opSet1['AmazonOrderId']);
		$output[$lfdNr]['bearbeitungsstatus'] = $bearbeitungsstatus;
		$get_it = false;
		if ($bearbeitungsstatus == "neu")
		{
			$get_it = true;
		}
		if ($get_it)
		{
			$amazonApiCall->callAmazon($amazonApiCall->prepareOrderItemsListRequest($opSet1['AmazonOrderId']));
			$orderItemsListOutput = $amazonApiCall->handleOrderItemsListResponse();

			if (array_key_exists('error', $orderItemsListOutput) && $orderItemsListOutput['error'])
			{
				$output[$lfdNr]['error'] = $orderItemsListOutput['error'];
			}
			else
			{
				$output[$lfdNr]['orderItemsListOutput'] = $orderItemsListOutput;
			}
		}
	}
	return $output;
}

// ##########################################################################################
 
class DhAmazonAccess	// enth�lt Zugangsdaten und Call-Funktion:
{
 	public function callAmazon($amazonMWSRequest)
 	{
		$conn = curl_init();
		curl_setopt($conn, CURLOPT_URL,				$amazonMWSRequest);
		curl_setopt($conn, CURLOPT_HEADER,			0);
		curl_setopt($conn, CURLOPT_RETURNTRANSFER,	1);
		curl_setopt($conn, CURLOPT_SSL_VERIFYPEER,	0);
		$responseXml = curl_exec($conn);
		$this->_responseXml = $responseXml;
		curl_close($conn);
	}
}
 
// ##########################################################################################

class DhListOrders extends DhAmazonAccess
{
	public function prepareOrderListRequest($fulfillmentchannel, $versandstatus, $suchdatum)
	{
		require "conf.php";
		
		// Request zusammenstellen:
		if ($this->_domain == "COM")
 		{
			$request = "AWSAccessKeyId=".$AccessKeyID_COM;
 		}
 		else
 		{
			$request = "AWSAccessKeyId=".$AccessKeyID;
		}
		$request .= "&Action=ListOrders";
				   
		if ($suchdatum == "bestelldatum")
		{
 			$request .= "&CreatedAfter=".$this->_dateAfter;
 			$request .= "&CreatedBefore=".$this->_dateBefore;
		}
				   
		if ($fulfillmentchannel == "haendler")
		{
			$request .= "&FulfillmentChannel.Channel.1=MFN";
		}
		else
		{
			$request .= "&FulfillmentChannel.Channel.1=AFN";
		}

		if ($suchdatum == "versanddatum")
		{
			$request .= "&LastUpdatedAfter=".$this->_dateAfter;
			$request .= "&LastUpdatedBefore=".$this->_dateBefore;
		}
		
		if ($this->_domain == "COM")
 		{
			$request .= "&MarketplaceId.Id.1=".$MarketplaceID_US;
 		}
 		else
 		{
			$request .= "&MarketplaceId.Id.1=".$MarketplaceID_DE
						."&MarketplaceId.Id.2=".$MarketplaceID_GB
						."&MarketplaceId.Id.3=".$MarketplaceID_FR
						."&MarketplaceId.Id.4=".$MarketplaceID_IT
						."&MarketplaceId.Id.5=".$MarketplaceID_ES;
		}

		if ($versandstatus)
		{
			$zaehler = 1;
			if (in_array("shipped", $versandstatus)) { $request .= "&OrderStatus.Status.".$zaehler++."=Shipped"; }
			if (in_array("pending", $versandstatus)) { $request .= "&OrderStatus.Status.".$zaehler++."=Pending"; }
			if (in_array("partiallyunshipped", $versandstatus) || $fulfillmentchannel == "haendler") { $request .= "&OrderStatus.Status.".$zaehler++."=PartiallyShipped"."&OrderStatus.Status.".$zaehler++."=Unshipped"; }
			if (in_array("canceled", $versandstatus)) { $request .= "&OrderStatus.Status.".$zaehler++."=Canceled"; }
			if (in_array("unfulfillable", $versandstatus)) { $request .= "&OrderStatus.Status.".$zaehler++."=Unfulfillable"; }
		}
		else
		{
			$request .= "&OrderStatus.Status.1=Shipped";
 		}
 					
 		if ($this->_domain == "COM")
 		{
			$request .= "&SellerId=".$MerchantID_COM;
 		}
 		else
 		{
			$request .= "&SellerId=".$MerchantID;
		}
		
		$request .= "&SignatureMethod=".$SigMethod
					."&SignatureVersion=".$SigVersion
					."&Timestamp=".$this->_timestamp
					."&Version=2011-01-01";

		
		// Request sauber zusammenstellen:
		$requestArr = explode("&",$request);
		foreach ($requestArr as $requestSet)
		{
			list($param, $value) = explode("=",$requestSet);
			$param = str_replace("%7E","~",rawurlencode($param));
			$value = str_replace("%7E","~",rawurlencode($value));
			$requestCanonicalized[] = $param."=".$value;
		}
		$request=implode("&",$requestCanonicalized);
		
		// Signatur erstellen, codieren, Hash bilden, Request endg�ltig zusammenstellen
		if ($this->_domain == "COM")
 		{
	 		$stringToSign = "GET\n".$EndpointUrl_COM."\n/Orders/2011-01-01\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey_COM, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl_COM."/Orders/2011-01-01?".$request."&Signature=".$signature;
 		}
 		else
 		{
	 		$stringToSign = "GET\n".$EndpointUrl."\n/Orders/2011-01-01\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl."/Orders/2011-01-01?".$request."&Signature=".$signature;
		}

		
		return $request;
	}
 
	public function handleOrderListResponse()
	{
		function cmp($a, $b)
		{
			return strcmp($a['LastUpdateDate'], $b['LastUpdateDate']);
		}
		
		require "constants.php";
		
		$responseDomDoc = new DomDocument();	// Response in neuem DomDocument-Objekt verarbeiten
		$responseDomDoc->loadXML($this->_responseXml);
		$error=$responseDomDoc->getElementsByTagName('Error');	// Fehler abfragen
 
		if ($error->length>0)	// wenn Fehler, Errorcode auslesen und darstellen:
		{
			$errorType=$error->item(0)->getElementsByTagName('Type')->item(0)->nodeValue;
			$errorCode=$error->item(0)->getElementsByTagName('Code')->item(0)->nodeValue;
 			$errorMsg=$error->item(0)->getElementsByTagName('Message')->item(0)->nodeValue;
 			
			$output['error'][]=$errorType." ".$errorCode.": ".$errorMsg;
		}
		else // sonst: angeforderte Parameter aus Response in Array auslesen:
  		{
			$responses=$responseDomDoc->getElementsByTagName("ListOrdersResult");

			foreach ($responses as $response)	// nur Daten weiter untersuchen, die im Tag <Order> stehen:
			{
				$nextToken = $response->getElementsByTagName("NextToken")->item(0)->nodeValue;
				if (!empty($nextToken))
				{
					$amazonApiCall = new DhListOrdersByNextToken();
					$amazonApiCall->_domain = $this->_domain;
					$amazonApiCall->_timestamp=gmdate("Y-m-d\TH:i:s\Z");
					$amazonApiCall->callAmazon($amazonApiCall->prepareListOrdersByNextTokenRequest($nextToken));
					$nextTokenOutput = $amazonApiCall->handleListOrdersByNextTokenResponse();
				}
				
				$items=$response->getElementsByTagName("Order");
				
				foreach($items as $i => $item)
				{
					foreach($paramsOrders as $param)
					{
						$output[$i][$param] = $item->getElementsByTagName($param)->item(0)->nodeValue;
					}
					$output[$i]['MarketplaceId'] = "Amazon";
					$output[$i]['PaymentMethod'] = "Amazon";
				}
				
				foreach($nextTokenOutput as $item)
				{
					$i++;
					$output[$i] = $item;
				}
			}
		}
		
		// output sortieren
		if ($suchdatum == "versanddatum")
		{
			usort($output, "cmp");
		}
		
		return $output;
	}
	
	public function prepareOrderItemsListRequest($amazonOrderId)
	{
		require "conf.php";
		// Request zusammenstellen:
 		if ($this->_domain == "COM")
 		{
			$request = "AWSAccessKeyId=".$AccessKeyID_COM
						."&Action=ListOrderItems"
					  	."&AmazonOrderId=".$amazonOrderId
						."&SellerId=".$MerchantID_COM
						."&SignatureMethod=".$SigMethod
						."&SignatureVersion=".$SigVersion
						."&Timestamp=".$this->_timestamp
						."&Version=2011-01-01";			
 		}
 		else
 		{
	 		$request = "AWSAccessKeyId=".$AccessKeyID
						."&Action=ListOrderItems"
					  	."&AmazonOrderId=".$amazonOrderId
						."&SellerId=".$MerchantID
						."&SignatureMethod=".$SigMethod
						."&SignatureVersion=".$SigVersion
						."&Timestamp=".$this->_timestamp
						."&Version=2011-01-01";			
		}		

		// Request sauber zusammenstellen:
		$requestArr = explode("&",$request);
		foreach ($requestArr as $requestSet)
		{
			list($param, $value) = explode("=",$requestSet);
			$param = str_replace("%7E","~",rawurlencode($param));
			$value = str_replace("%7E","~",rawurlencode($value));
			$requestCanonicalized[] = $param."=".$value;
		}
		$request=implode("&",$requestCanonicalized);
		
		// Signatur erstellen, codieren, Hash bilden, Request endg�ltig zusammenstellen
		if ($this->_domain == "COM")
 		{
	 		$stringToSign = "GET\n".$EndpointUrl_COM."\n/Orders/2011-01-01\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey_COM, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl_COM."/Orders/2011-01-01?".$request."&Signature=".$signature;
 		}
 		else
 		{
	 		$stringToSign = "GET\n".$EndpointUrl."\n/Orders/2011-01-01\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl."/Orders/2011-01-01?".$request."&Signature=".$signature;			
		}

		return $request;
	}
	
	public function handleOrderItemsListResponse()
	{
		require "constants.php";
				
		$responseDomDoc = new DomDocument();	// Response in neuem DomDocument-Objekt verarbeiten
		$responseDomDoc->loadXML($this->_responseXml);
		$error=$responseDomDoc->getElementsByTagName('Error');	// Fehler abfragen
		
		if ($error->length>0)	// wenn Fehler, Errorcode auslesen und darstellen:
		{
			$errorType=$error->item(0)->getElementsByTagName('Type')->item(0)->nodeValue;
			$errorCode=$error->item(0)->getElementsByTagName('Code')->item(0)->nodeValue;
 			$errorMsg=$error->item(0)->getElementsByTagName('Message')->item(0)->nodeValue;
 			
			$output['error'][]=$errorType." ".$errorCode.": ".$errorMsg;
		}
		else // sonst: angeforderte Parameter aus Response in Array auslesen:
  		{
			$responses = $responseDomDoc->getElementsByTagName("ListOrderItemsResult");

			foreach ($responses as $response)	// nur Daten weiter untersuchen, die im Tag <OrderItem> stehen:
			{
				$amazonOrderId = $response->getElementsByTagName("AmazonOrderId")->item(0)->nodeValue;

				$items = $response->getElementsByTagName("OrderItem");

				foreach($items as $i => $item)
				{
					$output[$i]['AmazonOrderId'] = $amazonOrderId;
					foreach($paramsOrderItems as $param)
					{
						$output[$i][$param] = $item->getElementsByTagName($param)->item(0)->nodeValue;
					}
				}
			}
		}
		return $output;
	}
}

class DhListOrdersByNextToken extends DhAmazonAccess
{
	public function prepareListOrdersByNextTokenRequest($nextToken)
	{
		require "conf.php";
		
		// Request zusammenstellen:
		if ($this->_domain == "COM")
 		{
			$request = "AWSAccessKeyId=".$AccessKeyID_COM;
 		}
 		else
 		{
			$request = "AWSAccessKeyId=".$AccessKeyID;
		}
		$request .= "&Action=ListOrdersByNextToken";
				   
		$request .= "&NextToken=".$nextToken;

		if ($this->_domain == "COM")
 		{
			$request .= "&SellerId=".$MerchantID_COM;
 		}
 		else
 		{
			$request .= "&SellerId=".$MerchantID;
		}
		
		$request .= "&SignatureMethod=".$SigMethod
					."&SignatureVersion=".$SigVersion
					."&Timestamp=".$this->_timestamp
					."&Version=2011-01-01";
				
		// Request sauber zusammenstellen:
		$requestArr = explode("&",$request);
		foreach ($requestArr as $requestSet)
		{
			list($param, $value) = explode("=",$requestSet);
			$param = str_replace("%7E","~",rawurlencode($param));
			$value = str_replace("%7E","~",rawurlencode($value));
			$requestCanonicalized[] = $param."=".$value;
		}
		$request=implode("&",$requestCanonicalized);
		
		// Signatur erstellen, codieren, Hash bilden, Request endg�ltig zusammenstellen
		if ($this->_domain == "COM")
 		{
	 		$stringToSign = "GET\n".$EndpointUrl_COM."\n/Orders/2011-01-01\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey_COM, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl_COM."/Orders/2011-01-01?".$request."&Signature=".$signature;			
 		}
 		else
 		{
	 		$stringToSign = "GET\n".$EndpointUrl."\n/Orders/2011-01-01\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl."/Orders/2011-01-01?".$request."&Signature=".$signature;			
		}		

		return $request;
	}
 
	public function handleListOrdersByNextTokenResponse()
	{
		require "constants.php";
		
		$responseDomDoc = new DomDocument();	// Response in neuem DomDocument-Objekt verarbeiten
		$responseDomDoc->loadXML($this->_responseXml);
		$error = $responseDomDoc->getElementsByTagName('Error');	// Fehler abfragen
 
		if ($error->length>0)	// wenn Fehler, Errorcode auslesen und darstellen:
		{
			$errorType = $error->item(0)->getElementsByTagName('Type')->item(0)->nodeValue;
			$errorCode = $error->item(0)->getElementsByTagName('Code')->item(0)->nodeValue;
 			$errorMsg = $error->item(0)->getElementsByTagName('Message')->item(0)->nodeValue;
 			
			// $output['error'][] = $errorType." ".$errorCode.": ".$errorMsg;
			echo "Es konnte nicht alles abgerufen werden. ListOrdersByNextTokenResponse() liefert folgenden Fehler:<br>";
			echo $errorType." ".$errorCode.": ".$errorMsg."<br>";
		}
		else // sonst: angeforderte Parameter aus Response in Array auslesen:
  		{
			$responses=$responseDomDoc->getElementsByTagName("ListOrdersByNextTokenResult");

			foreach ($responses as $response)	// nur Daten weiter untersuchen, die im Tag <Order> stehen:
			{
				$nextToken = $response->getElementsByTagName("NextToken")->item(0)->nodeValue;
				if (!empty($nextToken))
				{
					$amazonApiCall = new DhListOrdersByNextToken();
					$amazonApiCall->_domain = $this->_domain;
					$amazonApiCall->_timestamp=gmdate("Y-m-d\TH:i:s\Z");
					$amazonApiCall->callAmazon($amazonApiCall->prepareListOrdersByNextTokenRequest($nextToken));
					$nextTokenOutput = $amazonApiCall->handleListOrdersByNextTokenResponse();
				}
				
				$items=$response->getElementsByTagName("Order");
				
				foreach($items as $i => $item)
				{
					foreach($paramsOrders as $param)
					{
						$output[$i][$param] = $item->getElementsByTagName($param)->item(0)->nodeValue;
					}
					$output[$i]['MarketplaceId'] = "Amazon";
					$output[$i]['PaymentMethod'] = "Amazon";
				}
				
				foreach($nextTokenOutput as $item)
				{
					$i++;
					$output[$i] = $item;
				}
			}
		}
		
		return $output;
	}
}


// ###################################################################################
?>