<?php
if (!isset($_SERVER['PHP_AUTH_USER']))
{
	Header("WWW-Authenticate: Basic realm=\"Shopdaten-Import\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "Sie m&uuml;ssen sich autentifizieren\n";
	exit;
}
else
{
	require "conf.php";
	require "constants.php";
	
	function cmpversand($a, $b)
	{
		return strcmp($a['PurchaseDate'], $b['PurchaseDate'])*(-1);
	}

	if ($_SERVER['PHP_AUTH_USER']<>$ERPftpuser || $_SERVER['PHP_AUTH_PW']<>$ERPftppwd)
	{
		Header("WWW-Authenticate: Basic realm=\"Shopdaten-Import\"");
		Header("HTTP/1.0 401 Unauthorized");
		echo "Sie m&uuml;ssen sich autentifizieren\n";
		exit;
	}
	
	require "erpfunctions.php";
	require "amazonfunctions.php";
	require "ebayfunctions.php";
	require "joomlafunctions.php";
	require "rakutenfunctions.php";
	
	$autoimport = 0;
	if (isset($_GET["autoimport"])) {
		$autoimport=$_GET["autoimport"];
	}
	
	if (isset($_POST["bestellungvom"])) {
		$bestellungvom_DATA = $_POST["bestellungvom"];
		$date_from = explode("-", $_POST["bestellungvom"]);
		$bestellungvom = strtoupper(gmdate("d-M-Y", mktime(12, 0, 0, $date_from[1], $date_from[0], $date_from[2])));
	} else {
		$bestellungvom_DATA = strtoupper(gmdate("d-m-Y", time()-86400*$daysBeforeFrom));
		$bestellungvom = strtoupper(gmdate("d-M-Y", time()-86400*$daysBeforeFrom));
	}
	if (isset($_POST["bestellungbis"])) {
		$bestellungbis_DATA = $_POST["bestellungbis"];
		$date_bis = explode("-", $_POST["bestellungbis"]);
		$bestellungbis = strtoupper(gmdate("d-M-Y", mktime(12, 0, 0, $date_bis[1], $date_bis[0], $date_bis[2])));
	} else {
		$bestellungbis_DATA = strtoupper(gmdate("d-m-Y", time()-120));
		$bestellungbis = strtoupper(gmdate("d-M-Y", time()-120));
	}
	if ((isset($_POST["fulfillmentchannel"]) &&  $_POST["fulfillmentchannel"] == "haendler")
		|| (isset($_GET["fulfillmentchannel"]) &&  $_GET["fulfillmentchannel"] == "haendler")) {
		$fulfillmentchannel = "haendler";
		$amazon_checked = "";
		$haendler_checked = "checked=\"checked\"";
	} else {
		$fulfillmentchannel = "amazon";
		$amazon_checked = "checked=\"checked\"";
		$haendler_checked = "";
	}
	$Alle_checked = "";	
	$Amazon_checked = "";	
	$Ebay_checked = "";
	$Joomla_checked = "";
	$Rakuten_checked = "";
	if (isset($_POST["filter"])) {
		if ($_POST["filter"] == "Alle") {
			$filter = "Alle";
			$Alle_checked = "checked=\"checked\"";
		}
		if ($_POST["filter"] == "Amazon") {
			$filter = "Amazon";
			$Amazon_checked = "checked=\"checked\"";
		}
		if ($_POST["filter"] == "Ebay") {
			$filter = "Ebay";
			$Ebay_checked = "checked=\"checked\"";
		}
		if ($_POST["filter"] == "Joomla") {
			$filter = "Joomla";
			$Joomla_checked = "checked=\"checked\"";
		}
		if ($_POST["filter"] == "Rakuten") {
			$filter = "Rakuten";
			$Rakuten_checked = "checked=\"checked\"";
		}
	} else {
		$filter = "Alle";
		$Alle_checked = "checked=\"checked\"";
	}
	// Sellingdata CSV ausgeben
	if (isset($_POST["sellingscsv"]))
	{
		getSellingInfo($bestellungvom, $bestellungbis, true);
		exit;
	}

	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/transitional.dtd\">";
	echo "<html>";
	echo "<head>";
	echo "	<title>Amazon-Import</title>";
	echo "	<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
	echo "	<script type=\"text/javascript\" src=\"calendarDateInput.js\"></script>";
	echo "	<style>
				body {font-family: \"Lato\", sans-serif; font-size: 0.75em;}
				/* Style the tab */
				div.tab { overflow: hidden; border: 1px solid #ccc; background-color: #f1f1f1; }
				/* Style the buttons inside the tab */
				div.tab button { background-color: inherit; float: left; border: none; outline: none; cursor: pointer; padding: 14px 16px; transition: 0.3s; font-size: 17px; }
				/* Change background color of buttons on hover */
				div.tab button:hover { background-color: #ddd; }
				/* Create an active/current tablink class */
				div.tab button.active { background-color: #ccc; }
				/* Style the tab content */
				.tabcontent { display: none; padding: 6px 12px; border: 1px solid #ccc; border-top: none; }
				div.spalte { display: table-cell; width: 400px; vertical-align:top; }
			</style>";
	echo "</head>";
	echo "<body>";	
	echo "<form name=\"gesamtformular\" action=\"shoptoerp.php\" method=\"post\">";
	echo	"<table style=\"background-color:#cccccc\">"
		 		."<tr>"
		 			."<td>Bestellungen vom </td>"
					."<td><script>DateInput('bestellungvom', true, 'DD-MM-YYYY', '".$bestellungvom."')</script></td>"
					."<td>Bestellungen bis </td>"
					."<td><script>DateInput('bestellungbis', true, 'DD-MM-YYYY', '".$bestellungbis."')</script></td>"
				."</tr>"
				."<tr>"
					."<td>Amazon Fulfillment (nur Amazon)</td>"
					."<td><input type=\"radio\" name=\"fulfillmentchannel\" value=\"amazon\" ".$amazon_checked."></td>"
					."<td>Haendler Fulfillment (<input type=\"radio\" name=\"filter\" value=\"Alle\" ".$Alle_checked.">Alle";
					if ($Amazonaktiviert == "checked") { echo ", <input type=\"radio\" name=\"filter\" value=\"Amazon\" ".$Amazon_checked.">Amazon"; }
					if ($eBayaktiviert == "checked") { echo ", <input type=\"radio\" name=\"filter\" value=\"Ebay\" ".$Ebay_checked.">Ebay"; }
					if ($Joomlaaktiviert == "checked") { echo ", <input type=\"radio\" name=\"filter\" value=\"Joomla\" ".$Joomla_checked.">Joomla"; }
					if ($Rakutenaktiviert == "checked") { echo ", <input type=\"radio\" name=\"filter\" value=\"Rakuten\" ".$Rakuten_checked.">Rakuten"; }
	echo			")</td><td><input type=\"radio\" name=\"fulfillmentchannel\" value=\"haendler\" ".$haendler_checked."></td>"
				."</tr>"
			."</table>";
	echo 	"<br><input type=\"submit\" name=\"bestellungen\" value=\"Bestellungen anzeigen\">";
	echo 	"&nbsp;&nbsp;<input type=\"submit\" name=\"sellings\" value=\"Produktverkaufstabelle\">";
	echo 	"&nbsp;&nbsp;<input type=\"submit\" name=\"sellingscsv\" value=\"Verkaufstabelle als .csv\"><br>";
	
	if (isset($_POST["bestellungen"]) || $autoimport)
	{
		$output = array();
		if ($Amazonaktiviert == "checked" && (($fulfillmentchannel == "amazon" && $AmazonFBAaktiviert == "checked") ||
		                                      ($fulfillmentchannel == "haendler" && $filter == "Alle" && $AmazonMFNaktiviert == "checked") ||
		                                      ($fulfillmentchannel == "haendler" && $filter == "Amazon" && $AmazonMFNaktiviert == "checked")))
		{
				$amazonresult = getAmazonOrders($fulfillmentchannel, $bestellungvom_DATA, $bestellungbis_DATA, "EU");
				if(count($amazonresult) > 0)
				{
					$output = array_merge($output, $amazonresult);
				}
		}
		if ($Amazonaktiviert_COM == "checked" && (($fulfillmentchannel == "amazon" && $AmazonFBAaktiviert_COM == "checked") ||
		                                          ($fulfillmentchannel == "haendler" && $filter == "Alle" && $AmazonMFNaktiviert_COM == "checked") ||
		                                          ($fulfillmentchannel == "haendler" && $filter == "Amazon" && $AmazonMFNaktiviert_COM == "checked")))
		{
				$amazonresult = getAmazonOrders($fulfillmentchannel, $bestellungvom_DATA, $bestellungbis_DATA, "COM");
				if(count($amazonresult) > 0)
				{
					$output = array_merge($output, $amazonresult);
				}
		}			
		if ($eBayaktiviert == "checked" && ($filter == "Alle" || $filter == "Ebay"))
		{
			$ebayresult = getEbayOrders($fulfillmentchannel, $bestellungvom_DATA, $bestellungbis_DATA);
			if(count($ebayresult) > 0)
			{
				$output = array_merge($output, $ebayresult);
			}
		}
		if ($Joomlaaktiviert == "checked" && ($filter == "Alle" || $filter == "Joomla"))
		{
			$joomlaresult = getJoomlaOrders($fulfillmentchannel, $bestellungvom_DATA, $bestellungbis_DATA);
			if(count($joomlaresult) > 0)
			{
				$output = array_merge($output, $joomlaresult);
			}
		}
		if ($Rakutenaktiviert == "checked" && ($filter == "Alle" || $filter == "Rakuten"))
		{
			$Rakutenresult = getRakutenOrders($fulfillmentchannel, $bestellungvom_DATA, $bestellungbis_DATA);
			if(count($Rakutenresult) > 0)
			{
				$output = array_merge($output, $Rakutenresult);
			}
		}

		// output sortieren
		usort($output, "cmpversand");
	
		echo "<br>Bestellungen: ";
		
		// wenn Fehler, diese ausgeben, sonst Rückgabe in Tabelle anzeigen:
		if (array_key_exists('error', $output) && $output['error'])
		{
			foreach($output['error'] as $oeSet)
			{
		  		echo $oeSet;
			}
		}
		else
		{
			if (!$autoimport)
			{
				echo "<input type=\"submit\" name=\"import\" value=\"Ausgewaehltes importieren\"><br><br>";
			}
		 	echo	"<table border=\"1\">"
				 		."<tr>"
				 			."<th>Nr.</th>"
				 			."<th>Import</th>"
				 			."<th>Porto (bei Import)</th>"
		 					."<th>Bestellnummer</th>"
		 					."<th>Marktpl. (Startlager/Startland >> Zielland) / Steuerzone</th>"
		 					."<th>Datum / Versandart</th>"
		 					."<th>Name / Sprache</th>"
		 					."<th>Status</th>"
		 					."<th>Betrag</th>"
		 					."<th>Artikel</th>"
		 				."</tr>";
		 	// Ersatzsprache in Array umwandeln
			$replaceLanguage = array();
			foreach (explode("\n", $ersatzSprache) as $einzelSprache)
			{
				$zerlegteEinzelsprache = explode("|", $einzelSprache);
				if(count($zerlegteEinzelsprache) == 2)
				{
					$replaceLanguage[trim($zerlegteEinzelsprache[0])] = trim($zerlegteEinzelsprache[1]);
				}
			}
			
			$bestellungen_autoimport = array();			

			// Zusatzprodukte vorbereiten
			$zusatzSKU = array();
			foreach (explode("\n", $zusatzProdukt) as $einzelZusatzProdukt)
			{
				$zerlegtesEinzelZusatzProdukt = explode("|", $einzelZusatzProdukt);
				if (count($zerlegtesEinzelZusatzProdukt) == 3 || count($zerlegtesEinzelZusatzProdukt) == 4)
				{
					$zerlegtesEinzelZusatzProduktMarktplaetze = explode('@', $zerlegtesEinzelZusatzProdukt[0]);
					if (!isset($zusatzSKU[$zerlegtesEinzelZusatzProduktMarktplaetze[0]]['Marktplaetze']))
					{
						$zusatzSKU[$zerlegtesEinzelZusatzProduktMarktplaetze[0]]['Marktplaetze'] = array();
					}
					if (count($zerlegtesEinzelZusatzProduktMarktplaetze) > 1)
					{
						// --- Marktplatzbezogene Zusatzprodukte
						foreach ($zerlegtesEinzelZusatzProduktMarktplaetze as $value)
						{
							$zusatzSKU[$zerlegtesEinzelZusatzProduktMarktplaetze[0]]['Marktplaetze'][$value]['Zusatzprodukt'] = trim($zerlegtesEinzelZusatzProdukt[1]);
							$zusatzSKU[$zerlegtesEinzelZusatzProduktMarktplaetze[0]]['Marktplaetze'][$value]['Anzahl'] = trim($zerlegtesEinzelZusatzProdukt[2]);
							if (count($zerlegtesEinzelZusatzProdukt) == 4)
							{
								$zusatzSKU[$zerlegtesEinzelZusatzProduktMarktplaetze[0]]['Marktplaetze'][$value]['Bezeichnung'] = trim($zerlegtesEinzelZusatzProdukt[3]);
							}								
						}
					}
					else
					{
						// --- Generische Zusatzprodukte
						$zusatzSKU[$zerlegtesEinzelZusatzProduktMarktplaetze[0]]['Marktplaetze']['All']['Zusatzprodukt'] = trim($zerlegtesEinzelZusatzProdukt[1]);
						$zusatzSKU[$zerlegtesEinzelZusatzProduktMarktplaetze[0]]['Marktplaetze']['All']['Anzahl'] = trim($zerlegtesEinzelZusatzProdukt[2]);
						if (count($zerlegtesEinzelZusatzProdukt) == 4)
						{
							$zusatzSKU[$zerlegtesEinzelZusatzProduktMarktplaetze[0]]['Marktplaetze']['All']['Bezeichnung'] = trim($zerlegtesEinzelZusatzProdukt[3]);
						}
					}
				}
			}
			// Ersatz SKU vorbereiten
			$searchSKU = array();
			$replaceSKU = array();
			foreach (explode("\n", $ersatzSKU) as $einzelSKU)
			{
				$zerlegteEinzelSKU = explode("|", $einzelSKU);
				if(count($zerlegteEinzelSKU) == 2)
				{
					$searchSKU[] = trim($zerlegteEinzelSKU[0]);
					$replaceSKU[] = trim($zerlegteEinzelSKU[1]);
				}
			}			
						
			foreach($output as $lfdNr => $opSet1)
			{
				if(trim($opSet1['Language']) == false)
				{
					$opSet1['Language'] = $standardsprache;
				}
				$bearbeitungsstatus = $opSet1['bearbeitungsstatus'];
				
				// ----- Zusatzprodukte einfügen, bevor die SKU korrigiert werden, dann an $opSet1 anfügen -----
				$zuErgaenzendeProdukte = array();
				if (array_key_exists('orderItemsListOutput', $opSet1))
				{
					foreach($opSet1['orderItemsListOutput'] as $orderItem)
					{
						if (array_key_exists($orderItem['SellerSKU'], $zusatzSKU))
						{
							// --- Generische Zusatzprodukte hinzufügen
							if (array_key_exists('All', $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze']))
							{
								$newOrderItem = array();
								$newOrderItem['AmazonOrderId'] = $orderItem['AmazonOrderId'];
								$newOrderItem['OrderItemId'] = "Zusatzartikel";
								$newOrderItem['SellerSKU'] = $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze']['All']['Zusatzprodukt'];
								$newOrderItem['ASIN'] = "";
								$newOrderItem['ItemPrice'] = 0.00;
								$newOrderItem['ItemTax'] = 0.00;
								$newOrderItem['PromotionDiscount'] = 0.00;
								$newOrderItem['ShippingPrice'] = 0.00;
								$newOrderItem['ShippingTax'] = 0.00;
								$newOrderItem['ShippingDiscount'] = 0.00;
								$newOrderItem['GiftWrapPrice'] = 0.00;
								$newOrderItem['GiftWrapTax'] = 0.00;
								if ($zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze']['All']['Anzahl'] == '*')
								{
									$newOrderItem['QuantityOrdered'] = $orderItem['QuantityOrdered'];
									$newOrderItem['QuantityShipped'] = $orderItem['QuantityShipped'];
								}
								else
								{
									$newOrderItem['QuantityOrdered'] = $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze']['All']['Anzahl'];
									$newOrderItem['QuantityShipped'] = $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze']['All']['Anzahl'];
								}
								if (array_key_exists('Bezeichnung', $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze']['All']))
								{
									$newOrderItem['Title'] = $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze']['All']['Bezeichnung'];
								}
								else
								{
									$newOrderItem['Title'] = "";
								}
								$newOrderItem['SerialNumber'] = str_replace($searchSKU, $replaceSKU, $orderItem['SellerSKU']);
								$zuErgaenzendeProdukte[] = $newOrderItem;
								$opSet1['NumberOfItems']++;
							}
							// --- Marktplatzbezogene Zusatzprodukte hinzufügen
							if (array_key_exists($opSet1['SalesChannel'], $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze']))
							{
								$newOrderItem = array();
								$newOrderItem['AmazonOrderId'] = $orderItem['AmazonOrderId'];
								$newOrderItem['OrderItemId'] = "Zusatzartikel";
								$newOrderItem['SellerSKU'] = $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze'][$opSet1['SalesChannel']]['Zusatzprodukt'];
								$newOrderItem['ASIN'] = "";
								$newOrderItem['ItemPrice'] = 0.00;
								$newOrderItem['ItemTax'] = 0.00;
								$newOrderItem['PromotionDiscount'] = 0.00;
								$newOrderItem['ShippingPrice'] = 0.00;
								$newOrderItem['ShippingTax'] = 0.00;
								$newOrderItem['ShippingDiscount'] = 0.00;
								$newOrderItem['GiftWrapPrice'] = 0.00;
								$newOrderItem['GiftWrapTax'] = 0.00;
								if ($zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze'][$opSet1['SalesChannel']]['Anzahl'] == '*')
								{
									$newOrderItem['QuantityOrdered'] = $orderItem['QuantityOrdered'];
									$newOrderItem['QuantityShipped'] = $orderItem['QuantityShipped'];
								}
								else
								{
									$newOrderItem['QuantityOrdered'] = $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze'][$opSet1['SalesChannel']]['Anzahl'];
									$newOrderItem['QuantityShipped'] = $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze'][$opSet1['SalesChannel']]['Anzahl'];
								}
								if (array_key_exists('Bezeichnung', $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze'][$opSet1['SalesChannel']]))
								{
									$newOrderItem['Title'] = $zusatzSKU[$orderItem['SellerSKU']]['Marktplaetze'][$opSet1['SalesChannel']]['Bezeichnung'];
								}
								else
								{
									$newOrderItem['Title'] = "";
								}
								$newOrderItem['SerialNumber'] = str_replace($searchSKU, $replaceSKU, $orderItem['SellerSKU']);
								$zuErgaenzendeProdukte[] = $newOrderItem;
								$opSet1['NumberOfItems']++;
							}
						}
					}
					// Die neuen Produkte zur Liste ergänzen
					$opSet1['orderItemsListOutput'] = array_merge($opSet1['orderItemsListOutput'], $zuErgaenzendeProdukte);
				}
				
				// Sprache zuordnen
				if(trim($opSet1['MarketplaceId']) == trim("Amazon"))
				{
					if (array_key_exists($opSet1['SalesChannel'], $replaceLanguage))
					{
						$opSet1['Language'] = $replaceLanguage[$opSet1['SalesChannel']];
					}
				}
				else
				{
					if (array_key_exists($opSet1['Language'], $replaceLanguage))
					{
						$opSet1['Language'] = $replaceLanguage[$opSet1['Language']];
					}
				}					
				echo 	"<tr valign=\"top\">";
				echo 		"<td>".$lfdNr."</td>";
							if ($bearbeitungsstatus == "auftrag")
							{
								echo "<td>Auftrag vorhanden</td>";
							}
							elseif ($bearbeitungsstatus == "lieferschein")
							{
								echo "<td>Lieferschein vorhanden</td>";
							}
							elseif ($bearbeitungsstatus == "rechnung")
							{
								echo "<td>Rechnung vorhanden</td>";
							}
							elseif ($bearbeitungsstatus == "email")
							{
								echo "<td>Email verschickt</td>";
							}
							elseif ($bearbeitungsstatus == "neu")
							{
								if (array_key_exists('error', $opSet1) && $opSet1['error'])
								{
									echo "<td bgcolor=\"red\">Bestellte Produkte Abfragefehler!</td>";
								}
								else
								{
									if ($opSet1['OrderStatus'] == "Shipped" || $opSet1['OrderStatus'] == "shipped" ||
										($opSet1['OrderStatus'] == "Unshipped" && $opSet1['FulfillmentChannel'] == "MFN") ||
										($opSet1['OrderStatus'] == "Pending payment" && $opSet1['FulfillmentChannel'] == "MFN") ||
										($opSet1['OrderStatus'] == "Paid" && $opSet1['FulfillmentChannel'] == "MFN") ||
										($opSet1['OrderStatus'] == "Completed" && $opSet1['MarketplaceId'] == $eBayAbteilungsname) ||
										($opSet1['OrderStatus'] == "Active" && $opSet1['MarketplaceId'] == $eBayAbteilungsname) ||
										($opSet1['OrderStatus'] == "editable" && $opSet1['MarketplaceId'] == $RakutenAbteilungsname) ||
										($opSet1['OrderStatus'] == "payout" && $opSet1['MarketplaceId'] == $RakutenAbteilungsname))
									{
										echo "<td>";
										echo "<input type=\"checkbox\" name=\"importauswahl[]\" value=\"".$opSet1['AmazonOrderId']."\" ";
										if ($amazon_checked)
										{
											echo "checked=\"checked\"";
										}
										echo ">";
										
										// Wertefelder fuer Import vorbereiten
										foreach(array_keys($paramsOrders) as $param)
										{
											if (array_key_exists($param, $opSet1))
											{
												echo "<input type=\"hidden\" name=\"".$param."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1[$param]."\">";
											}
										}
										echo "</td>";

										if ($amazon_checked && $autoimport)
										{
											// Wertefelder fuer Autoimport vorbereiten
											foreach(array_keys($paramsOrders) as $param)
											{
												if (array_key_exists($param, $opSet1))
												{
													$bestellungen_autoimport[$opSet1['AmazonOrderId']][$param] = $opSet1[$param];
												}
											}
										}
									}
									else 
									{
										echo "<td>nicht versendet</td>";
									}
								}
							}
							else
							{
								echo "<td>$bearbeitungsstatus</td>";
							}
							// Portoinfo
							if ($fulfillmentchannel == "amazon")
							{
								echo "<td></td>";
							}
							else
							{
								echo "<td>";
								if (checkPortostatusOfOrderId($opSet1['AmazonOrderId']))
								{
									echo "bereits erstellt";
								}
								elseif ($opSet1['ShipmentServiceLevelCategory'] == "Expedited" && !array_key_exists('error', $opSet1) && !$opSet1['error'])
								{
									echo "Expresssendung";
								}
								elseif ($bearbeitungsstatus == "auftrag" && !array_key_exists('error', $opSet1) && !$opSet1['error'])
								{
									$order_id	= $opSet1['AmazonOrderId'];
								    $name		= $opSet1['recipient-name'];
								    $department = $opSet1['ship-address-1'];
								    $street		= $opSet1['ship-address-2'];
								    $postcode	= $opSet1['ship-postal-code'];
								    $city		= $opSet1['ship-city'];
								    $country_ISO_code = $opSet1['ship-country'];
							    	if (array_key_exists($opSet1['ship-country'], $GLOBALS["LAND"]))
							    	{
								    	$country = utf8_encode($GLOBALS["LAND"][$opSet1['ship-country']]);
							    	}
							    	else
							    	{
								    	$country = $opSet1['ship-country'];
							    	}
								    $telephone	= $opSet1['Phone'];
								    $email		= $opSet1['BuyerEmail'];
								    $customs_currency = $opSet1['CurrencyCode'];
								    
								    $export_details = array();
								    $zaehler = 0;
									foreach($opSet1['orderItemsListOutput'] as $orderItem)
									{
										$export_details[$zaehler]['sku'] = rawurlencode(str_replace($searchSKU, $replaceSKU, $orderItem['SellerSKU']));
										if (!empty($orderItem['SerialNumber']))
										{
											$export_details[$zaehler]['sku'] .= rawurlencode('@'.$orderItem['SerialNumber']);
										}											
										$export_details[$zaehler]['item_amount'] = rawurlencode($orderItem['QuantityOrdered']);
										$export_details[$zaehler]['customs_value'] = rawurlencode($orderItem['ItemPrice']);											
										$export_details[$zaehler]['goods_description'] = rawurlencode($orderItem['Title']);
										$zaehler++;
									}
									
									$get_link = "cusordnumber=".rawurlencode($order_id)."&".
												"name=".rawurlencode($name)."&".
												"department=".rawurlencode($department)."&".
												"street=".rawurlencode($street)."&".
												"postcode=".rawurlencode($postcode)."&".
												"city=".rawurlencode($city)."&".
												"country_ISO_code=".$country_ISO_code."&".
												"country=".rawurlencode($country)."&".
												"telephone=".rawurlencode($telephone)."&".
												"email=".rawurlencode($email)."&".
												"customs_currency=".$customs_currency."&".
												"count=".$zaehler;
									foreach($export_details as $lfdNrexport_details => $single_export_details)
									{
										$get_link .= "&".$lfdNrexport_details."=".$single_export_details['sku']."|".$single_export_details['item_amount']."|".$single_export_details['customs_value']."|".$single_export_details['goods_description'];
									}
									echo "<a style=\"-webkit-appearance: button; -moz-appearance: button; appearance: button; text-decoration: none; color: initial;\" target=\"_blank\" href=\"porto/porto.php?".$get_link."\" class=\"button\">&nbsp;Porto per Hand&nbsp;</a>";
									echo " (<input type=\"checkbox\" disabled>)";										
								}
								elseif ($bearbeitungsstatus == "neu" && !array_key_exists('error', $opSet1) && !$opSet1['error'])
								{
									echo "<a style=\"-webkit-appearance: button; -moz-appearance: button; appearance: button; text-decoration: none; color: Lightgray; pointer-events: none; cursor: default;\">&nbsp;Porto per Hand&nbsp;</a>";
									echo " (<input type=\"checkbox\" name=\"portoauswahl[]\" value=\"".$opSet1['AmazonOrderId']."\">)";
								}
								else
								{
									echo "nicht moeglich";
								}
								echo "</td>";
							}
							
							$date = new DateTime($opSet1['PurchaseDate']);
							
							$fulfillmentCenterLand = "";		
							$fulfillmentCenterIdData;
							if(array_key_exists('fulfillment-center-id', $opSet1))
							{
								$fulfillmentCenterIdData = checkFulfillmentCenterId($opSet1['fulfillment-center-id']);
								if ($fulfillmentCenterIdData)
								{
									$fulfillmentCenterLand = $opSet1['fulfillment-center-id'] . " / ".$fulfillmentCenterIdData['Country'];
								}								
								else
								{
									$fulfillmentCenterLand = $opSet1['fulfillment-center-id'] . " / ?";
								}
							}
							
							$taxzonedata = getTaxzone($fulfillmentCenterIdData['Country'], $opSet1['ship-country'], $opSet1['tax_number']);
							
							echo "<td>".$opSet1['AmazonOrderId']."</td>"
								."<td>".$opSet1['SalesChannel']." (".$fulfillmentCenterLand." >> ".$opSet1['ship-country'].") / ".$taxzonedata["NAME"]."</td>"
								."<td>".$date->format('Y-m-d H:i')." / ". $opSet1['ShipmentServiceLevelCategory']."</td>";
								
							echo "<td>".$opSet1['BuyerName']." / ".$opSet1['Language']."</td>";
				echo		"<td>".$opSet1['OrderStatus']."</td>";
				echo		"<td>".$opSet1['Amount']." ".$opSet1['CurrencyCode']."</td>";
							
				if (array_key_exists('error', $opSet1) && $opSet1['error'])
				{
					foreach($opSet1['error'] as $oeSet)
					{
						echo "<td>".$oeSet."</td>";
					}
				}
				else
				{
					echo "<td>";
					$zaehler = 0;
					foreach($opSet1['orderItemsListOutput'] as $lfdNrOrderItem => $orderItem)
					{
						$promotiondiscount_text = "";
						if (isset($orderItem['PromotionDiscount']) && $orderItem['PromotionDiscount'] > 0.0)
						{
							$promotiondiscount_text = " PromotionDiscount ".$orderItem['PromotionDiscount'];
						}
						$shipping_text = "";
						if (isset($orderItem['ShippingPrice']) && $orderItem['ShippingPrice'] > 0.0)
						{
							$shipping_text = " Shipping ".$orderItem['ShippingPrice'];
						}
						$shippingdiscount_text = "";
						if (isset($orderItem['ShippingDiscount']) && abs($orderItem['ShippingDiscount']) > 0.0)
						{
							$shippingdiscount_text = " ShippingDiscount ".abs($orderItem['ShippingDiscount']);
						}
						$giftwrap_text = "";
						if (isset($orderItem['GiftWrapPrice']) && $orderItem['GiftWrapPrice'] > 0.0)
						{
							$giftwrap_text = " GiftWrap ".$orderItem['GiftWrapPrice'];
						}
						
						$count = 0;
						str_replace($searchSKU, $replaceSKU, $orderItem['SellerSKU'], $count);
						if ($count > 0)
						{
							echo $orderItem['SellerSKU']." (".str_replace($searchSKU, $replaceSKU, $orderItem['SellerSKU'])."); Ordered ".$orderItem['QuantityOrdered']."; Price ".$orderItem['ItemPrice'].$promotiondiscount_text.$shipping_text.$shippingdiscount_text.$giftwrap_text."<br>";
						}
						else
						{
							echo $orderItem['SellerSKU']."; Ordered ".$orderItem['QuantityOrdered']."; Price ".$orderItem['ItemPrice'].$promotiondiscount_text.$shipping_text.$shippingdiscount_text.$giftwrap_text."<br>";
						}
						
						// Nachdem alles andere verarbeitet ist, an dieser Stellt die Ersatz-SKU einfügen
						$orderItem['SellerSKU'] = str_replace($searchSKU, $replaceSKU, $orderItem['SellerSKU']);
						
						echo "<input type=\"hidden\" name=\"AmazonOrderIdProducts"."|".$opSet1['AmazonOrderId']."|".$lfdNrOrderItem."\" value=\"";
						
						// Wertefelder Produkte fuer Import vorbereiten
						foreach(array_keys($paramsOrderItems) as $param)
						{
							if (array_key_exists($param, $orderItem))
							{
								echo $orderItem[$param]."|";
							}
							else
							{
								echo "|";
							}
						}
						echo "\">";
						
						if ($amazon_checked && $autoimport && $bearbeitungsstatus == "neu" && ($opSet1['OrderStatus'] == "Shipped" || $opSet1['OrderStatus'] == "shipped"))
						{
							// Wertefelder Produkte fuer Autoimport vorbereiten
							foreach(array_keys($paramsOrderItems) as $param)
							{
								if (array_key_exists($param, $orderItem))
								{
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler][$param] = $orderItem[$param];
								}
								else
								{
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler][$param] = "";
								}
							}
							$zaehler++;
						}
					}
					echo "</td>";
				}
				echo 	"</tr>";
			}
			echo "</table>";
		}
	}
	
	if (isset($_POST["import"]) || $autoimport)
	{
		// Zum Import ausgewaehlte Datensaetze zusammenstellen	
		if (isset($_POST["importauswahl"])) {
			$importauswahl = $_POST['importauswahl'];
		}
		
		$bestellungen = array();
		
		foreach ($importauswahl as $lfdNr => $importItem)
		{
			// Bestellungsdaten fuer Import wieder einlesen
			foreach(array_keys($paramsOrders) as $param)
			{
				if (isset($_POST[$param."|".$importItem])) { $bestellungen[$importItem][$param] = $_POST[$param."|".$importItem]; }
			}
	
			//Artikel pro Bestellung
			for ($zaehler = 0; $zaehler <= intval($bestellungen[$importItem]['NumberOfItems']); $zaehler++)
			{
				if (isset($_POST["AmazonOrderIdProducts|".$importItem."|".$zaehler]))
				{
					$produktdaten = explode("|", $_POST["AmazonOrderIdProducts|".$importItem."|".$zaehler]);
					
					// Wertefelder Produkte fuer Import wieder einlesen
					foreach(array_keys($paramsOrderItems) as $arraynummer => $param)
					{
						$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler][$param] = $produktdaten[$arraynummer];
					}
				}
			}
		}
		echo "<br> Starte Import!<br><br>";
		
		if ($autoimport)
		{
			$bestellungen = $bestellungen_autoimport;
		}
		
		$bestellungszahl = count($bestellungen);

//		echo '<pre>';
//		print_r($bestellungen);
//		echo '</pre>';
			
		if ($bestellungszahl)
		{
			echo "Es liegen $bestellungszahl Bestellungen zum Import vor.<br>";
	
			// Importfunktion aufrufen
			foreach (array_values($bestellungen) as $bestellung)
			{
				// Wenn FulfillmentCenter nicht vorhanden, Warnemail schreiben, ggf. Import der Bestellung abbrechen
				$fulfillmentcentererror = false;
				$fulfillmentCenterIdData = checkFulfillmentCenterId($bestellung['fulfillment-center-id']);
				if (!$fulfillmentCenterIdData)
				{
					$fulfillmentcentererror = true;
					$empfaenger = $standardwarnemailrecipient;
					$betreff = "FulfillmentCenter nicht vorhanden";
					if ($importOhneVerandzentrum == "false")
					{
						$betreff .= ", die betroffene Bestellung wurde nicht importiert!";
					}
					$nachricht = "Das FulfillmentCenter mit der ID >>".$bestellung['fulfillment-center-id']."<< ist in den Einstellungen von Multishop nicht definiert\r\n" . 
								 "Betroffene Bestellung:" . "\r\n" .
								 "Verkaufskanal    : " . $bestellung['SalesChannel'] . "\r\n" . 
								 "Bestellungnummer : " . $bestellung['AmazonOrderId'] . "\r\n";
					$header =	"From: ".$standardwarnemailsender."\r\n" .
								"X-Mailer: PHP/" . phpversion();
					mail($empfaenger, $betreff, $nachricht, $header);					
				}
				
				if (!$fulfillmentcentererror || ($fulfillmentcentererror && $importOhneVerandzentrum == "true"))
				{
					$kundennummern = check_update_Kundendaten($bestellung);
					$nummernarray = explode("|", $kundennummern);
					if ($nummernarray[0] > 0)
					{
						erstelle_Auftrag($bestellung, $nummernarray[0], $nummernarray[1], $ERPusrID);
					}
					
					// Checke auf falsche Preise und schicke Warnemail
					If ($pricewarning == "true")
					{
					 	// Preiswarnungen in Array umwandeln
						$pricewarningsarray = array();
						foreach (explode("\n", $pricewarninglist) as $einzelpricewarning)
						{
							$zerlegtepricewarning = explode("|", $einzelpricewarning);
							if(count($zerlegtepricewarning) == 2)
							{
								$pricewarningsarray[trim($zerlegtepricewarning[0])] = trim($zerlegtepricewarning[1]);
							}
						}
						foreach ($bestellung['AmazonOrderIdProducts'] as $einzelprodukt)
						{
							foreach ($pricewarningsarray as $skuvalue => $pricevalue)
							{
								if(stripos($einzelprodukt['SellerSKU'], $skuvalue) !== false)
								{
									if (floatval($einzelprodukt['ItemPrice']) - floatval($einzelprodukt['PromotionDiscount']) < floatval($pricevalue))
									{
										// Warnemail schreiben!!!
										$empfaenger = $pricewarningemail;
										$betreff = "PREISWARNUNG PRODUKT: Ein Artikel wurde stark verbilligt verkauft";
										$nachricht = "Es gibt eine Rabattwarnung für folgende Bestellung\r\n" . 
													 "Verkaufskanal    : " . $bestellung['SalesChannel'] . "\r\n" . 
													 "Bestellungnummer : " . $bestellung['AmazonOrderId'] . "\r\n" . 
													 "Artikel          : " . $einzelprodukt['SellerSKU'] . "\r\n" .
													 "Preis            : " . $einzelprodukt['ItemPrice'] . "\r\n" .
													 "Rabatt           : " . $einzelprodukt['PromotionDiscount'] . "\r\n";
										$header =	"From: ".$pricewarningemailsender."\r\n" .
													"X-Mailer: PHP/" . phpversion();
										mail($empfaenger, $betreff, $nachricht, $header);
									}
									break;
								}
							}
						}
					}
				}
			}
		}
		else
		{
			echo "Keine Bestellungen ausgewaehlt/ es liegen keine Bestellungen vor!<br>";
		}
	}	
	
	if (isset($_POST["sellings"]))
	{
		echo "<br><br><table border=\"1\">"
				."<tr>"
					."<th>Saleschannel</th>"
					."<th>Produkt</th>"
					."<th>Menge</th>"
					."<th>Returns</th>"

				."</tr>";
		foreach (getSellingInfo($bestellungvom, $bestellungbis) as $eintrag)
		{
			echo "<tr>"
					."<td>".$eintrag[0]."</td>"
					."<td>".$eintrag[1]."</td>"
					."<td>".$eintrag[2]."</td>"
					."<td>".$eintrag[3]."</td>"
				."</tr>";
		}
	}
	
	echo "</form>";
	echo "</body>";
	echo "</html>";
}
?>
