<?php

function getEbayOrders($fulfillmentchannel, $bestellungvom, $bestellungbis)
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
	
	$eBayGetData = new eBayApiClass();
	$eBayGetData->callEbay('GetOrders', $eBayGetData->_getOrderRequestBody($dateAfter, $dateBefore));
	
	$returnvalue = $eBayGetData->handleResultXML();
	
	foreach($returnvalue as $lfdNr => $opSet1)
	{
		$bearbeitungsstatus = checkAmazonOrderId($opSet1['AmazonOrderId']);
		$returnvalue[$lfdNr]['bearbeitungsstatus'] = $bearbeitungsstatus;
	}
	
	return $returnvalue;
}
 
class eBayApiClass
{
    private $_siteId = 77;  // default: Germany
    private $_eBayApiVersion = 1039;
 
    public function _getOrderRequestBody($dateAfter, $dateBefore)
    {
	    require "conf.php";
	    
		$search = array(	'%%USER_TOKEN%%',
        					'%%EBAY_API_VERSION%%',
        					'%%TIMEFROM%%',
  							'%%TIMETO%%',
        );
        $replace = array(	$eBayUserToken,
        					$this->_eBayApiVersion,
        					$dateAfter,
        					$dateBefore,
        );
 
        $requestXmlBody = file_get_contents('ebayGetOrders.xml');
        $requestXmlBody = str_replace($search, $replace, $requestXmlBody);
 
        // echo '<pre>', htmlentities($requestXmlBody), '</pre>';
        
        return $requestXmlBody;
    }
    
    public function callEbay($call, $requestBody)
    {
    	require "conf.php";
 
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $eBayServerUrl);
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
 
        $headers = array (
            'X-EBAY-API-COMPATIBILITY-LEVEL: ' . $this->_eBayApiVersion,
            'X-EBAY-API-DEV-NAME: ' . $eBayDEVID,
            'X-EBAY-API-APP-NAME: ' . $eBayAppID,
            'X-EBAY-API-CERT-NAME: ' . $eBayCertID,
            'X-EBAY-API-CALL-NAME: ' . $call,
            'X-EBAY-API-SITEID: ' . $this->_siteId,
            'Content-Type : text/xml',
        );
 
        curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($connection, CURLOPT_POST, 1);
 
        curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        $responseXml = curl_exec($connection);
        curl_close($connection);
        $this->_responseXml = $responseXml;
    }
 
    public function handleResultXML()
    {
	    require "conf.php";
		
		$returnvalue = array();	    
        // XML string is parsed and creates a DOM Document object
        $responseDoc = new DomDocument();
        $responseDoc->loadXML($this->_responseXml);
        
        // Get any error nodes
        $errors = $responseDoc->getElementsByTagName('Errors');
 
        // If there are error nodes
        if ($errors->length > 0)
        {
            echo '<P><B>eBay returned the following error(s):</B>';
            // Display each error
            // Get error code, ShortMesaage and LongMessage
            $code     = $errors->item(0)->getElementsByTagName('ErrorCode');
            $shortMsg = $errors->item(0)->getElementsByTagName('ShortMessage');
            $longMsg  = $errors->item(0)->getElementsByTagName('LongMessage');
            // Display code and shortmessage
            echo '<P>', $code->item(0)->nodeValue, ' : ', str_replace(">", "&gt;", str_replace("<", "&lt;", $shortMsg->item(0)->nodeValue));
            // If there is a long message (ie ErrorLevel=1), display it
            if (count($longMsg) > 0)
            {
            	echo '<BR>', str_replace(">", "&gt;", str_replace("<", "&lt;", $longMsg->item(0)->nodeValue));
            }
 
        }
        else	// There are no errors, generate array with results
        {
            // Get results nodes
            $responses = $responseDoc->getElementsByTagName("GetOrdersResponse");
            foreach ($responses as $response)
            {
                $ack = $response->getElementsByTagName("Ack")->item(0)->nodeValue;
                 if ($ack == "Success")
                 {
	                $items = $response->getElementsByTagName("Order");
	                $totalNumberOfEntries = $response->getElementsByTagName('TotalNumberOfEntries')->item(0)->nodeValue;

	                $bestellungszaehler = 0;
	                
					foreach ($items as $item)
		            {
	            		$returnvalue[$bestellungszaehler]['AmazonOrderId'] = $eBayBestellnummernprefix.$item->getElementsByTagName('OrderID')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['SellerOrderId'] = $eBayBestellnummernprefix.$item->getElementsByTagName('OrderID')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['PurchaseDate'] = $item->getElementsByTagName('CreatedDate')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['LastUpdateDate'] = $item->getElementsByTagName('LastModifiedTime')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['SalesChannel'] = $item->getElementsByTagName('Platform')->item(0)->nodeValue.".".$item->getElementsByTagName('TransactionSiteID')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['Language'] = $item->getElementsByTagName('TransactionSiteID')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['tax_number'] = "";
						$returnvalue[$bestellungszaehler]['tax_included'] = "t";
						$returnvalue[$bestellungszaehler]['MarketplaceId'] = $eBayAbteilungsname;
						// $returnvalue[$bestellungszaehler]['OrderType'] = "";
						$returnvalue[$bestellungszaehler]['OrderStatus'] = $item->getElementsByTagName('OrderStatus')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['FulfillmentChannel'] = "MFN";
						if (trim($eBayStandardVersandzentrum) != false)
						{
							$returnvalue[$bestellungszaehler]['fulfillment-center-id'] = $eBayStandardVersandzentrum;
						}
						else if (trim($StandardVersandzentrum) != false)
						{
							$returnvalue[$bestellungszaehler]['fulfillment-center-id'] = $StandardVersandzentrum;
						}
						else
						{
							$returnvalue[$bestellungszaehler]['fulfillment-center-id'] = "";
						}
						$shippingServiceSubtree = $item->getElementsByTagName("ShippingServiceSelected");
						$returnvalue[$bestellungszaehler]['ShipmentServiceLevelCategory'] = $shippingServiceSubtree->item(0)->getElementsByTagName('ShippingService')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['ShipServiceLevel'] = "";
						
						$returnvalue[$bestellungszaehler]['Amount'] = $item->getElementsByTagName('Total')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['CurrencyCode'] = $item->getElementsByTagName('Total')->item(0)->getAttribute('currencyID');
						$returnvalue[$bestellungszaehler]['IsBusinessOrder'] = "false";
						$returnvalue[$bestellungszaehler]['PaymentMethod'] = "eBay";
						$zahlungsmethode = $item->getElementsByTagName('PaymentMethod')->item(0)->nodeValue;
						if(strpos($zahlungsmethode, "MoneyXfer") !== false)
						{
							$returnvalue[$bestellungszaehler]['PaymentMethodDetail'] = "Vorauskasse";
						}
						else if(strpos($zahlungsmethode, "PayPal") !== false)
						{
							$returnvalue[$bestellungszaehler]['PaymentMethodDetail'] = "PayPal";
						}
						else
						{
							$returnvalue[$bestellungszaehler]['PaymentMethodDetail'] = "Sonstiges";
						}
						$returnvalue[$bestellungszaehler]['BuyerName'] = $item->getElementsByTagName('BuyerUserID')->item(0)->nodeValue;
						//$returnvalue[$bestellungszaehler]['Title'] = "";
						$returnvalue[$bestellungszaehler]['Name'] = $item->getElementsByTagName('Name')->item(0)->nodeValue;
						// einfuegen ---> $item->getElementsByTagName('CompanyName')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['AddressLine1'] = $item->getElementsByTagName('Street1')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['AddressLine2'] = $item->getElementsByTagName('Street2')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['PostalCode'] = $item->getElementsByTagName('PostalCode')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['City'] = $item->getElementsByTagName('CityName')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['CountryCode'] = $item->getElementsByTagName('Country')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['StateOrRegion'] = $item->getElementsByTagName('StateOrProvince')->item(0)->nodeValue;
						// $returnvalue[$bestellungszaehler]['recipient-title'] = utf8_encode("");
						$returnvalue[$bestellungszaehler]['recipient-name'] = $item->getElementsByTagName('Name')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['ship-address-1'] = $item->getElementsByTagName('Street1')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['ship-address-2'] = $item->getElementsByTagName('Street2')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['ship-postal-code'] = $item->getElementsByTagName('PostalCode')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['ship-city'] = $item->getElementsByTagName('CityName')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['ship-country'] = $item->getElementsByTagName('Country')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['ship-state'] = $item->getElementsByTagName('StateOrProvince')->item(0)->nodeValue;
						$returnvalue[$bestellungszaehler]['BuyerEmail'] = $item->getElementsByTagName('Email')->item(0)->nodeValue;
						if ($item->getElementsByTagName('Phone')->item(0)->nodeValue != "Invalid Request")
						{
							$returnvalue[$bestellungszaehler]['Phone'] = $item->getElementsByTagName('Phone')->item(0)->nodeValue;
						}
						// $returnvalue[$bestellungszaehler]['OrderComment'] = utf8_encode("");
				
					    $itemcounter = 0;
					    $orderItemsListOutput = array();

						$orderItemsListOutput[$itemcounter]['ShippingPrice'] = $shippingServiceSubtree->item(0)->getElementsByTagName('ShippingServiceCost')->item(0)->nodeValue; // Versandkosten werden nur beim ersten Artikel eingetragen
						$orderItemsListOutput[$itemcounter]['ShippingTax'] = "";
						$orderItemsListOutput[$itemcounter]['ShippingDiscount'] = "";
						// $orderItemsListOutput[$itemcounter]['GiftWrapPrice'] = "";
						// $orderItemsListOutput[$itemcounter]['GiftWrapTax'] = "";

					    $transactions = $item->getElementsByTagName("Transaction");

					    foreach ($transactions as $transaction)
					    {
						    $returnvalue[$bestellungszaehler]['NumberOfItems'] += $transaction->getElementsByTagName('QuantityPurchased')->item(0)->nodeValue;
							$orderItemsListOutput[$itemcounter]['OrderItemId'] = $transaction->getElementsByTagName('ItemID')->item(0)->nodeValue;
							$orderItemsListOutput[$itemcounter]['SellerSKU'] = trim($transaction->getElementsByTagName('SKU')->item(0)->nodeValue);
							// $orderItemsListOutput[$itemcounter]['ASIN'] = "";
							$orderItemsListOutput[$itemcounter]['ItemPrice'] = $transaction->getElementsByTagName('TransactionPrice')->item(0)->nodeValue * $transaction->getElementsByTagName('QuantityPurchased')->item(0)->nodeValue;
							// $orderItemsListOutput[$itemcounter]['ItemTax'] = "";
							// $orderItemsListOutput[$itemcounter]['PromotionDiscount'] = ""; // Rabatte werden beim Artikel eingetragen
							$orderItemsListOutput[$itemcounter]['QuantityOrdered'] = $transaction->getElementsByTagName('QuantityPurchased')->item(0)->nodeValue;
							$orderItemsListOutput[$itemcounter]['QuantityShipped'] = 0;
							$orderItemsListOutput[$itemcounter]['Title'] = $transaction->getElementsByTagName('Title')->item(0)->nodeValue;
							$itemcounter++;
						}

						$returnvalue[$bestellungszaehler]['orderItemsListOutput'] = $orderItemsListOutput;

	                    $bestellungszaehler++;
					}
				}
            }
        }
		// echo $responseDoc->saveXML();
        // var_dump($responses);
		return $returnvalue;
    }
}