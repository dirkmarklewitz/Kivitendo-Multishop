<?php

function getRakutenOrders($fulfillmentchannel, $bestellungvom, $bestellungbis)
{
	$returnvalue = array();
	
	if($fulfillmentchannel == "amazon")
	{
		return $returnvalue;
	}
	
	// Bestellungen von
	$date_from = explode("-", $bestellungvom);
	$dateAfter = date("Y-m-d\TH:i:s\Z", mktime(0, 0, 0, $date_from[1], $date_from[0], $date_from[2]));
	
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
		$dateBefore = date("Y-m-d\TH:i:s\Z", mktime($zeit_bis[0], $zeit_bis[1], $zeit_bis[2], $date_bis[1], $date_bis[0], $date_bis[2]));
	}
	else
	{
		$dateBefore = gmdate("Y-m-d\TH:i:s\Z", time()-120); // 120 muß sein!!!
	}
	
	$RakutenGetData = new RakutenApiClass();
	$RakutenGetData->callRakuten('getOrders', $dateAfter, $dateBefore);
	
	$returnvalue = $RakutenGetData->handleResultXML();
	
	foreach($returnvalue as $lfdNr => $opSet1)
	{
		$bearbeitungsstatus = checkAmazonOrderId($opSet1['AmazonOrderId']);
		$returnvalue[$lfdNr]['bearbeitungsstatus'] = $bearbeitungsstatus;
	}
	
	return $returnvalue;
}
 
class RakutenApiClass
{
    public function callRakuten($call, $dateAfter, $dateBefore)
    {
    	require "conf.php";
    	
    	$request = $RakutenAPIUrl.$call."?key=".$RakutenAPISchluessel;
    	$request .= "&from=".$dateAfter;
 		$request .= "&to=".$dateBefore;
 
		$conn = curl_init();
		curl_setopt($conn, CURLOPT_URL,				$request);
		curl_setopt($conn, CURLOPT_HEADER,			0);
		curl_setopt($conn, CURLOPT_RETURNTRANSFER,	1);
		curl_setopt($conn, CURLOPT_SSL_VERIFYPEER,	0);
		$responseXml = curl_exec($conn);
		$this->_responseXml = $responseXml;
		curl_close($conn);    	
    }
 
    public function handleResultXML()
    {
	    require "conf.php";
		
		$returnvalue = array();	    
        // XML string is parsed and creates a DOM Document object
        $responseDoc = new DomDocument();
        $responseDoc->loadXML($this->_responseXml);
        
        // Get any error nodes
        $errors = $responseDoc->getElementsByTagName('errors');
 
        // If there are error nodes
        if ($errors->length > 0)
        {
	        echo '<P><B>Rakuten returned the following error(s):</B>';
			// Display each error
	        // Get error code, ShortMesaage and LongMessage
			foreach ($errors as $error)
            {
	            $code     = $error->getElementsByTagName('code');
	            $shortMsg = $error->getElementsByTagName('message');
	            // Display code and shortmessage
	            echo '<P>', $code->item(0)->nodeValue, ' : ', str_replace(">", "&gt;", str_replace("<", "&lt;", $shortMsg->item(0)->nodeValue));
        	}
        }
        else	// There are no errors, generate array with results
        {
            // Get results nodes
            $responses = $responseDoc->getElementsByTagName("orders");
            foreach ($responses as $response)
            {
                $items = $response->getElementsByTagName("order");

                $bestellungszaehler = 0;
                
				foreach ($items as $item)
	            {
            		$returnvalue[$bestellungszaehler]['AmazonOrderId'] = $RakutenBestellnummernprefix.$item->getElementsByTagName('order_no')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['SellerOrderId'] = $RakutenBestellnummernprefix.$item->getElementsByTagName('order_no')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['PurchaseDate'] = $item->getElementsByTagName('created')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['LastUpdateDate'] = $item->getElementsByTagName('created')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['SalesChannel'] = $RakutenAbteilungsname;
					$returnvalue[$bestellungszaehler]['MarketplaceId'] = $RakutenAbteilungsname;
					// $returnvalue[$bestellungszaehler]['OrderType'] = "";
					$returnvalue[$bestellungszaehler]['OrderStatus'] = $item->getElementsByTagName('status')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['FulfillmentChannel'] = "MFN";
					// $returnvalue[$bestellungszaehler]['ShipmentServiceLevelCategory'] = "";
					// $returnvalue[$bestellungszaehler]['ShipServiceLevel'] = "";
					$returnvalue[$bestellungszaehler]['Amount'] = $item->getElementsByTagName('total')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['CurrencyCode'] = "EUR";
					
					$zahlungsmethode = $item->getElementsByTagName('payment')->item(0)->nodeValue;
					if(	strpos($zahlungsmethode, "PP") !== false ||
						strpos($zahlungsmethode, "CC") !== false ||
						strpos($zahlungsmethode, "ELV") !== false ||
						strpos($zahlungsmethode, "SUE") !== false ||
						strpos($zahlungsmethode, "CB") !== false ||
						strpos($zahlungsmethode, "GP") !== false)
					{
						$returnvalue[$bestellungszaehler]['PaymentMethod'] = "Vorauskasse";
					}
					else if(strpos($zahlungsmethode, "INV") !== false)
					{
						$returnvalue[$bestellungszaehler]['PaymentMethod'] = "30-days";
					}
					else if(strpos($zahlungsmethode, "PAL") !== false)
					{
						$returnvalue[$bestellungszaehler]['PaymentMethod'] = "PayPal";
					}
					else
					{
						$returnvalue[$bestellungszaehler]['PaymentMethod'] = "Sonstiges";
					}
					
					$rechnungsdaten = $item->getElementsByTagName('client');
					$returnvalue[$bestellungszaehler]['BuyerName'] = "Rakuten-Kd-Nr-".$rechnungsdaten->item(0)->getElementsByTagName('client_id')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['Title'] = $rechnungsdaten->item(0)->getElementsByTagName('gender')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['Name'] = $rechnungsdaten->item(0)->getElementsByTagName('first_name')->item(0)->nodeValue." ".$rechnungsdaten->item(0)->getElementsByTagName('last_name')->item(0)->nodeValue;
					if (empty($rechnungsdaten->item(0)->getElementsByTagName('company')->item(0)->nodeValue))
					{
						$returnvalue[$bestellungszaehler]['AddressLine1'] = $rechnungsdaten->item(0)->getElementsByTagName('street')->item(0)->nodeValue." ".$rechnungsdaten->item(0)->getElementsByTagName('street_no')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['AddressLine2'] = $rechnungsdaten->item(0)->getElementsByTagName('address_add')->item(0)->nodeValue;
					}
					else
					{
						$returnvalue[$bestellungszaehler]['AddressLine1'] = $rechnungsdaten->item(0)->getElementsByTagName('company')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['AddressLine2'] = $rechnungsdaten->item(0)->getElementsByTagName('street')->item(0)->nodeValue." ".$rechnungsdaten->item(0)->getElementsByTagName('street_no')->item(0)->nodeValue;
						if (!empty($rechnungsdaten->item(0)->getElementsByTagName('address_add')->item(0)->nodeValue))
						{
							$returnvalue[$bestellungszaehler]['AddressLine2'] .= " / ".$rechnungsdaten->item(0)->getElementsByTagName('address_add')->item(0)->nodeValue;
						}
					}
					$returnvalue[$bestellungszaehler]['PostalCode'] = $rechnungsdaten->item(0)->getElementsByTagName('zip_code')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['City'] = $rechnungsdaten->item(0)->getElementsByTagName('city')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['CountryCode'] = $rechnungsdaten->item(0)->getElementsByTagName('country')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['StateOrRegion'] = utf8_encode("");
					$returnvalue[$bestellungszaehler]['BuyerEmail'] = $rechnungsdaten->item(0)->getElementsByTagName('email')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['Phone'] = $rechnungsdaten->item(0)->getElementsByTagName('phone')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['OrderComment'] = $item->getElementsByTagName('comment_client')->item(0)->nodeValue;
					
					$versanddaten = $item->getElementsByTagName('delivery_address');
					$returnvalue[$bestellungszaehler]['recipient-title'] = $versanddaten->item(0)->getElementsByTagName('gender')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['recipient-name'] =  $versanddaten->item(0)->getElementsByTagName('first_name')->item(0)->nodeValue." ".$versanddaten->item(0)->getElementsByTagName('last_name')->item(0)->nodeValue;
					if (empty($versanddaten->item(0)->getElementsByTagName('company')->item(0)->nodeValue))
					{
						$returnvalue[$bestellungszaehler]['ship-address-1'] = $versanddaten->item(0)->getElementsByTagName('street')->item(0)->nodeValue." ".$versanddaten->item(0)->getElementsByTagName('street_no')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['ship-address-2'] = $versanddaten->item(0)->getElementsByTagName('address_add')->item(0)->nodeValue;
					}
					else
					{
						$returnvalue[$bestellungszaehler]['ship-address-1'] = $versanddaten->item(0)->getElementsByTagName('company')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['ship-address-2'] = $versanddaten->item(0)->getElementsByTagName('street')->item(0)->nodeValue." ".$versanddaten->item(0)->getElementsByTagName('street_no')->item(0)->nodeValue;
						if (!empty($versanddaten->item(0)->getElementsByTagName('address_add')->item(0)->nodeValue))
						{
							$returnvalue[$bestellungszaehler]['ship-address-2'] .= " / ".$versanddaten->item(0)->getElementsByTagName('address_add')->item(0)->nodeValue;
						}
					}
					$returnvalue[$bestellungszaehler]['ship-postal-code'] = $versanddaten->item(0)->getElementsByTagName('zip_code')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['ship-city'] = $versanddaten->item(0)->getElementsByTagName('city')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['ship-country'] = $versanddaten->item(0)->getElementsByTagName('country')->item(0)->nodeValue;
					$returnvalue[$bestellungszaehler]['ship-state'] = utf8_encode("");
			
				    $itemcounter = 0;
				    $artikelanzahl = 0;
				    $orderItemsListOutput = array();
				    $artikelliste = $item->getElementsByTagName("items");
					foreach ($artikelliste as $einzelartikel)
	            	{
						$orderItemsListOutput[$itemcounter]['OrderItemId'] = $einzelartikel->getElementsByTagName('product_id')->item(0)->nodeValue;
						$orderItemsListOutput[$itemcounter]['SellerSKU'] = trim($einzelartikel->getElementsByTagName('product_art_no')->item(0)->nodeValue);
						// $orderItemsListOutput[$itemcounter]['ASIN'] = "";
						$orderItemsListOutput[$itemcounter]['ItemPrice'] = $einzelartikel->getElementsByTagName('price_sum')->item(0)->nodeValue;
						// $orderItemsListOutput[$itemcounter]['ItemTax'] = "";
						// $orderItemsListOutput[$itemcounter]['PromotionDiscount'] = ""; // Rabatte werden beim Artikel eingetragen
						if($itemcounter == 0)
						{
							$orderItemsListOutput[$itemcounter]['ShippingPrice'] = $item->getElementsByTagName('shipping')->item(0)->nodeValue; // Versandkosten werden beim Artikel eingetragen
							// $orderItemsListOutput[$itemcounter]['ShippingTax'] = "";
							// $orderItemsListOutput[$itemcounter]['ShippingDiscount'] = "";
							// $orderItemsListOutput[$itemcounter]['GiftWrapPrice'] = "";
							// $orderItemsListOutput[$itemcounter]['GiftWrapTax'] = "";
						}
						$orderItemsListOutput[$itemcounter]['QuantityOrdered'] = $einzelartikel->getElementsByTagName('qty')->item(0)->nodeValue;
						$artikelanzahl += $einzelartikel->getElementsByTagName('qty')->item(0)->nodeValue;
						$orderItemsListOutput[$itemcounter]['QuantityShipped'] = 0;
						$orderItemsListOutput[$itemcounter]['Title'] = $einzelartikel->getElementsByTagName('name')->item(0)->nodeValue;
	
	                    $itemcounter++;
                	}
                	
                	
                	$returnvalue[$bestellungszaehler]['orderItemsListOutput'] = $orderItemsListOutput;
                    $returnvalue[$bestellungszaehler]['NumberOfItemsShipped'] = 0;
                    $returnvalue[$bestellungszaehler]['NumberOfItemsUnshipped'] = $artikelanzahl;
                    
    				$bestellungszaehler++;
				}
            }
        }
        
        //	*** Testausgaben ***
		// 	echo $responseDoc->saveXML()."<br>";
		// 		
		// 	echo $returnvalue[0]['recipient-title']."<br>";
		// 	echo $returnvalue[0]['recipient-name']."<br>";
		// 	echo $returnvalue[0]['ship-address-1']."<br>";
		// 	echo $returnvalue[0]['ship-address-2']."<br>";
		// 	echo $returnvalue[0]['ship-postal-code']."<br>";
		// 	echo $returnvalue[0]['ship-city']."<br>";
		// 	echo $returnvalue[0]['ship-country']."<br>";
		// 	echo $returnvalue[0]['ship-state']."<br>";
		
		return $returnvalue;
    }
}