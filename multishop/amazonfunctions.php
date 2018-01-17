<?php

function csv_to_array($csv, $delimiter = "\t", $enclosure = '"', $escape = '\\', $terminator = "\n")
{
    $r = array();
    $rows = explode($terminator, $csv);
    $names_csv = array_shift($rows);
    $names = str_getcsv($names_csv, $delimiter, $enclosure, $escape);
    $nc = count($names);
    foreach ($rows as $row)
    {
        if ($row)
        {
            $values = str_getcsv($row, $delimiter, $enclosure, $escape);
            if (!$values) $values = array_fill(0,$nc,null);
            $r[] = array_combine($names, $values);
        }
    }
    return $r; 
}

function getAmazonOrders($fulfillmentchannel, $reportsvom, $reportsbis, $domain)
{
	require "constants.php";
	require "conf.php";
	$amazonApiCall = new DhListOrdersByReports();
	$amazonApiCall->_domain = $domain;
	$amazonApiCall->_timestamp = gmdate("Y-m-d\TH:i:s\Z");
	// Report vom
	$date_from = explode("-", $reportsvom);
	$amazonApiCall->_dateAfter = date("Y-m-d\TH:i:s\Z", mktime(0, 0, 0, $date_from[1], $date_from[0], $date_from[2]));
	
	// Reports bis
	if ($reportsbis != "")
	{
		$zeit = "0:0:0";
		if(gmdate("d-m-Y", time()-120) == $reportsbis)
		{
			$zeit = gmdate("H:i:s", time()-120);
		}
		else
		{
			$zeit = "23:59:59";
		}
		$date_bis = explode("-", $reportsbis);
		$zeit_bis = explode(":", $zeit);
		$amazonApiCall->_dateBefore = date("Y-m-d\TH:i:s\Z", mktime($zeit_bis[0], $zeit_bis[1], $zeit_bis[2], $date_bis[1], $date_bis[0], $date_bis[2]));
	}
	else
	{
		$amazonApiCall->_dateBefore=gmdate("Y-m-d\TH:i:s\Z", time()-120); // 120 muß sein!!!
	}

	$amazonApiCall->callAmazon($amazonApiCall->prepareReportListRequest($fulfillmentchannel));
	$reportlist_output = $amazonApiCall->handleReportListResponse();
	
	$bestellungen = array();
	$lfdNr = 0;

    foreach($reportlist_output as $single_report)
	{
		if (!$amazonApiCall->getSavedRequest($single_report['ReportId']))
		{
			$amazonApiCall->callAmazon($amazonApiCall->prepareGetReportRequest($single_report['ReportId']));
		}
		$reportText = $amazonApiCall->handleGetReportResponse($single_report['ReportId']);
		if ($fulfillmentchannel == "haendler")
		{
			$reportText = utf8_encode($reportText);
		}

		// --- Einzelverarbeitung der Reports ---
		
		if (is_array($reportText) && array_key_exists('error', $reportText) && $reportText['error'])
		{
			$bestellungen[$lfdNr]['error'] = $reportText['error'];
			$lfdNr++;
			echo "Error!<br>";
		}
		else
		{
			$csvarray = csv_to_array($reportText);
			
			if($single_report['ReportType'] == "_GET_AMAZON_FULFILLED_SHIPMENTS_DATA_")
			{
				foreach($csvarray as $einzelbestellung)
				{
					// Bestellung bereits vorhanden, nur noch zusätzliche Produkte und Versanddaten hinzufügen
					if (array_key_exists($einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']], $bestellungen))
					{
						// Zusätzlicher Artikel zur Bestellung, zuerst prüfen ob schon in Artikelliste vorhanden, und ob in einem Extra-Paket
						$artikelVorhanden = false;
						$paketVorhanden = false;
						foreach($bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['orderItemsListOutput'] as $einzelartikel)
						{
							if( $einzelartikel['OrderItemId'] == $einzelbestellung[$paramsOrderItemsReportFBA['OrderItemId']] &&
								$einzelartikel['shipment-id'] == $einzelbestellung[$paramsOrderItemsReportFBA['shipment-id']] &&
								$einzelartikel['shipment-item-id'] == $einzelbestellung[$paramsOrderItemsReportFBA['shipment-item-id']])
							{
								$artikelVorhanden = true;
							}
							if ($einzelartikel['shipment-id'] == $einzelbestellung[$paramsOrderItemsReportFBA['shipment-id']])
							{
								$paketVorhanden = true;
							}
						}
						// Wenn zusätzlicher Artikel noch nicht vorhanden, dann hinzufuegen
						if ($artikelVorhanden == false)
						{
							$itemcounter = count($bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['orderItemsListOutput']);
							foreach(array_keys($paramsOrderItems) as $param)
							{
								if (array_key_exists($paramsOrderItemsReportFBA[$param], $einzelbestellung))
								{
									$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['orderItemsListOutput'][$itemcounter][$param] = $einzelbestellung[$paramsOrderItemsReportFBA[$param]];
								}
								else
								{
									$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['orderItemsListOutput'][$itemcounter][$param] = "";
								}
							}
							$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['NumberOfItems'] += $einzelbestellung[$paramsOrdersReportFBA['NumberOfItems']];
							if ($paketVorhanden == false)
							{
								$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['carrier'] .= ";" . $einzelbestellung[$paramsOrdersReportFBA['carrier']];
								$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['tracking-number'] .= ";" . $einzelbestellung[$paramsOrdersReportFBA['tracking-number']];
								$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['estimated-arrival-date'] .= ";" . $einzelbestellung[$paramsOrdersReportFBA['estimated-arrival-date']];
								$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['fulfillment-center-id'] .= ";" . $einzelbestellung[$paramsOrdersReportFBA['fulfillment-center-id']];
							}
						}
					}
					else
					{
						$bearbeitungsstatus = checkAmazonOrderId($einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]);
						
						// Grunddaten zur Bestellung
						$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['bearbeitungsstatus'] = $bearbeitungsstatus;
		        		$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['AmazonOrderId'] = $einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']];
						foreach(array_keys($paramsOrders) as $param)
						{
							if (array_key_exists($paramsOrdersReportFBA[$param], $einzelbestellung))
							{
								$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]][$param] = $einzelbestellung[$paramsOrdersReportFBA[$param]];
							}
							else
							{
								$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]][$param] = "";
							}
						}
						$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['MarketplaceId'] = "Amazon";
						$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['PaymentMethod'] = "Amazon";
						$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['tax_number'] = "";
						$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['tax_included'] = "t";
						
						// 1. Artikel zur Bestellung
						$orderItemsListOutput = array();
						$itemcounter = 0;
						foreach(array_keys($paramsOrderItems) as $param)
						{
							if (array_key_exists($paramsOrderItemsReportFBA[$param], $einzelbestellung))
							{
								$orderItemsListOutput[$itemcounter][$param] = $einzelbestellung[$paramsOrderItemsReportFBA[$param]];
							}
							else
							{
								$orderItemsListOutput[$itemcounter][$param] = "";
							}
						}
						$itemcounter++;
						$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['NumberOfItems'] = $einzelbestellung[$paramsOrdersReportFBA['NumberOfItems']];
						$bestellungen[$einzelbestellung[$paramsOrdersReportFBA['AmazonOrderId']]]['orderItemsListOutput'] = $orderItemsListOutput;
					}
				}
			}
			else
			{
				foreach($csvarray as $einzelbestellung)
				{
					// Bestellung bereits vorhanden, nur noch zusätzliche Produkte und Versanddaten hinzufügen
					if (array_key_exists($einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']], $bestellungen))
					{
						// Zusätzlicher Artikel zur Bestellung, zuerst prüfen ob schon in Artikelliste vorhanden, und ob in einem Extra-Paket
						$artikelVorhanden = false;
						foreach($bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['orderItemsListOutput'] as $einzelartikel)
						{
							if( $einzelartikel['OrderItemId'] == $einzelbestellung[$paramsOrderItemsReportMFN['OrderItemId']] )
							{
								$artikelVorhanden = true;
							}
						}
						// Wenn zusätzlicher Artikel noch nicht vorhanden, dann hinzufuegen
						if ($artikelVorhanden == false)
						{
							$itemcounter = count($bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['orderItemsListOutput']);
							foreach(array_keys($paramsOrderItems) as $param)
							{
								if (array_key_exists($paramsOrderItemsReportMFN[$param], $einzelbestellung))
								{
									$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['orderItemsListOutput'][$itemcounter][$param] = $einzelbestellung[$paramsOrderItemsReportMFN[$param]];
								}
								else
								{
									$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['orderItemsListOutput'][$itemcounter][$param] = "";
								}								
							}
							$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['NumberOfItems'] += $einzelbestellung[$paramsOrdersReportMFN['NumberOfItems']];
						}
					}
					else
					{
						$bearbeitungsstatus = checkAmazonOrderId($einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]);
						
						// Grunddaten zur Bestellung
						$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['bearbeitungsstatus'] = $bearbeitungsstatus;
		        		$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['AmazonOrderId'] = $einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']];
						foreach(array_keys($paramsOrders) as $param)
						{
							if (array_key_exists($paramsOrdersReportMFN[$param], $einzelbestellung))
							{
								$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]][$param] = $einzelbestellung[$paramsOrdersReportMFN[$param]];
							}
							else
							{
								$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]][$param] = "";
							}
						}
						$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['MarketplaceId'] = "Amazon";
						$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['PaymentMethod'] = "Amazon";
						$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['tax_number'] = "";
						$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['tax_included'] = "t";
						
						if ($domain == "EU" && trim($AmazonStandardVersandzentrum) != false)
						{
							$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['fulfillment-center-id'] = $AmazonStandardVersandzentrum;
						}
						else if ($domain == "COM" && trim($AmazonStandardVersandzentrum_COM) != false)
						{
							$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['fulfillment-center-id'] = $AmazonStandardVersandzentrum_COM;
						}
						else if (trim($StandardVersandzentrum) != false)
						{
							$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['fulfillment-center-id'] = $StandardVersandzentrum;
						}
						else
						{
							$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['fulfillment-center-id'] = "";
						}
						
						// 1. Artikel zur Bestellung
						$orderItemsListOutput = array();
						$itemcounter = 0;
						foreach(array_keys($paramsOrderItems) as $param)
						{
							if (array_key_exists($paramsOrderItemsReportMFN[$param], $einzelbestellung))
							{
								$orderItemsListOutput[$itemcounter][$param] = $einzelbestellung[$paramsOrderItemsReportMFN[$param]];
							}
							else
							{
								$orderItemsListOutput[$itemcounter][$param] = "";
							}							
						}
						$itemcounter++;
						$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['NumberOfItems'] = $einzelbestellung[$paramsOrdersReportMFN['NumberOfItems']];
						$bestellungen[$einzelbestellung[$paramsOrdersReportMFN['AmazonOrderId']]]['orderItemsListOutput'] = $orderItemsListOutput;
					}
				}
			}
		}
	}
	
	$zaehler = 1;
	$newOrderIdsArray = array();
	$newOrdersAWSData = array();
	foreach ($bestellungen as $einzelbestellung)
	{
		if ($einzelbestellung['bearbeitungsstatus'] == "neu")
		{
			$orderIdArray[] = $einzelbestellung['AmazonOrderId'];
			$zaehler++;
		}
		
		if ($zaehler > 50) // GetOrderRequest can not handle more than 50 IDs at once
		{
			$amazonApiCallTest = new DhListOrders();
			$amazonApiCallTest->_domain = $domain;
			$amazonApiCallTest->_timestamp = gmdate("Y-m-d\TH:i:s\Z");
			$amazonApiCallTest->callAmazon($amazonApiCallTest->prepareGetOrderRequest($orderIdArray));
			$newOrdersAWSData = array_merge($newOrdersAWSData, $amazonApiCallTest->handleOrderListResponse("GetOrderResult"));
			
			$zaehler = 1;
			$orderIdArray = array();
		}
	}
	if ($zaehler != 1) // GetOrderRequest can not handle more than 50 IDs at once
	{
		$amazonApiCallTest = new DhListOrders();
		$amazonApiCallTest->_domain = $domain;
		$amazonApiCallTest->_timestamp = gmdate("Y-m-d\TH:i:s\Z");
		$amazonApiCallTest->callAmazon($amazonApiCallTest->prepareGetOrderRequest($orderIdArray));
		$newOrdersAWSData = array_merge($newOrdersAWSData, $amazonApiCallTest->handleOrderListResponse("GetOrderResult"));
	}
	
	// Combine Report and AWS-Live-Data
	foreach ($newOrdersAWSData as $newSingleOrderAWSData)
	{
		$AmazonOrderId = $newSingleOrderAWSData['AmazonOrderId'];
		
		if ($bestellungen[$AmazonOrderId]['NumberOfItems'] != $newSingleOrderAWSData['NumberOfItems'] && $newSingleOrderAWSData['FulfillmentChannel'] != 'MFN')
		{
			$newSingleOrderAWSData['OrderStatus'] = "PartiallyShipped";
		}	
	
		foreach ($newSingleOrderAWSData as $singleAWSKey => $singleAWSValue)
		{
			if (trim($singleAWSValue) != false)
			{
				if (trim($bestellungen[$AmazonOrderId][$singleAWSKey]) == false)
				{
					$bestellungen[$AmazonOrderId][$singleAWSKey] = $singleAWSValue;
				}
				else
				{
					if ($bestellungen[$AmazonOrderId][$singleAWSKey] != $singleAWSValue)
					{
						$bestellungen[$AmazonOrderId][$singleAWSKey] = $singleAWSValue;
						// echo $singleAWSKey . " >>>> " . $bestellungen[$AmazonOrderId][$singleAWSKey] . " <====> " . $singleAWSValue . "<br>";
					}
				}
			}
		}
	
		// Check if billing address is set, if not make a copy of shipping address
		if (trim($bestellungen[$AmazonOrderId]['AddressLine1']) == false &&
			trim($bestellungen[$AmazonOrderId]['AddressLine2']) == false &&
			trim($bestellungen[$AmazonOrderId]['PostalCode']) == false &&
			trim($bestellungen[$AmazonOrderId]['City']) == false)
		{
			$bestellungen[$AmazonOrderId]['Title'] = $bestellungen[$AmazonOrderId]['recipient-title'];
			$bestellungen[$AmazonOrderId]['Name'] = $bestellungen[$AmazonOrderId]['recipient-name'];
			$bestellungen[$AmazonOrderId]['AddressLine1'] = $bestellungen[$AmazonOrderId]['ship-address-1'];
			$bestellungen[$AmazonOrderId]['AddressLine2'] = $bestellungen[$AmazonOrderId]['ship-address-2'];
			$bestellungen[$AmazonOrderId]['PostalCode'] = $bestellungen[$AmazonOrderId]['ship-postal-code'];
			$bestellungen[$AmazonOrderId]['City'] = $bestellungen[$AmazonOrderId]['ship-city'];
			$bestellungen[$AmazonOrderId]['StateOrRegion'] = $bestellungen[$AmazonOrderId]['ship-state'];
			$bestellungen[$AmazonOrderId]['CountryCode'] = $bestellungen[$AmazonOrderId]['ship-country'];
		}
	}
	
	// echo '<pre>';
	// print_r($bestellungen);
	// echo '</pre>';

	return $bestellungen;
}

// ##########################################################################################
 
class DhAmazonAccess	// enthält Zugangsdaten und Call-Funktion:
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
	public function prepareGetOrderRequest($orderIdArray)
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
		$request .= "&Action=GetOrder";

		
		$counter = 1;
		$amazonOrderIdSorted = array();
		foreach ($orderIdArray as $singleOrderId)
		{
			$amazonOrderIdSorted[$counter] = "&AmazonOrderId.Id.".$counter."=".$singleOrderId;
			$counter++;
		}
		ksort ($amazonOrderIdSorted, SORT_STRING );
		foreach ($amazonOrderIdSorted as $singleOrderId)
		{
			$request .= $singleOrderId;
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
					."&Version=2013-09-01";

		
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
		
		// Signatur erstellen, codieren, Hash bilden, Request endgültig zusammenstellen
		if ($this->_domain == "COM")
 		{
	 		$stringToSign = "GET\n".$EndpointUrl_COM."\n/Orders/2013-09-01\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey_COM, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl_COM."/Orders/2013-09-01?".$request."&Signature=".$signature;
 		}
 		else
 		{
	 		$stringToSign = "GET\n".$EndpointUrl."\n/Orders/2013-09-01\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl."/Orders/2013-09-01?".$request."&Signature=".$signature;
		}

		return $request;
	}
 
	public function handleOrderListResponse($reportResultType)
	{
		require "constants.php";
		
		$responseDomDoc = new DomDocument();	// Response in neuem DomDocument-Objekt verarbeiten
		$responseDomDoc->loadXML($this->_responseXml);
		$error = $responseDomDoc->getElementsByTagName('Error');	// Fehler abfragen
		$output = array();
		
		if ($error->length>0)	// wenn Fehler, Errorcode auslesen und darstellen:
		{
			$errorType=$error->item(0)->getElementsByTagName('Type')->item(0)->nodeValue;
			$errorCode=$error->item(0)->getElementsByTagName('Code')->item(0)->nodeValue;
 			$errorMsg=$error->item(0)->getElementsByTagName('Message')->item(0)->nodeValue;
 			
			$output['error'][]=$errorType." ".$errorCode.": ".$errorMsg;
		}
		else // sonst: angeforderte Parameter aus Response in Array auslesen:
  		{
			$responses=$responseDomDoc->getElementsByTagName($reportResultType);

			foreach ($responses as $response)	// nur Daten weiter untersuchen, die im Tag <Order> stehen:
			{
				$p = $response->getElementsByTagName("NextToken");
				if($p->length > 0)
				{
				    $nextToken = $p->item(0)->nodeValue;
				} else {
				    $nextToken = "";
				}
				
				$nextTokenOutput = array();
				if (!empty($nextToken))
				{
					$amazonApiCall = new DhListOrdersByNextToken();
					$amazonApiCall->_domain = $this->_domain;
					$amazonApiCall->_timestamp=gmdate("Y-m-d\TH:i:s\Z");
					$amazonApiCall->callAmazon($amazonApiCall->prepareListOrdersByNextTokenRequest($nextToken));
					$nextTokenOutput = $amazonApiCall->handleListOrdersByNextTokenResponse();
				}
				
				$items=$response->getElementsByTagName("Order");
				
				foreach ($items as $i => $item)
				{
					foreach(array_keys($paramsOrders) as $param)
					{
						$entries = $item->getElementsByTagName($paramsOrders[$param]);
						if($entries->length > 0)
						{
							foreach ($entries as $entry)
							{
								if(array_key_exists($param, $output[$i]))
								{
									$output[$i][$param] .= ";" . $entry->nodeValue;
								}
								else
								{
    								$output[$i][$param] = $entry->nodeValue;
								}
							}
						} 
						else
						{
						    $output[$i][$param] = "";
						}
					}
					$output[$i]['MarketplaceId'] = "Amazon";
					$output[$i]['PaymentMethod'] = "Amazon";
					$output[$i]['tax_number'] = "";
					$output[$i]['tax_included'] = "t";
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

// ##########################################################################################

class DhListOrdersByReports extends DhAmazonAccess
{
	public function prepareReportListRequest($fulfillmentchannel)
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
		
		$request .= "&Action=GetReportList";
		
		$request .= "&AvailableFromDate=".$this->_dateAfter;
		$request .= "&AvailableToDate=".$this->_dateBefore;		
		
 		if ($this->_domain == "COM")
 		{
			$request .= "&Merchant=".$MerchantID_COM;
 		}
 		else
 		{
			$request .= "&Merchant=".$MerchantID;
		}
				   
		if ($fulfillmentchannel == "haendler")
		{
			$request .= "&ReportTypeList.Type.1=_GET_FLAT_FILE_ORDERS_DATA_";
		}
		else if ($fulfillmentchannel == "amazon")
		{
			$request .= "&ReportTypeList.Type.1=_GET_AMAZON_FULFILLED_SHIPMENTS_DATA_";
		}
		else
		{
			$request .= "&ReportTypeList.Type.1=_GET_FLAT_FILE_ORDERS_DATA_";
			$request .= "&ReportTypeList.Type.2=_GET_AMAZON_FULFILLED_SHIPMENTS_DATA_";
		}

		$request .= "&SignatureMethod=".$SigMethod
					."&SignatureVersion=".$SigVersion
					."&Timestamp=".$this->_timestamp
					."&Version=2009-01-01";

		// Request sauber zusammenstellen:
		$requestArr = explode("&", $request);
		foreach ($requestArr as $requestSet)
		{
			list($param, $value) = explode("=",$requestSet);
			$param = str_replace("%7E","~",rawurlencode($param));
			$value = str_replace("%7E","~",rawurlencode($value));
			$requestCanonicalized[] = $param."=".$value;
		}
		$request=implode("&",$requestCanonicalized);
		
		// Signatur erstellen, codieren, Hash bilden, Request endgültig zusammenstellen
		if ($this->_domain == "COM")
 		{
	 		$stringToSign = "GET\n".$EndpointUrl_COM."\n/\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey_COM, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl_COM."/?".$request."&Signature=".$signature;
 		}
 		else
 		{
	 		$stringToSign = "GET\n".$EndpointUrl."\n/\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl."/?".$request."&Signature=".$signature;
		}

		return $request;
	}
 
	public function handleReportListResponse()
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
			$responses=$responseDomDoc->getElementsByTagName("GetReportListResult");

			foreach ($responses as $response)	// nur Daten weiter untersuchen, die im Tag <ReportInfo> stehen:
			{
				$nextToken = $response->getElementsByTagName("NextToken")->item(0)->nodeValue;
				
				$nextTokenOutput = array();
				if (!empty($nextToken))
				{
					$amazonApiCall = new DhReportListByNextToken();
					$amazonApiCall->_domain = $this->_domain;
					$amazonApiCall->_timestamp=gmdate("Y-m-d\TH:i:s\Z");
					$amazonApiCall->callAmazon($amazonApiCall->prepareReportListByNextTokenRequest($nextToken));
					$nextTokenOutput = $amazonApiCall->handleReportListByNextTokenResponse();
				}
				
				$items=$response->getElementsByTagName("ReportInfo");
				
				foreach($items as $i => $item)
				{
					$output[$i]['ReportId'] = $item->getElementsByTagName('ReportId')->item(0)->nodeValue;
					$output[$i]['ReportType'] = $item->getElementsByTagName('ReportType')->item(0)->nodeValue;
					$output[$i]['ReportRequestId'] = $item->getElementsByTagName('ReportRequestId')->item(0)->nodeValue;
					$output[$i]['AvailableDate'] = $item->getElementsByTagName('AvailableDate')->item(0)->nodeValue;
					$output[$i]['Acknowledged'] = $item->getElementsByTagName('Acknowledged')->item(0)->nodeValue;
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
	
	public function prepareGetReportRequest($reportId)
	{
		require "conf.php";
		// Request zusammenstellen:
 		if ($this->_domain == "COM")
 		{
			$request = "AWSAccessKeyId=".$AccessKeyID_COM
						."&Action=GetReport"
						."&Merchant=".$MerchantID_COM
					  	."&ReportId=".$reportId						
						."&SignatureMethod=".$SigMethod
						."&SignatureVersion=".$SigVersion
						."&Timestamp=".$this->_timestamp
						."&Version=2009-01-01";			
 		}
 		else
 		{
	 		$request = "AWSAccessKeyId=".$AccessKeyID
						."&Action=GetReport"
						."&Merchant=".$MerchantID
					  	."&ReportId=".$reportId						
						."&SignatureMethod=".$SigMethod
						."&SignatureVersion=".$SigVersion
						."&Timestamp=".$this->_timestamp
						."&Version=2009-01-01";			
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
		
		// Signatur erstellen, codieren, Hash bilden, Request endgültig zusammenstellen
		if ($this->_domain == "COM")
 		{
	 		$stringToSign = "GET\n".$EndpointUrl_COM."\n/\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey_COM, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl_COM."/?".$request."&Signature=".$signature;
 		}
 		else
 		{
	 		$stringToSign = "GET\n".$EndpointUrl."\n/\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl."/?".$request."&Signature=".$signature;			
		}

		return $request;
	}
	
	public function getSavedRequest($reportId)
	{
		if (file_exists("./reportdata/".$this->_domain."_".$reportId))
		{
			$reportdatei = fopen("./reportdata/".$this->_domain."_".$reportId, "r");
			$reportinhalt = fread($reportdatei, filesize("./reportdata/".$this->_domain."_".$reportId));
			fclose($reportdatei);
			$this->_responseXml = $reportinhalt;
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function handleGetReportResponse($reportId)
	{
		require "constants.php";
				
		$responseDomDoc = new DomDocument();	// Response verarbeiten, wenn Error XML, sonst PLAIN-Text
		$responseDomDoc->loadXML($this->_responseXml);
		$error=$responseDomDoc->getElementsByTagName('Error');	// Fehler abfragen
		
		if ($error->length>0)	// wenn Fehler, Errorcode auslesen und darstellen:
		{
			$errorType=$error->item(0)->getElementsByTagName('Type')->item(0)->nodeValue;
			$errorCode=$error->item(0)->getElementsByTagName('Code')->item(0)->nodeValue;
 			$errorMsg=$error->item(0)->getElementsByTagName('Message')->item(0)->nodeValue;
 			
			$output['error'][]=$errorType." ".$errorCode.": ".$errorMsg;
		}
		else // sonst: PLAIN-Text auslesen und Reporttext speichern:
  		{
	  		
			$output = $this->_responseXml;
			if (!file_exists("./reportdata/".$this->_domain."_".$reportId))
			{
				$reportdatei = fopen("./reportdata/".$this->_domain."_".$reportId, "w");
				fwrite($reportdatei, $output);
				fclose($reportdatei);
			}
		}
		return $output;
	}	
}

// ##########################################################################################

class DhReportListByNextToken extends DhAmazonAccess
{
	public function prepareReportListByNextTokenRequest($nextToken)
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
		$request .= "&Action=GetReportListByNextToken";

		if ($this->_domain == "COM")
 		{
			$request .= "&Merchant=".$MerchantID_COM;
 		}
 		else
 		{
			$request .= "&Merchant=".$MerchantID;
		}
						   
		$request .= "&NextToken=".$nextToken;

		$request .= "&SignatureMethod=".$SigMethod
					."&SignatureVersion=".$SigVersion
					."&Timestamp=".$this->_timestamp
					."&Version=2009-01-01";
				
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
		
		// Signatur erstellen, codieren, Hash bilden, Request endgültig zusammenstellen
		if ($this->_domain == "COM")
 		{
	 		$stringToSign = "GET\n".$EndpointUrl_COM."\n/\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey_COM, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl_COM."/?".$request."&Signature=".$signature;			
 		}
 		else
 		{
	 		$stringToSign = "GET\n".$EndpointUrl."\n/\n".$request;
			$signature = base64_encode(hash_hmac("sha256", $stringToSign, $SecretKey, True));
			$signature = str_replace("%7E","~",rawurlencode($signature));
			$request = "https://".$EndpointUrl."/?".$request."&Signature=".$signature;			
		}

		return $request;
	}
 
	public function handleReportListByNextTokenResponse()
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
			echo "Es konnte nicht alles abgerufen werden. ReportListByNextTokenResponse() liefert folgenden Fehler:<br>";
			echo $errorType." ".$errorCode.": ".$errorMsg."<br>";
		}
		else // sonst: angeforderte Parameter aus Response in Array auslesen:
  		{
			$responses=$responseDomDoc->getElementsByTagName("GetReportListByNextTokenResult");

			foreach ($responses as $response)	// nur Daten weiter untersuchen, die im Tag <ReportInfo> stehen:
			{
				$nextToken = $response->getElementsByTagName("NextToken")->item(0)->nodeValue;
				
				$nextTokenOutput = array();
				if (!empty($nextToken))
				{
					$amazonApiCall = new DhReportListByNextToken();
					$amazonApiCall->_domain = $this->_domain;
					$amazonApiCall->_timestamp=gmdate("Y-m-d\TH:i:s\Z");
					$amazonApiCall->callAmazon($amazonApiCall->prepareReportListByNextTokenRequest($nextToken));
					$nextTokenOutput = $amazonApiCall->handleReportListByNextTokenResponse();
				}
				
				$items=$response->getElementsByTagName("ReportInfo");
				
				foreach($items as $i => $item)
				{
					$output[$i]['ReportId'] = $item->getElementsByTagName('ReportId')->item(0)->nodeValue;
					$output[$i]['ReportType'] = $item->getElementsByTagName('ReportType')->item(0)->nodeValue;
					$output[$i]['ReportRequestId'] = $item->getElementsByTagName('ReportRequestId')->item(0)->nodeValue;
					$output[$i]['AvailableDate'] = $item->getElementsByTagName('AvailableDate')->item(0)->nodeValue;
					$output[$i]['Acknowledged'] = $item->getElementsByTagName('Acknowledged')->item(0)->nodeValue;
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