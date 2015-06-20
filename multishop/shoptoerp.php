<?php
if (!isset($_SERVER['PHP_AUTH_USER']))
{
	Header("WWW-Authenticate: Basic realm=\"Configurations-Editor\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "Sie m&uuml;ssen sich autentifizieren\n";
	exit;
}
else
{
	require "conf.php";
	
	function cmpversand($a, $b)
	{
		return strcmp($a['LastUpdateDate'], $b['LastUpdateDate']);
	}
	function cmpbestell($a, $b)
	{
		return strcmp($a['PurchaseDate'], $b['PurchaseDate']);
	}

	if ($_SERVER['PHP_AUTH_USER']<>$ERPftpuser || $_SERVER['PHP_AUTH_PW']<>$ERPftppwd)
	{
		Header("WWW-Authenticate: Basic realm=\"My Realm\"");
		Header("HTTP/1.0 401 Unauthorized");
		echo "Sie m&uuml;ssen sich autentifizieren\n";
		exit;
	}
	
	require "erpfunctions.php";
	require "amazonfunctions.php";
	require "ebayfunctions.php";
	require "joomlafunctions.php";
	require "rakutenfunctions.php";
	
	// Variablen definieren: wieviele Tage in Vergangenheit gezeigt werden sollen:
	$daysBeforeFrom	= "5";
	
	if (isset($_POST["ansicht"]) &&  $_POST["ansicht"] == "detailansicht") {
		$listenansicht_checked = "";
		$detailansicht_checked = "checked=\"checked\"";
	} else {
		$listenansicht_checked = "checked=\"checked\"";
		$detailansicht_checked = "";
	}
	if (isset($_POST["erledigtesanzeigen"])) {
		$erledigtesanzeigen = "checked=\"checked\"";
	} else {
		$erledigtesanzeigen = "";
	}
	if (isset($_POST["suchdatum"]) &&  $_POST["suchdatum"] == "bestelldatum") {
		$versanddatum_checked = "";
		$bestelldatum_checked = "checked=\"checked\"";
	} else {
		$versanddatum_checked = "checked=\"checked\"";
		$bestelldatum_checked = "";
	}
	if (isset($_POST["bestellungvom"])) {
		$date_from = explode("-", $_POST["bestellungvom"]);
		$bestellungvom = strtoupper(gmdate("d-M-Y", mktime(12, 0, 0, $date_from[1], $date_from[0], $date_from[2])));
	} else {
		$bestellungvom = strtoupper(gmdate("d-M-Y", time()-86400*$daysBeforeFrom));
	}
	if (isset($_POST["bestellungbis"])) {
		$date_bis = explode("-", $_POST["bestellungbis"]);
		$bestellungbis = strtoupper(gmdate("d-M-Y", mktime(12, 0, 0, $date_bis[1], $date_bis[0], $date_bis[2])));
	} else {
		$bestellungbis = strtoupper(gmdate("d-M-Y", time()-120));
	}
	if (isset($_POST["fulfillmentchannel"]) &&  $_POST["fulfillmentchannel"] == "haendler") {
		$amazon_checked = "";
		$haendler_checked = "checked=\"checked\"";
	} else {
		$amazon_checked = "checked=\"checked\"";
		$haendler_checked = "";
	}
	if (isset($_POST["filter"])) {
		if ($_POST["filter"] == "Alle") {
			$Alle_checked = "checked=\"checked\"";
		}
		if ($_POST["filter"] == "Amazon") {
			$Amazon_checked = "checked=\"checked\"";
		}
		if ($_POST["filter"] == "Ebay") {
			$Ebay_checked = "checked=\"checked\"";
		}
		if ($_POST["filter"] == "Joomla") {
			$Joomla_checked = "checked=\"checked\"";
		}
		if ($_POST["filter"] == "Rakuten") {
			$Rakuten_checked = "checked=\"checked\"";
		}
	} else {
		$Alle_checked = "checked=\"checked\"";
	}
	if (isset($_POST["versandstatus"])) {
		$versandstatus = $_POST['versandstatus'];
		if (in_array("shipped", $versandstatus)) { $versandstatus_shipped = "checked=\"checked\""; } else { $versandstatus_shipped = ""; }
		if (in_array("pending", $versandstatus)) { $versandstatus_pending = "checked=\"checked\""; } else { $versandstatus_pending = ""; }
		if (in_array("partiallyunshipped", $versandstatus) || $_POST["fulfillmentchannel"] == "haendler") { $versandstatus_partiallyunshipped = "checked=\"checked\""; } else { $versandstatus_partiallyunshipped = ""; }
		if (in_array("canceled", $versandstatus)) { $versandstatus_canceled = "checked=\"checked\""; } else { $versandstatus_canceled = ""; }
		if (in_array("unfulfillable", $versandstatus)) { $versandstatus_unfulfillable = "checked=\"checked\""; } else { $versandstatus_unfulfillable = ""; }
	}
	else
	{
		$versandstatus_shipped = "checked=\"checked\"";
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
	echo "	<script type=\"text/javascript\" src=\"calendarDateInput.js\">";
	echo "		/***********************************************";
	echo "		* Jason's Date Input Calendar- By Jason Moon http://calendar.moonscript.com/dateinput.cfm";
	echo "		* Script featured on and available at http://www.dynamicdrive.com";
	echo "		* Keep this notice intact for use.";
	echo "		***********************************************/";
	echo "	</script>";
	echo "</head>";
	echo "<body>";	
	echo "<form name=\"gesamtformular\" action=\"shoptoerp.php\" method=\"post\">";
	echo	"<table style=\"background-color:#cccccc\">"
				."<tr><p>"
					."<td>Listenansicht</td>"
					."<td><input type=\"radio\" name=\"ansicht\" value=\"listenansicht\" ".$listenansicht_checked."></td>"
					."<td>Detailansicht</td>"
					."<td><input type=\"radio\" name=\"ansicht\" value=\"detailansicht\" ".$detailansicht_checked."></td>"
				."</p></tr>"
				."<tr>"
					."<td>Erledigtes anzeigen</td>"
					."<td><input type=\"checkbox\" name=\"erledigtesanzeigen\" value=\"erledigtesanzeigen\" ".$erledigtesanzeigen."></td>"
					."<td></td>"
					."<td></td>"
				."</tr>"
				."<tr><p>"
					."<td>Versanddatum</td>"
					."<td><input type=\"radio\" name=\"suchdatum\" value=\"versanddatum\" ".$versanddatum_checked."></td>"
					."<td>Bestelldatum</td>"
					."<td><input type=\"radio\" name=\"suchdatum\" value=\"bestelldatum\" ".$bestelldatum_checked."></td>"
				."</p></tr>"
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
				."<tr>"
					."<td><input type=\"checkbox\" name=\"versandstatus[]\" value=\"shipped\" ".$versandstatus_shipped.">Shipped</td>"
					."<td><input type=\"checkbox\" name=\"versandstatus[]\" value=\"pending\" ".$versandstatus_pending.">Pending</td>"
					."<td><input type=\"checkbox\" name=\"versandstatus[]\" value=\"partiallyunshipped\" ".$versandstatus_partiallyunshipped.">Partially Shipped / Unshipped</td>"
					."<td><input type=\"checkbox\" name=\"versandstatus[]\" value=\"canceled\" ".$versandstatus_canceled.">Canceled&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" name=\"versandstatus[]\" value=\"unfulfillable\" ".$versandstatus_unfulfillable.">Unfulfillable</td>"
				."</tr>"
			."</table>";
	echo 	"<br><input type=\"submit\" name=\"bestellungen\" value=\"Bestellungen anzeigen\">";
	echo 	"&nbsp;&nbsp;<input type=\"submit\" name=\"orderreports\" value=\"Adressen ueber Reports abgleichen (nur Amazon)\">";
	echo 	"&nbsp;&nbsp;<input type=\"submit\" name=\"sellings\" value=\"Produktverkaufstabelle\">";
	echo 	"&nbsp;&nbsp;<input type=\"submit\" name=\"sellingscsv\" value=\"Verkaufstabelle als .csv\"><br>";
	
	if (isset($_POST["bestellungen"]) && isset($_POST["bestellungvom"]))
	{
		if (isset($_POST["bestellungbis"]))
		{
			$output = array();
			if ($Amazonaktiviert == "checked" && ($_POST["fulfillmentchannel"] == "amazon" || $_POST["filter"] == "Alle" || $_POST["filter"] == "Amazon"))
			{
 				$amazonresult = getAmazonOrders($_POST["fulfillmentchannel"], $_POST["versandstatus"], $_POST["suchdatum"], $_POST["bestellungvom"], $_POST["bestellungbis"], "EU");
 				if(count($amazonresult) > 0)
 				{
 					$output = array_merge($output, $amazonresult);
 				}
			}
			if ($Amazonaktiviert_COM == "checked" && ($_POST["fulfillmentchannel"] == "amazon" || $_POST["filter"] == "Alle" || $_POST["filter"] == "Amazon"))
			{
 				$amazonresult = getAmazonOrders($_POST["fulfillmentchannel"], $_POST["versandstatus"], $_POST["suchdatum"], $_POST["bestellungvom"], $_POST["bestellungbis"], "COM");
 				if(count($amazonresult) > 0)
 				{
 					$output = array_merge($output, $amazonresult);
 				}
			}			
			if ($eBayaktiviert == "checked" && ($_POST["filter"] == "Alle" || $_POST["filter"] == "Ebay"))
			{
				$ebayresult = getEbayOrders($_POST["fulfillmentchannel"], $_POST["bestellungvom"], $_POST["bestellungbis"]);
				if(count($ebayresult) > 0)
				{
					$output = array_merge($output, $ebayresult);
				}
			}
			if ($Joomlaaktiviert == "checked" && ($_POST["filter"] == "Alle" || $_POST["filter"] == "Joomla"))
			{
				$joomlaresult = getJoomlaOrders($_POST["fulfillmentchannel"], $_POST["bestellungvom"], $_POST["bestellungbis"]);
				if(count($joomlaresult) > 0)
				{
					$output = array_merge($output, $joomlaresult);
				}
			}
			if ($Rakutenaktiviert == "checked" && ($_POST["filter"] == "Alle" || $_POST["filter"] == "Rakuten"))
			{
				$Rakutenresult = getRakutenOrders($_POST["fulfillmentchannel"], $_POST["bestellungvom"], $_POST["bestellungbis"]);
				if(count($Rakutenresult) > 0)
				{
					$output = array_merge($output, $Rakutenresult);
				}
			}
								
			// output sortieren
			if ($suchdatum == "versanddatum")
			{
				usort($output, "cmpversand");
			}
			else
			{
				usort($output, "cmpbestell");
			}
		}
		else
		{
			$output = array();
			if ($Amazonaktiviert == "checked")
			{
				$amazonresult = getAmazonOrders($_POST["fulfillmentchannel"], $_POST["versandstatus"], $_POST["suchdatum"], $_POST["bestellungvom"], "", "EU");
				if(count($amazonresult) > 0)
				{
					$output = array_merge($output, $amazonresult);
				}
			}
			if ($Amazonaktiviert_COM == "checked")
			{
				$amazonresult = getAmazonOrders($_POST["fulfillmentchannel"], $_POST["versandstatus"], $_POST["suchdatum"], $_POST["bestellungvom"], "", "COM");
				if(count($amazonresult) > 0)
				{
					$output = array_merge($output, $amazonresult);
				}
			}
			if ($eBayaktiviert == "checked")
			{
				$ebayresult = getEbayOrders($_POST["fulfillmentchannel"], $_POST["bestellungvom"], "");
				if(count($ebayresult) > 0)
				{
					$output = array_merge($output, $ebayresult);
				}
			}
			if ($Joomlaaktiviert == "checked")
			{
				$joomlaresult = getJoomlaOrders($_POST["fulfillmentchannel"], $_POST["bestellungvom"], "");
				if(count($joomlaresult) > 0)
				{
					$output = array_merge($output, $joomlaresult);
				}
			}
			if ($Rakutenaktiviert == "checked")
			{
				$Rakutenresult = getRakutenOrders($_POST["fulfillmentchannel"], $_POST["bestellungvom"], "");
				if(count($Rakutenresult) > 0)
				{
					$output = array_merge($output, $Rakutenresult);
				}
			}

			// output sortieren
			if ($suchdatum == "versanddatum")
			{
				usort($output, "cmpversand");
			}
			else
			{
				usort($output, "cmpbestell");
			}
		}
	
		echo "<br>Bestellungen:<br>";
		
		// wenn Fehler, diese ausgeben, sonst R�ckgabe in Tabelle anzeigen:
		if (array_key_exists('error', $output) && $output['error'])
		{
			foreach($output['error'] as $oeSet)
			{
		  		echo $oeSet;
			}
		}
		else
		{
			if (isset($_POST["ansicht"]) &&  $_POST["ansicht"] == "detailansicht")
			{
				echo	"<table border=\"1\">"
					 		."<tr>"
			 					."<th>Bestellnummer</th>"
			 					."<th>Details</th>"
			 				."</tr>";
		 	}
		 	else
		 	{
			 	echo	"<table border=\"1\">"
					 		."<tr>"
					 			."<th>Nr.</th>"
					 			."<th>Importieren</th>"
			 					."<th>Bestellnummer</th>"
			 					."<th>Marktpl. (Ziel) - Sprache</th>"
			 					."<th>Versanddatum (Bestelldatum)</th>"
			 					."<th>Name</th>"
			 					."<th>Status (x of y done)</th>"
			 					."<th>Gesamtbetrag</th>"
			 					."<th>Artikel</th>"
			 				."</tr>";
		 	}
		 	// Ersatzsprache in Array umwandeln
			$replaceLanguage = array();
			foreach (split("\n", $ersatzSprache) as $einzelSprache)
			{
				$zerlegteEinzelsprache = split("\|", $einzelSprache);
				if(count($zerlegteEinzelsprache) == 2)
				{
					$replaceLanguage[trim($zerlegteEinzelsprache[0])] = trim($zerlegteEinzelsprache[1]);
				}
			}
			foreach($output as $lfdNr => $opSet1)
			{
				$languagetemp = $standardsprache;
				$bearbeitungsstatus = $opSet1['bearbeitungsstatus'];
				$show_it = true;
				if (!isset($_POST["erledigtesanzeigen"]) && $bearbeitungsstatus == "email")
				{
					$show_it = false;
				}
				if ($show_it)
				{
					if (isset($_POST["ansicht"]) &&  $_POST["ansicht"] == "detailansicht")
					{
						echo 	"<tr valign=\"top\">"
									."<td>".$opSet1['AmazonOrderId']."</td>"
									."<td>"
										.$opSet1['PurchaseDate']." - Last Update ".$opSet1['LastUpdateDate']."<br>"
										.$opSet1['SalesChannel']." - ".$opSet1['MarketplaceId']."<br>"
										.$opSet1['OrderType']." - ".$opSet1['OrderStatus']." - ".$opSet1['FulfillmentChannel']." - ".$opSet1['AmazonOrderId']." - ".$opSet1['SellerOrderId']."<br>"
										.$opSet1['ShipmentServiceLevelCategory']." - ".$opSet1['ShipServiceLevel']."<br>"
										.$opSet1['Amount']." ".$opSet1['CurrencyCode']." - ".$opSet1['PaymentMethod']."<br>"
										.$opSet1['NumberOfItemsShipped']." (Unshipped ".$opSet1['NumberOfItemsUnshipped'].")<br>"
										.$opSet1['BuyerName']."<br>"
										.$opSet1['Name']."<br>"
										.$opSet1['AddressLine1']."<br>"
										.$opSet1['AddressLine2']."<br>"
										.$opSet1['CountryCode']."-".$opSet1['PostalCode']." ".$opSet1['City']."<br>"
										.$opSet1['StateOrRegion']."<br>" 
										.$opSet1['BuyerEmail']."<br>"
										.$opSet1['Phone']."<br>"
									."</td>"
								."</tr>";
					
					}
					else
					{
						// Sprache zuordnen
						if(trim($opSet1['MarketplaceId']) == trim("Amazon"))
						{
							if (array_key_exists($opSet1['SalesChannel'], $replaceLanguage))
							{
								$languagetemp = $replaceLanguage[$opSet1['SalesChannel']];
							}
						}
						else
						{
							if (array_key_exists($opSet1['Language'], $replaceLanguage))
							{
								$languagetemp = $replaceLanguage[$opSet1['Language']];
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
												
												echo "<input type=\"hidden\" name=\"AmazonOrderId"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['AmazonOrderId']."\">";
												echo "<input type=\"hidden\" name=\"SellerOrderId"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['SellerOrderId']."\">";
												echo "<input type=\"hidden\" name=\"PurchaseDate"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['PurchaseDate']."\">";
												echo "<input type=\"hidden\" name=\"LastUpdateDate"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['LastUpdateDate']."\">";
												echo "<input type=\"hidden\" name=\"SalesChannel"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['SalesChannel']."\">";
												echo "<input type=\"hidden\" name=\"MarketplaceId"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['MarketplaceId']."\">";
												echo "<input type=\"hidden\" name=\"OrderType"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['OrderType']."\">";
												echo "<input type=\"hidden\" name=\"OrderStatus"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['OrderStatus']."\">";
												echo "<input type=\"hidden\" name=\"FulfillmentChannel"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['FulfillmentChannel']."\">";
												echo "<input type=\"hidden\" name=\"ShipmentServiceLevelCategory"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['ShipmentServiceLevelCategory']."\">";
												echo "<input type=\"hidden\" name=\"ShipServiceLevel"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['ShipServiceLevel']."\">";
												echo "<input type=\"hidden\" name=\"Amount"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['Amount']."\">";
												echo "<input type=\"hidden\" name=\"CurrencyCode"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['CurrencyCode']."\">";
												echo "<input type=\"hidden\" name=\"PaymentMethod"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['PaymentMethod']."\">";
												echo "<input type=\"hidden\" name=\"NumberOfItemsShipped"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['NumberOfItemsShipped']."\">";
												echo "<input type=\"hidden\" name=\"NumberOfItemsUnshipped"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['NumberOfItemsUnshipped']."\">";
												echo "<input type=\"hidden\" name=\"BuyerName"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['BuyerName']."\">";
												echo "<input type=\"hidden\" name=\"Title"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['Title']."\">";
												echo "<input type=\"hidden\" name=\"Name"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['Name']."\">";
												echo "<input type=\"hidden\" name=\"AddressLine1"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['AddressLine1']."\">";
												echo "<input type=\"hidden\" name=\"AddressLine2"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['AddressLine2']."\">";
												echo "<input type=\"hidden\" name=\"PostalCode"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['PostalCode']."\">";
												echo "<input type=\"hidden\" name=\"City"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['City']."\">";
												echo "<input type=\"hidden\" name=\"CountryCode"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['CountryCode']."\">";
												echo "<input type=\"hidden\" name=\"StateOrRegion"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['StateOrRegion']."\">";
												echo "<input type=\"hidden\" name=\"recipient-title"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['recipient-title']."\">";
												echo "<input type=\"hidden\" name=\"recipient-name"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['recipient-name']."\">";
												echo "<input type=\"hidden\" name=\"ship-address-1"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['ship-address-1']."\">";
												echo "<input type=\"hidden\" name=\"ship-address-2"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['ship-address-2']."\">";
												echo "<input type=\"hidden\" name=\"ship-address-3"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['ship-address-3']."\">";
												echo "<input type=\"hidden\" name=\"ship-postal-code"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['ship-postal-code']."\">";
												echo "<input type=\"hidden\" name=\"ship-city"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['ship-city']."\">";
												echo "<input type=\"hidden\" name=\"ship-state"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['ship-state']."\">";
												echo "<input type=\"hidden\" name=\"ship-country"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['ship-country']."\">";
												echo "<input type=\"hidden\" name=\"BuyerEmail"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['BuyerEmail']."\">";
												echo "<input type=\"hidden\" name=\"Phone"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['Phone']."\">";
												echo "<input type=\"hidden\" name=\"OrderComment"."|".$opSet1['AmazonOrderId']."\" value=\"".$opSet1['OrderComment']."\">";
												echo "<input type=\"hidden\" name=\"Language"."|".$opSet1['AmazonOrderId']."\" value=\"".$languagetemp."\">";
												echo "</td>";
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
									$date1 = new DateTime($opSet1['LastUpdateDate']);
									$date2 = new DateTime($opSet1['PurchaseDate']);
									echo "<td>".$opSet1['AmazonOrderId']."</td>"
										."<td>".$opSet1['SalesChannel']." (".$opSet1['CountryCode'].") - ".$languagetemp." </td>"
										."<td>".$date1->format('Y-m-d H:i')." (".$date2->format('Y-m-d H:i').")</td>";
									$customerstatus = checkCustomer($opSet1['BuyerEmail'], $opSet1['BuyerName']);
									if ($customerstatus == "neu")
									{
										echo "<td>".$opSet1['BuyerName']." (Neukunde)</td>";
									}
									elseif ($customerstatus == "-")
									{
										echo "<td>".$opSet1['BuyerName']."-</td>";
									}
									elseif ($customerstatus == "vorhanden")
									{
										echo "<td>".$opSet1['BuyerName']." (Altkunde)</td>";
									}
									else
									{
										echo "<td>".$customerstatus."</td>";
									}
						echo		"<td>".$opSet1['OrderStatus']." (".$opSet1['NumberOfItemsShipped']." of ".strval(intval($opSet1['NumberOfItemsShipped'])+intval($opSet1['NumberOfItemsUnshipped'])).")</td>";
						echo		"<td>".$opSet1['Amount']." ".$opSet1['CurrencyCode']."</td>";
									
						$bearbeitungsstatus = checkAmazonOrderId($opSet1['AmazonOrderId']);
						if ($bearbeitungsstatus == "neu")
						{
							if (array_key_exists('error', $opSet1) && $opSet1['error'])
							{
								foreach($opSet1['error'] as $oeSet)
								{
									echo "<td>".$oeSet."</td>";
								}
							}
							else
							{
								$searchSKU = array();
								$replaceSKU = array();
								
								foreach (split("\n", $ersatzSKU) as $einzelSKU)
								{
									$zerlegteEinzelSKU = split("\|", $einzelSKU);
									if(count($zerlegteEinzelSKU) == 2)
									{
										$searchSKU[] = trim($zerlegteEinzelSKU[0]);
										$replaceSKU[] = trim($zerlegteEinzelSKU[1]);
									}
								}
								
								echo "<td>";
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
									if (isset($orderItem['ShippingDiscount']) && $orderItem['ShippingDiscount'] > 0.0)
									{
										$shippingdiscount_text = " ShippingDiscount ".$orderItem['ShippingDiscount'];
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
										echo $orderItem['SellerSKU']." (".str_replace($searchSKU, $replaceSKU, $orderItem['SellerSKU']).") - Shipped ".$orderItem['QuantityShipped']." of ".$orderItem['QuantityOrdered']." Price ".$orderItem['ItemPrice'].$promotiondiscount_text.$shipping_text.$shippingdiscount_text.$giftwrap_text."<br>";
									}
									else
									{
										echo $orderItem['SellerSKU']." - Shipped ".$orderItem['QuantityShipped']." of ".$orderItem['QuantityOrdered']." Price ".$orderItem['ItemPrice'].$promotiondiscount_text.$shipping_text.$shippingdiscount_text.$giftwrap_text."<br>";
									}
									
									echo "<input type=\"hidden\" name=\"AmazonOrderIdProducts"."|".$opSet1['AmazonOrderId']."|".$lfdNrOrderItem."\" value=\""
											.$orderItem['OrderItemId']."|"
											.str_replace($searchSKU, $replaceSKU, $orderItem['SellerSKU'])."|"
											.$orderItem['ASIN']."|"
											.$orderItem['ItemPrice']."|"
											.$orderItem['ItemTax']."|"
											.$orderItem['PromotionDiscount']."|"
											.$orderItem['ShippingPrice']."|"
											.$orderItem['ShippingTax']."|"
											.$orderItem['ShippingDiscount']."|"
											.$orderItem['GiftWrapPrice']."|"
											.$orderItem['GiftWrapTax']."|"
											.$orderItem['QuantityOrdered']."|"
											.$orderItem['QuantityShipped']."|"
											.$orderItem['Title']
											."\">";
								}
								echo "</td>";
							}
						}
						else
						{
							echo "<td>Keine Abfrage, Daten bereits importiert!</td>";
						}
					}
					echo 	"</tr>";
		
					if (isset($_POST["ansicht"]) &&  $_POST["ansicht"] == "detailansicht")
					{				
						if (array_key_exists('error', $opSet1) && $opSet1['error'])
						{
							foreach($opSet1['error'] as $oeSet)
							{
								echo 	"<tr valign=\"top\">"
										."<td></td>"
										."<td>".$oeSet."</td>"
										."</tr>";
							}
						}
						else
						{
							foreach($opSet1['orderItemsListOutput'] as $lfdNrOrderItem => $orderItem)
							{
								echo 	"<tr valign=\"top\">"
										."<td>  ".$opSet1['AmazonOrderId']." -> ".$lfdNrOrderItem."</td>"
										."<td>".$orderItem['SellerSKU']." / ".$orderItem['ASIN']." - Shipped ".$orderItem['QuantityShipped']." of ".$orderItem['QuantityOrdered']." Price ".$orderItem['ItemPrice']." PromotionDiscount ".$orderItem['PromotionDiscount']." Shipping ".$orderItem['ShippingPrice']." GiftWrap ".$orderItem['GiftWrapPrice']."<br>".$orderItem['Title']."</td>"
										."</tr>";
							}
						}
					}
				}
			}
			if (isset($_POST["ansicht"]) &&  $_POST["ansicht"] == "detailansicht")
			{
				echo "</table>";
			}
			else
			{
				echo "</table><br>";
				echo "<input type=\"submit\" name=\"import\" value=\"Ausgewaehltes importieren\">";
			}
		}
	}
	else if (isset($_POST["orderreports"]) && isset($_POST["bestellungvom"]))
	{
		if (isset($_POST["bestellungbis"]))
		{
			$output = array();
			if ($Amazonaktiviert == "checked" && ($_POST["fulfillmentchannel"] == "amazon" || $_POST["filter"] == "Alle" || $_POST["filter"] == "Amazon"))
			{
 				$amazonresult = getAmazonOrdersByReports($_POST["fulfillmentchannel"], $_POST["bestellungvom"], $_POST["bestellungbis"], "EU");
 				if(count($amazonresult) > 0)
 				{
 					$output = array_merge($output, $amazonresult);
 				}
			}
// 			if ($Amazonaktiviert_COM == "checked" && ($_POST["fulfillmentchannel"] == "amazon" || $_POST["filter"] == "Alle" || $_POST["filter"] == "Amazon"))
// 			{
//  				$amazonresult = getAmazonOrdersByReports($_POST["fulfillmentchannel"], $_POST["bestellungvom"], $_POST["bestellungbis"], "COM");
//  				if(count($amazonresult) > 0)
//  				{
//  					$output = array_merge($output, $amazonresult);
//  				}
// 			}			
		}
		else
		{
			$output = array();
			if ($Amazonaktiviert == "checked")
			{
				$amazonresult = getAmazonOrdersByReports($_POST["fulfillmentchannel"], $_POST["bestellungvom"], "", "EU");
				if(count($amazonresult) > 0)
				{
					$output = array_merge($output, $amazonresult);
				}
			}
// 			if ($Amazonaktiviert_COM == "checked")
// 			{
// 				$amazonresult = getAmazonOrdersByReports($_POST["fulfillmentchannel"], $_POST["bestellungvom"], "", "COM");
// 				if(count($amazonresult) > 0)
// 				{
// 					$output = array_merge($output, $amazonresult);
// 				}
// 			}
		}
	
		echo "<br>Datensaetze zum Adressupdate: ".count($output)."<br>";
		
		// wenn Fehler, diese ausgeben, sonst R�ckgabe in Tabelle anzeigen:
		if (array_key_exists('error', $output) && $output['error'])
		{
			foreach($output['error'] as $oeSet)
			{
		  		echo $oeSet;
			}
		}
		else
		{
		 	echo	"<table border=\"1\">"
				 		."<tr>"
				 			."<th>Nr.</th>"
				 			."<th>Abgleichen</th>"
				 			."<th>KdNr</th>"
		 					."<th>Bestellnummer</th>"
		 					."<th>Marktplatz (Zielland)</th>"
		 					."<th>Versanddatum</th>"
		 					."<th>Rechnungsadresse</th>"
		 					."<th>Versandadresse</th>"
		 				."</tr>";
		 				
			foreach($output as $lfdNr => $einzelbestellung)
			{
				$adressabgleicherledigt = explode ("|", checkCustomerAdressabgleichErledigt($einzelbestellung));
				
				$show_it = true;
				if (!isset($_POST["erledigtesanzeigen"]) && $adressabgleicherledigt[0] == "ja")
				{
					$show_it = false;
				}
				if ($show_it)
				{
					echo 	"<tr valign=\"top\">";
					echo 		"<td>".$lfdNr."</td>";
					if ($adressabgleicherledigt[0] == "ja")
					{
						echo "<td>Adresse abgeglichen/ aktuell</td>";
						echo "<td>".$adressabgleicherledigt[1]."</td>";
					}
					elseif ($adressabgleicherledigt[0] == "nein")
					{
						if (array_key_exists('error', $einzelbestellung) && $einzelbestellung['error'])
						{
							echo "<td bgcolor=\"red\">Abfragefehler!</td>";
						}
						else
						{
							echo "<td>";
							echo "<input type=\"checkbox\" name=\"abgleichsauswahl[]\" value=\"".$einzelbestellung['AmazonOrderId']."\" checked=\"checked\">";
							
							echo "<input type=\"hidden\" name=\"AmazonOrderId"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['AmazonOrderId']."\">";
							echo "<input type=\"hidden\" name=\"SellerOrderId"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['SellerOrderId']."\">";
							echo "<input type=\"hidden\" name=\"PurchaseDate"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['PurchaseDate']."\">";
							echo "<input type=\"hidden\" name=\"SalesChannel"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['SalesChannel']."\">";
							echo "<input type=\"hidden\" name=\"FulfillmentChannel"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['FulfillmentChannel']."\">";
							echo "<input type=\"hidden\" name=\"BuyerName"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['BuyerName']."\">";
							echo "<input type=\"hidden\" name=\"Title"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['Title']."\">";
							echo "<input type=\"hidden\" name=\"Name"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['Name']."\">";
							echo "<input type=\"hidden\" name=\"AddressLine1"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['AddressLine1']."\">";
							echo "<input type=\"hidden\" name=\"AddressLine2"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['AddressLine2']."\">";
							echo "<input type=\"hidden\" name=\"PostalCode"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['PostalCode']."\">";
							echo "<input type=\"hidden\" name=\"City"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['City']."\">";
							echo "<input type=\"hidden\" name=\"CountryCode"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['CountryCode']."\">";
							echo "<input type=\"hidden\" name=\"StateOrRegion"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['StateOrRegion']."\">";
							echo "<input type=\"hidden\" name=\"BuyerEmail"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['BuyerEmail']."\">";
							echo "<input type=\"hidden\" name=\"Phone"."|".$einzelbestellung['AmazonOrderId']."\" value=\"".$einzelbestellung['Phone']."\">";
							echo "<input type=\"hidden\" name=\"recipient-title"."|".$$einzelbestellung['AmazonOrderId']."\" value=\"".$$einzelbestellung['recipient-title']."\">";
							echo "<input type=\"hidden\" name=\"recipient-name"."|".$$einzelbestellung['AmazonOrderId']."\" value=\"".$$einzelbestellung['recipient-name']."\">";
							echo "<input type=\"hidden\" name=\"ship-address-1"."|".$$einzelbestellung['AmazonOrderId']."\" value=\"".$$einzelbestellung['ship-address-1']."\">";
							echo "<input type=\"hidden\" name=\"ship-address-2"."|".$$einzelbestellung['AmazonOrderId']."\" value=\"".$$einzelbestellung['ship-address-2']."\">";
							echo "<input type=\"hidden\" name=\"ship-address-3"."|".$$einzelbestellung['AmazonOrderId']."\" value=\"".$$einzelbestellung['ship-address-3']."\">";
							echo "<input type=\"hidden\" name=\"ship-postal-code"."|".$$einzelbestellung['AmazonOrderId']."\" value=\"".$$einzelbestellung['ship-postal-code']."\">";
							echo "<input type=\"hidden\" name=\"ship-city"."|".$$einzelbestellung['AmazonOrderId']."\" value=\"".$$einzelbestellung['ship-city']."\">";
							echo "<input type=\"hidden\" name=\"ship-state"."|".$$einzelbestellung['AmazonOrderId']."\" value=\"".$$einzelbestellung['ship-state']."\">";
							echo "<input type=\"hidden\" name=\"ship-country"."|".$$einzelbestellung['AmazonOrderId']."\" value=\"".$$einzelbestellung['ship-country']."\">";
							echo "</td>";
							echo "<td>".$adressabgleicherledigt[1]."</td>";
						}
					}
					else 
					{
						echo "<td>".$adressabgleicherledigt[0]."</td><td></td>";
					}
					$date = new DateTime($einzelbestellung['LastUpdateDate']);
					echo "<td>".$einzelbestellung['AmazonOrderId']."</td>"
						."<td>".$einzelbestellung['SalesChannel']." (".$einzelbestellung['CountryCode'].")</td>"
						."<td>".$date->format('Y-m-d H:i')."</td>";
					echo "<td>".$einzelbestellung['Name'].", ".$einzelbestellung['AddressLine1'].", ".$einzelbestellung['AddressLine2'].", ".$einzelbestellung['PostalCode']." ".$einzelbestellung['City'].", ".$einzelbestellung['CountryCode']."</td>";
					echo "<td>".$einzelbestellung['recipient-name'].", ".$einzelbestellung['ship-address-1'].", ".$einzelbestellung['ship-address-2'].", ".$einzelbestellung['ship-postal-code']." ".$einzelbestellung['ship-city'].", ".$einzelbestellung['ship-country']."</td>";
					echo "</tr>";
				}
			}
			echo "</table><br>";
			echo "<input type=\"submit\" name=\"adressabgleich\" value=\"Ausgewaehlte Adressen abgleichen\">";
		}
	}	
	else if (isset($_POST["import"]))
	{
		// Zum Import ausgewaehlte Datensaetze zusammenstellen	
		if (isset($_POST["importauswahl"])) {
			$importauswahl = $_POST['importauswahl'];
		}
		
		$bestellungen = array();
		
		foreach ($importauswahl as $lfdNr => $importItem)
		{
			// Bestellungsdaten
			if (isset($_POST["AmazonOrderId|".$importItem])) { $bestellungen[$importItem]['AmazonOrderId'] = $_POST["AmazonOrderId|".$importItem]; }
			if (isset($_POST["SellerOrderId|".$importItem])) { $bestellungen[$importItem]['SellerOrderId'] = $_POST["SellerOrderId|".$importItem]; }
			if (isset($_POST["PurchaseDate|".$importItem])) { $bestellungen[$importItem]['PurchaseDate'] = $_POST["PurchaseDate|".$importItem]; }
			if (isset($_POST["LastUpdateDate|".$importItem])) { $bestellungen[$importItem]['LastUpdateDate'] = $_POST["LastUpdateDate|".$importItem]; }
			if (isset($_POST["SalesChannel|".$importItem])) { $bestellungen[$importItem]['SalesChannel'] = $_POST["SalesChannel|".$importItem]; }
			if (isset($_POST["MarketplaceId|".$importItem])) { $bestellungen[$importItem]['MarketplaceId'] = $_POST["MarketplaceId|".$importItem]; }
			if (isset($_POST["OrderType|".$importItem])) { $bestellungen[$importItem]['OrderType'] = $_POST["OrderType|".$importItem]; }
			if (isset($_POST["OrderStatus|".$importItem])) { $bestellungen[$importItem]['OrderStatus'] = $_POST["OrderStatus|".$importItem]; }
			if (isset($_POST["FulfillmentChannel|".$importItem])) { $bestellungen[$importItem]['FulfillmentChannel'] = $_POST["FulfillmentChannel|".$importItem]; }
			if (isset($_POST["ShipmentServiceLevelCategory|".$importItem])) { $bestellungen[$importItem]['ShipmentServiceLevelCategory'] = $_POST["ShipmentServiceLevelCategory|".$importItem]; }
			if (isset($_POST["ShipServiceLevel|".$importItem])) { $bestellungen[$importItem]['ShipServiceLevel'] = $_POST["ShipServiceLevel|".$importItem]; }
			if (isset($_POST["Amount|".$importItem])) { $bestellungen[$importItem]['Amount'] = $_POST["Amount|".$importItem]; }
			if (isset($_POST["CurrencyCode|".$importItem])) { $bestellungen[$importItem]['CurrencyCode'] = $_POST["CurrencyCode|".$importItem]; }
			if (isset($_POST["PaymentMethod|".$importItem])) { $bestellungen[$importItem]['PaymentMethod'] = $_POST["PaymentMethod|".$importItem]; }
			if (isset($_POST["NumberOfItemsShipped|".$importItem])) { $bestellungen[$importItem]['NumberOfItemsShipped'] = $_POST["NumberOfItemsShipped|".$importItem]; }
			if (isset($_POST["NumberOfItemsUnshipped|".$importItem])) { $bestellungen[$importItem]['NumberOfItemsUnshipped'] = $_POST["NumberOfItemsUnshipped|".$importItem]; }
			if (isset($_POST["BuyerName|".$importItem])) { $bestellungen[$importItem]['BuyerName'] = $_POST["BuyerName|".$importItem]; }
			if (isset($_POST["Title|".$importItem])) { $bestellungen[$importItem]['Title'] = $_POST["Title|".$importItem]; }
			if (isset($_POST["Name|".$importItem])) { $bestellungen[$importItem]['Name'] = $_POST["Name|".$importItem]; }
			if (isset($_POST["AddressLine1|".$importItem])) { $bestellungen[$importItem]['AddressLine1'] = $_POST["AddressLine1|".$importItem]; }
			if (isset($_POST["AddressLine2|".$importItem])) { $bestellungen[$importItem]['AddressLine2'] = $_POST["AddressLine2|".$importItem]; }
			if (isset($_POST["PostalCode|".$importItem])) { $bestellungen[$importItem]['PostalCode'] = $_POST["PostalCode|".$importItem]; }
			if (isset($_POST["City|".$importItem])) { $bestellungen[$importItem]['City'] = $_POST["City|".$importItem]; }
			if (isset($_POST["CountryCode|".$importItem])) { $bestellungen[$importItem]['CountryCode'] = $_POST["CountryCode|".$importItem]; }
			if (isset($_POST["StateOrRegion|".$importItem])) { $bestellungen[$importItem]['StateOrRegion'] = $_POST["StateOrRegion|".$importItem]; }
			if (isset($_POST["recipient-title|".$importItem])) { $bestellungen[$importItem]['recipient-title'] = $_POST["recipient-title|".$importItem]; }
			if (isset($_POST["recipient-name|".$importItem])) { $bestellungen[$importItem]['recipient-name'] = $_POST["recipient-name|".$importItem]; }
			if (isset($_POST["ship-address-1|".$importItem])) { $bestellungen[$importItem]['ship-address-1'] = $_POST["ship-address-1|".$importItem]; }
			if (isset($_POST["ship-address-2|".$importItem])) { $bestellungen[$importItem]['ship-address-2'] = $_POST["ship-address-2|".$importItem]; }
			if (isset($_POST["ship-address-3|".$importItem])) { $bestellungen[$importItem]['ship-address-3'] = $_POST["ship-address-3|".$importItem]; }
			if (isset($_POST["ship-postal-code|".$importItem])) { $bestellungen[$importItem]['ship-postal-code'] = $_POST["ship-postal-code|".$importItem]; }
			if (isset($_POST["ship-city|".$importItem])) { $bestellungen[$importItem]['ship-city'] = $_POST["ship-city|".$importItem]; }
			if (isset($_POST["ship-state|".$importItem])) { $bestellungen[$importItem]['ship-state'] = $_POST["ship-state|".$importItem]; }
			if (isset($_POST["ship-country|".$importItem])) { $bestellungen[$importItem]['ship-country'] = $_POST["ship-country|".$importItem]; }
			if (isset($_POST["BuyerEmail|".$importItem])) { $bestellungen[$importItem]['BuyerEmail'] = $_POST["BuyerEmail|".$importItem]; }			
			if (isset($_POST["Phone|".$importItem])) { $bestellungen[$importItem]['Phone'] = $_POST["Phone|".$importItem]; }
			if (isset($_POST["OrderComment|".$importItem])) { $bestellungen[$importItem]['OrderComment'] = $_POST["OrderComment|".$importItem]; }
			if (isset($_POST["Language|".$importItem])) { $bestellungen[$importItem]['Language'] = $_POST["Language|".$importItem]; }
	
			//Artikel pro Bestellung
			for ($zaehler = 0; $zaehler <= intval($bestellungen[$importItem]['NumberOfItemsShipped']) + intval($bestellungen[$importItem]['NumberOfItemsUnshipped']); $zaehler++)
			{
				if (isset($_POST["AmazonOrderIdProducts|".$importItem."|".$zaehler]))
				{
					$produktdaten = explode("|", $_POST["AmazonOrderIdProducts|".$importItem."|".$zaehler]);
					
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['OrderItemId'] = $produktdaten[0];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['SellerSKU'] = $produktdaten[1];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['ASIN'] = $produktdaten[2];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['ItemPrice'] = $produktdaten[3];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['ItemTax'] = $produktdaten[4];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['PromotionDiscount'] = $produktdaten[5];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['ShippingPrice'] = $produktdaten[6];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['ShippingTax'] = $produktdaten[7];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['ShippingDiscount'] = $produktdaten[8];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['GiftWrapPrice'] = $produktdaten[9];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['GiftWrapTax'] = $produktdaten[10];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['QuantityOrdered'] = $produktdaten[11];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['QuantityShipped'] = $produktdaten[12];
					$bestellungen[$importItem]['AmazonOrderIdProducts'][$zaehler]['Title'] = $produktdaten[13];
				}
			}
		}
		echo "<br> Starte Import!<br><br>";
		
		$bestellungszahl = count($bestellungen);
	
		if ($bestellungszahl)
		{
			echo "Es liegen $bestellungszahl Bestellungen zum Import vor.<br>";
	
			// Importfunktion aufrufen
			foreach (array_values($bestellungen) as $bestellung)
			{
				$kundennummern = check_update_Kundendaten($bestellung);
				$nummernarray = explode("|", $kundennummern);
				if ($nummernarray[0] > 0)
				{
					erstelle_Auftrag($bestellung, $nummernarray[0], $nummernarray[1], $ERPusrID);
				}
			}
		}
		else
		{
			echo "Keine Bestellungen ausgewaehlt/ es liegen keine Bestellungen vor!<br>";
		}
	}
	else if (isset($_POST["adressabgleich"]))
	{
		// Zum Adressabgleich ausgewaehlte Datensaetze zusammenstellen
		if (isset($_POST["abgleichsauswahl"])) {
			$adressabgleichsauswahl = $_POST['abgleichsauswahl'];
		}
		
		$abgleichsdatensaetze = array();
		
		foreach ($adressabgleichsauswahl as $lfdNr => $abgleichsitem)
		{
			// Bestellungsdaten
			if (isset($_POST["AmazonOrderId|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['AmazonOrderId'] = $_POST["AmazonOrderId|".$abgleichsitem]; }
			if (isset($_POST["SellerOrderId|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['SellerOrderId'] = $_POST["SellerOrderId|".$abgleichsitem]; }
			if (isset($_POST["PurchaseDate|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['PurchaseDate'] = $_POST["PurchaseDate|".$abgleichsitem]; }
			if (isset($_POST["SalesChannel|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['SalesChannel'] = $_POST["SalesChannel|".$abgleichsitem]; }
			if (isset($_POST["FulfillmentChannel|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['FulfillmentChannel'] = $_POST["FulfillmentChannel|".$abgleichsitem]; }
			if (isset($_POST["BuyerName|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['BuyerName'] = $_POST["BuyerName|".$abgleichsitem]; }
			if (isset($_POST["Title|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['Title'] = $_POST["Title|".$abgleichsitem]; }
			if (isset($_POST["Name|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['Name'] = $_POST["Name|".$abgleichsitem]; }
			if (isset($_POST["AddressLine1|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['AddressLine1'] = $_POST["AddressLine1|".$abgleichsitem]; }
			if (isset($_POST["AddressLine2|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['AddressLine2'] = $_POST["AddressLine2|".$abgleichsitem]; }
			if (isset($_POST["PostalCode|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['PostalCode'] = $_POST["PostalCode|".$abgleichsitem]; }
			if (isset($_POST["City|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['City'] = $_POST["City|".$abgleichsitem]; }
			if (isset($_POST["CountryCode|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['CountryCode'] = $_POST["CountryCode|".$abgleichsitem]; }
			if (isset($_POST["StateOrRegion|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['StateOrRegion'] = $_POST["StateOrRegion|".$abgleichsitem]; }
			if (isset($_POST["BuyerEmail|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['BuyerEmail'] = $_POST["BuyerEmail|".$abgleichsitem]; }			
			if (isset($_POST["Phone|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['Phone'] = $_POST["Phone|".$abgleichsitem]; }
			if (isset($_POST["recipient-title|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['recipient-title'] = $_POST["recipient-title|".$abgleichsitem]; }
			if (isset($_POST["recipient-name|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['recipient-name'] = $_POST["recipient-name|".$abgleichsitem]; }
			if (isset($_POST["ship-address-1|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['ship-address-1'] = $_POST["ship-address-1|".$abgleichsitem]; }
			if (isset($_POST["ship-address-2|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['ship-address-2'] = $_POST["ship-address-2|".$abgleichsitem]; }
			if (isset($_POST["ship-address-3|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['ship-address-3'] = $_POST["ship-address-3|".$abgleichsitem]; }
			if (isset($_POST["ship-postal-code|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['ship-postal-code'] = $_POST["ship-postal-code|".$abgleichsitem]; }
			if (isset($_POST["ship-city|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['ship-city'] = $_POST["ship-city|".$abgleichsitem]; }
			if (isset($_POST["ship-state|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['ship-state'] = $_POST["ship-state|".$abgleichsitem]; }
			if (isset($_POST["ship-country|".$abgleichsitem])) { $abgleichsdatensaetze[$abgleichsitem]['ship-country'] = $_POST["ship-country|".$abgleichsitem]; }
		}
		echo "<br> Starte Adressabgleich!<br><br>";
		
		$abgleichsdatensaetzezahl = count($abgleichsdatensaetze);
	
		if ($abgleichsdatensaetzezahl)
		{
			echo "Es liegen $abgleichsdatensaetzezahl Datensaetze zum Abgleich vor.<br>";
	
			// Abgleichsfunktion aufrufen
			foreach (array_values($abgleichsdatensaetze) as $abgleichsdatensatz)
			{
				$kundennummern = check_update_Rechnungsadresse($abgleichsdatensatz);
			}
		}
		else
		{
			echo "Keine Datensaetze ausgewaehlt/ es liegen keine Datensaetze vor!<br>";
		}
	}
	else if (isset($_POST["sellings"]))
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
		echo "</table>";
// 		echo "<br><br><table border=\"1\">"
// 				."<tr>"
// 					."<th>Produkt</th>"
// 					."<th>Saleschannel</th>"
// 					."<th>Laenderkuerzel</th>"
// 					."<th>Abteilung</th>"
// 					."<th>Zielland</th>"
// 					."<th>Region</th>"
// 					."<th>Menge</th>"
// 					."<th>Returns</th>"
// 				."</tr>";
// 		foreach (getSellingInfo($bestellungvom, $bestellungbis) as $eintrag)
// 		{
// 			echo "<tr>"
// 					."<td>".$eintrag[8]."</td>"
// 					."<td>".$eintrag[1]."</td>"
// 					."<td>".$eintrag[2]."</td>"
// 					."<td>".$eintrag[3]."</td>"
// 					."<td>".$eintrag[4]."</td>"
// 					."<td>".$eintrag[5]."</td>"
// 					."<td>".$eintrag[6]."</td>"
// 					."<td>".$eintrag[7]."</td>"
// 				."</tr>";
// 		}
// 		echo "</table>";		
	}
	
	echo "</form>";
	echo "</body>";
	echo "</html>";
}
?>
