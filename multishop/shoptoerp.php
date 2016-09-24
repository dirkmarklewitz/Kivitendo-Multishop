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
		return strcmp($a['LastUpdateDate'], $b['LastUpdateDate']);
	}
	function cmpbestell($a, $b)
	{
		return strcmp($a['PurchaseDate'], $b['PurchaseDate']);
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
	
	// Variablen definieren: wieviele Tage in Vergangenheit gezeigt werden sollen:
	$daysBeforeFrom	= "5";	
	if (isset($_POST["erledigtesanzeigen"])) {
		$erledigtesanzeigen = "checked=\"checked\"";
	} else {
		$erledigtesanzeigen = "";
	}
	if (isset($_POST["suchdatum"]) &&  $_POST["suchdatum"] == "bestelldatum") {
		$suchdatum = "bestelldatum";
		$versanddatum_checked = "";
		$bestelldatum_checked = "checked=\"checked\"";
	} else {
		$suchdatum = "versanddatum";
		$versanddatum_checked = "checked=\"checked\"";
		$bestelldatum_checked = "";
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
	if (isset($_POST["fulfillmentchannel"]) &&  $_POST["fulfillmentchannel"] == "haendler") {
		$fulfillmentchannel = "haendler";
		$amazon_checked = "";
		$haendler_checked = "checked=\"checked\"";
	} else {
		$fulfillmentchannel = "amazon";
		$amazon_checked = "checked=\"checked\"";
		$haendler_checked = "";
	}
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
	if (isset($_POST["versandstatus"])) {
		$versandstatus = $_POST['versandstatus'];
		if (in_array("shipped", $versandstatus)) { $versandstatus_shipped = "checked=\"checked\""; } else { $versandstatus_shipped = ""; }
		if (in_array("pending", $versandstatus)) { $versandstatus_pending = "checked=\"checked\""; } else { $versandstatus_pending = ""; }
		if (in_array("partiallyunshipped", $versandstatus) || $fulfillmentchannel == "haendler") { $versandstatus_partiallyunshipped = "checked=\"checked\""; } else { $versandstatus_partiallyunshipped = ""; }
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
	
	if (isset($_POST["bestellungen"]) || $autoimport)
	{
		$output = array();
		if ($Amazonaktiviert == "checked" && ($fulfillmentchannel == "amazon" || $filter == "Alle" || $filter == "Amazon"))
		{
				$amazonresult = getAmazonOrders($fulfillmentchannel, $_POST["versandstatus"], $suchdatum, $bestellungvom_DATA, $bestellungbis_DATA, "EU");
				if(count($amazonresult) > 0)
				{
					$output = array_merge($output, $amazonresult);
				}
		}
		if ($Amazonaktiviert_COM == "checked" && ($fulfillmentchannel == "amazon" || $filter == "Alle" || $filter == "Amazon"))
		{
				$amazonresult = getAmazonOrders($fulfillmentchannel, $_POST["versandstatus"], $suchdatum, $bestellungvom_DATA, $bestellungbis_DATA, "COM");
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
		if ($suchdatum == "versanddatum")
		{
			usort($output, "cmpversand");
		}
		else
		{
			usort($output, "cmpbestell");
		}
	
		echo "<br>Bestellungen:<br>";
		
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
		 	echo	"<table border=\"1\">"
				 		."<tr>"
				 			."<th>Nr.</th>"
				 			."<th>Importieren</th>"
				 			."<th>Porto (bei Import)</th>"
		 					."<th>Bestellnummer</th>"
		 					."<th>Marktpl. (Ziel) - Sprache</th>"
		 					."<th>Versanddatum (Bestelldatum)</th>"
		 					."<th>Name</th>"
		 					."<th>Status (x of y done)</th>"
		 					."<th>Gesamtbetrag</th>"
		 					."<th>Artikel</th>"
		 				."</tr>";
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
			
			$bestellungen_autoimport = array();			
			
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

											if ($amazon_checked && $autoimport)
											{
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderId'] = $opSet1['AmazonOrderId'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['SellerOrderId'] = $opSet1['SellerOrderId'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['PurchaseDate'] = $opSet1['PurchaseDate'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['LastUpdateDate'] = $opSet1['LastUpdateDate'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['SalesChannel'] = $opSet1['SalesChannel'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['MarketplaceId'] = $opSet1['MarketplaceId'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['OrderType'] = $opSet1['OrderType'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['OrderStatus'] = $opSet1['OrderStatus'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['FulfillmentChannel'] = $opSet1['FulfillmentChannel'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['ShipmentServiceLevelCategory'] = $opSet1['ShipmentServiceLevelCategory'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['ShipServiceLevel'] = $opSet1['ShipServiceLevel'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['Amount'] = $opSet1['Amount'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['CurrencyCode'] = $opSet1['CurrencyCode'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['PaymentMethod'] = $opSet1['PaymentMethod'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['NumberOfItemsShipped'] = $opSet1['NumberOfItemsShipped'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['NumberOfItemsUnshipped'] = $opSet1['NumberOfItemsUnshipped'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['BuyerName'] = $opSet1['BuyerName'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['Title'] = $opSet1['Title'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['Name'] = $opSet1['Name'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AddressLine1'] = $opSet1['AddressLine1'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AddressLine2'] = $opSet1['AddressLine2'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['PostalCode'] = $opSet1['PostalCode'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['City'] = $opSet1['City'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['CountryCode'] = $opSet1['CountryCode'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['StateOrRegion'] = $opSet1['StateOrRegion'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['recipient-title'] = $opSet1['recipient-title'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['recipient-name'] = $opSet1['recipient-name'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['ship-address-1'] = $opSet1['ship-address-1'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['ship-address-2'] = $opSet1['ship-address-2'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['ship-address-3'] = $opSet1['ship-address-3'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['ship-postal-code'] = $opSet1['ship-postal-code'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['ship-city'] = $opSet1['ship-city'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['ship-state'] = $opSet1['ship-state'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['ship-country'] = $opSet1['ship-country'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['BuyerEmail'] = $opSet1['BuyerEmail'];			
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['Phone'] = $opSet1['Phone'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['OrderComment'] = $opSet1['OrderComment'];
												$bestellungen_autoimport[$opSet1['AmazonOrderId']]['Language'] = $languagetemp;
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
										foreach($opSet1['orderItemsListOutput'] as $lfdNrOrderItem => $orderItem)
										{
											$export_details[$zaehler]['sku'] = rawurlencode($orderItem['SellerSKU']);
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
					if ($bearbeitungsstatus == "neu" || ($bearbeitungsstatus == "auftrag" && $fulfillmentchannel == "haendler"))
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
							// ----- Zusatzprodukte einfügen, bevor die SKU korrigiert werden, dann an $opSet1 anfügen -----
							$zusatzSKU = array();
							foreach (split("\n", $zusatzProdukt) as $einzelZusatzProdukt)
							{
								$zerlegtesEinzelZusatzProdukt = split("\|", $einzelZusatzProdukt);
								if(count($zerlegtesEinzelZusatzProdukt) == 3 || count($zerlegtesEinzelZusatzProdukt) == 4)
								{
									$zusatzSKU[$zerlegtesEinzelZusatzProdukt[0]]['Zusatzprodukt'] = trim($zerlegtesEinzelZusatzProdukt[1]);
									$zusatzSKU[$zerlegtesEinzelZusatzProdukt[0]]['Anzahl'] = trim($zerlegtesEinzelZusatzProdukt[2]);
								}
								if(count($zerlegtesEinzelZusatzProdukt) == 4)
								{
									$zusatzSKU[$zerlegtesEinzelZusatzProdukt[0]]['Bezeichnung'] = trim($zerlegtesEinzelZusatzProdukt[3]);
								}
							}
							$zuErgaenzendeProdukte = array();
							foreach($opSet1['orderItemsListOutput'] as $lfdNrOrderItem => $orderItem)
							{
								if (array_key_exists($orderItem['SellerSKU'], $zusatzSKU))
								{
									$newOrderItem = array();
									$newOrderItem['AmazonOrderId'] = $orderItem['AmazonOrderId'];
									$newOrderItem['OrderItemId'] = "Zusatzartikel";
									$newOrderItem['SellerSKU'] = $zusatzSKU[$orderItem['SellerSKU']]['Zusatzprodukt'];
									$newOrderItem['ASIN'] = "";
									$newOrderItem['ItemPrice'] = 0.00;
									$newOrderItem['ItemTax'] = 0.00;
									$newOrderItem['PromotionDiscount'] = 0.00;
									$newOrderItem['ShippingPrice'] = 0.00;
									$newOrderItem['ShippingTax'] = 0.00;
									$newOrderItem['ShippingDiscount'] = 0.00;
									$newOrderItem['GiftWrapPrice'] = 0.00;
									$newOrderItem['GiftWrapTax'] = 0.00;
									if ($zusatzSKU[$orderItem['SellerSKU']]['Anzahl'] == '*')
									{
										$newOrderItem['QuantityOrdered'] = $orderItem['QuantityOrdered'];
										$newOrderItem['QuantityShipped'] = $orderItem['QuantityShipped'];
									}
									else
									{
										$newOrderItem['QuantityOrdered'] = $zusatzSKU[$orderItem['SellerSKU']]['Anzahl'];
										$newOrderItem['QuantityShipped'] = $zusatzSKU[$orderItem['SellerSKU']]['Anzahl'];
									}
									if (array_key_exists('Bezeichnung', $zusatzSKU[$orderItem['SellerSKU']]))
									{
										$newOrderItem['Title'] = $zusatzSKU[$orderItem['SellerSKU']]['Bezeichnung'];
									}
									else
									{
										$newOrderItem['Title'] = "";
									}
									$zuErgaenzendeProdukte[] = $newOrderItem;
								}
							}
							// Die neuen Produkte zur Liste ergänzen
							$opSet1['orderItemsListOutput'] = array_merge($opSet1['orderItemsListOutput'], $zuErgaenzendeProdukte);
							
							// Ersatz SKU vorbereiten
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
								if ($amazon_checked && $autoimport)
								{
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['OrderItemId'] = $orderItem['OrderItemId'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['SellerSKU'] = str_replace($searchSKU, $replaceSKU, $orderItem['SellerSKU']);
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['ASIN'] = $orderItem['ASIN'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['ItemPrice'] = $orderItem['ItemPrice'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['ItemTax'] = $orderItem['ItemTax'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['PromotionDiscount'] = $orderItem['PromotionDiscount'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['ShippingPrice'] = $orderItem['ShippingPrice'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['ShippingTax'] = $orderItem['ShippingTax'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['ShippingDiscount'] = $orderItem['ShippingDiscount'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['GiftWrapPrice'] = $orderItem['GiftWrapPrice'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['GiftWrapTax'] = $orderItem['GiftWrapTax'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['QuantityOrdered'] = $orderItem['QuantityOrdered'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['QuantityShipped'] = $orderItem['QuantityShipped'];
									$bestellungen_autoimport[$opSet1['AmazonOrderId']]['AmazonOrderIdProducts'][$zaehler]['Title'] = $orderItem['Title'];
									$zaehler++;
								}
							}
							echo "</td>";
						}
					}
					else
					{
						echo "<td>Keine Abfrage, Daten bereits importiert!</td>";
					}
					echo 	"</tr>";
				}
			}
			echo "</table><br>";
			if (!$autoimport) {
				echo "<input type=\"submit\" name=\"import\" value=\"Ausgewaehltes importieren\">";
			}
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
		
		if ($autoimport) {
			$bestellungen = $bestellungen_autoimport;
		}
		
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
				// Checke auf falsche Preise und schicke Warnemail
				If ($pricewarning == "true")
				{
				 	// Preiswarnungen in Array umwandeln
					$pricewarningsarray = array();
					foreach (split("\n", $pricewarninglist) as $einzelpricewarning)
					{
						$zerlegtepricewarning = split("\|", $einzelpricewarning);
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
								if (floatval($einzelprodukt['ItemPrice']) < floatval($pricevalue))
								{
									// Warnemail schreiben!!!
									$empfaenger = $pricewarningemail;
									$betreff = "PREISWARNUNG: Ein Artikel wurde vermutlich unter Preis verkauft";
									$nachricht = "Es gibt eine Preiswarnung für folgende Bestellung\r\n" . 
												 "Verkaufskanal    : " . $bestellung['SalesChannel'] . "\r\n" . 
												 "Bestellungnummer : " . $bestellung['AmazonOrderId'] . "\r\n" . 
												 "Artikel          : " . $einzelprodukt['SellerSKU'] . "\r\n" .
												 "Preis            : " . $einzelprodukt['ItemPrice'] . "\r\n";
									$header =	"From: kivi-import@opis-tech.com" . "\r\n" .
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
		else
		{
			echo "Keine Bestellungen ausgewaehlt/ es liegen keine Bestellungen vor!<br>";
		}
	}	
	
	if (isset($_POST["orderreports"]) || $autoimport)
	{
		$output = array();
		if ($Amazonaktiviert == "checked" && ($fulfillmentchannel == "amazon" || $filter == "Alle" || $filter == "Amazon"))
		{
				$amazonresult = getAmazonOrdersByReports($fulfillmentchannel, $bestellungvom_DATA, $bestellungbis_DATA, "EU");
				if(count($amazonresult) > 0)
				{
					$output = array_merge($output, $amazonresult);
				}
		}
// 		if ($Amazonaktiviert_COM == "checked" && ($fulfillmentchannel == "amazon" || $filter == "Alle" || $filter == "Amazon"))
// 		{
//  			$amazonresult = getAmazonOrdersByReports($fulfillmentchannel, $bestellungvom_DATA, $bestellungbis_DATA, "COM");
//  			if(count($amazonresult) > 0)
//  			{
//  				$output = array_merge($output, $amazonresult);
//  			}
// 		}			

	
		echo "<br>Datensaetze zum Adressupdate: ".count($output)."<br>";
		
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

		 	$abgleichsdatensaetze_autoimport = array();	
		 						 				
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
							
							if ($autoimport)
							{
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['AmazonOrderId'] = $einzelbestellung['AmazonOrderId'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['SellerOrderId'] = $einzelbestellung['SellerOrderId'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['PurchaseDate'] = $einzelbestellung['PurchaseDate'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['SalesChannel'] = $einzelbestellung['SalesChannel'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['FulfillmentChannel'] = $einzelbestellung['FulfillmentChannel'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['BuyerName'] = $einzelbestellung['BuyerName'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['Title'] = $einzelbestellung['Title'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['Name'] = $einzelbestellung['Name'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['AddressLine1'] = $einzelbestellung['AddressLine1'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['AddressLine2'] = $einzelbestellung['AddressLine2'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['PostalCode'] = $einzelbestellung['PostalCode'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['City'] = $einzelbestellung['City'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['CountryCode'] = $einzelbestellung['CountryCode'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['StateOrRegion'] = $einzelbestellung['StateOrRegion'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['BuyerEmail'] = $einzelbestellung['BuyerEmail'];			
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['Phone'] = $einzelbestellung['Phone'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['recipient-title'] = $einzelbestellung['recipient-title'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['recipient-name'] = $einzelbestellung['recipient-name'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['ship-address-1'] = $einzelbestellung['ship-address-1'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['ship-address-2'] = $einzelbestellung['ship-address-2'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['ship-address-3'] = $einzelbestellung['ship-address-3'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['ship-postal-code'] = $einzelbestellung['ship-postal-code'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['ship-city'] = $einzelbestellung['ship-city'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['ship-state'] = $einzelbestellung['ship-state'];
								$abgleichsdatensaetze_autoimport[$einzelbestellung['AmazonOrderId']]['ship-country'] = $einzelbestellung['ship-country'];
							}
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
			if (!$autoimport) {
				echo "<input type=\"submit\" name=\"adressabgleich\" value=\"Ausgewaehlte Adressen abgleichen\">";
			}
		}
	}
	
	if (isset($_POST["adressabgleich"]) || $autoimport)
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
		
		if ($autoimport)
		{
			$abgleichsdatensaetze = $abgleichsdatensaetze_autoimport;
		}
		
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
