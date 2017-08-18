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

	if ($_SERVER['PHP_AUTH_USER']<>$ERPftpuser || $_SERVER['PHP_AUTH_PW']<>$ERPftppwd)
	{
		Header("WWW-Authenticate: Basic realm=\"My Realm\"");
		Header("HTTP/1.0 401 Unauthorized");
		echo "Sie m&uuml;ssen sich autentifizieren\n";
		exit;
	}
	
	require_once "DB.php";

	if ($_POST["ok"] == "Alle Daten von alle Tabpages sichern")
	{
		$ok = true;
		$dsnP = array(
                    'phptype'  => 'pgsql',
                    'username' => $_POST["ERPuser"],
                    'password' => $_POST["ERPpass"],
                    'hostspec' => $_POST["ERPhost"],
                    'database' => $_POST["ERPdbname"],
                    'port'     => $_POST["ERPport"]
                    );
		$dbP=@DB::connect($dsnP);
		if (DB::isError($dbP)||!$dbP)
		{
			$ok=false;
			echo "Keine Verbindung zur ERP<br>";
			echo $dbP->userinfo;
			$dbP=false;
		}
		else
		{
			$rs=$dbP->getall("select id from employee where login = '".$_POST["ERPusrN"]."'");
			$_POST["ERPusrID"]=$rs[0][0];
		}
		if ($ok)
		{
			$f=fopen("conf.php","w");
			$v="2.0";
			$d=date("Y/m/d H:i:s");
			
			$ERPhost=$_POST["ERPhost"];
			$ERPport=$_POST["ERPport"];
			$ERPdbname=$_POST["ERPdbname"];
			$ERPuser=$_POST["ERPuser"];
			$ERPpass=$_POST["ERPpass"];
			$ERPusrN=$_POST["ERPusrN"];
			$ERPftpuser=$_POST["ERPftpuser"];
			$ERPftppwd=$_POST["ERPftppwd"];
			$debug=$_POST["debug"];
			$fehlendeSKU=$_POST["fehlendeSKU"];
			$platzhalterFehlendeSKU=$_POST["platzhalterFehlendeSKU"];
			$versandkosten=$_POST["versandkosten"];
			$geschenkverpackung=$_POST["geschenkverpackung"];
			$standardsprache=$_POST["standardsprache"];
			$standardprojekt=$_POST["standardprojekt"];
			$daysBeforeFrom=$_POST["daysBeforeFrom"];
			$portoemail=$_POST["portoemail"];
			$ersatzSprache=$_POST["ersatzSprache"];
			$pricewarning=$_POST["pricewarning"];
			$pricewarningemailsender=$_POST["pricewarningemailsender"];
			$pricewarningemail=$_POST["pricewarningemail"];
			$pricewarninglist=$_POST["pricewarninglist"];
			
			$Amazonaktiviert=$_POST["Amazonaktiviert"];
			$AmazonFBAaktiviert=$_POST["AmazonFBAaktiviert"];
			$AmazonMFNaktiviert=$_POST["AmazonMFNaktiviert"];
			$AmazonAbteilungsname=$_POST["AmazonAbteilungsname"];
			$AmazonBestellnummernprefix=$_POST["AmazonBestellnummernprefix"];
			$MerchantID=$_POST["MerchantID"];
			$AccessKeyID=$_POST["AccessKeyID"];
			$SecretKey=$_POST["SecretKey"];
			$EndpointUrl=$_POST["EndpointUrl"];
			$SigMethod=$_POST["SigMethod"];
			$SigVersion=$_POST["SigVersion"];
			$MarketplaceID_DE=$_POST["MarketplaceID_DE"];
			$MarketplaceID_GB=$_POST["MarketplaceID_GB"];
			$MarketplaceID_FR=$_POST["MarketplaceID_FR"];
			$MarketplaceID_IT=$_POST["MarketplaceID_IT"];
			$MarketplaceID_ES=$_POST["MarketplaceID_ES"];
			$zusatzProdukt=$_POST["zusatzProdukt"];
			$ersatzSKU=$_POST["ersatzSKU"];

			$Amazonaktiviert_COM=$_POST["Amazonaktiviert_COM"];			
			$AmazonFBAaktiviert_COM=$_POST["AmazonFBAaktiviert_COM"];
			$AmazonMFNaktiviert_COM=$_POST["AmazonMFNaktiviert_COM"];
			$MerchantID_COM=$_POST["MerchantID_COM"];
			$AccessKeyID_COM=$_POST["AccessKeyID_COM"];
			$SecretKey_COM=$_POST["SecretKey_COM"];
			$EndpointUrl_COM=$_POST["EndpointUrl_COM"];
			$MarketplaceID_US=$_POST["MarketplaceID_US"];
			$MarketplaceID_CA=$_POST["MarketplaceID_CA"];
			
			$eBayaktiviert=$_POST["eBayaktiviert"];
			$eBayAbteilungsname=$_POST["eBayAbteilungsname"];
			$eBayBestellnummernprefix=$_POST["eBayBestellnummernprefix"];
			$eBayServerUrl=$_POST["eBayServerUrl"];
			$eBayDEVID=$_POST["eBayDEVID"];
			$eBayAppID=$_POST["eBayAppID"];
			$eBayCertID=$_POST["eBayCertID"];
			$eBayUserToken=$_POST["eBayUserToken"];
			
			$Joomlaaktiviert=$_POST["Joomlaaktiviert"];
			$JoomlaAbteilungsname=$_POST["JoomlaAbteilungsname"];
			$JoomlaBestellnummernprefix=$_POST["JoomlaBestellnummernprefix"];
			$Joomlahost=$_POST["Joomlahost"];
			$Joomlaport=$_POST["Joomlaport"];
			$Joomladbname=$_POST["Joomladbname"];
			$Joomlauser=$_POST["Joomlauser"];
			$Joomlapass=$_POST["Joomlapass"];
			
			$Rakutenaktiviert=$_POST["Rakutenaktiviert"];
			$RakutenAbteilungsname=$_POST["RakutenAbteilungsname"];
			$RakutenBestellnummernprefix=$_POST["RakutenBestellnummernprefix"];
			$RakutenAPIUrl=$_POST["RakutenAPIUrl"];
			$RakutenAPISchluessel=$_POST["RakutenAPISchluessel"];
			
			fputs($f,"<?php\n// Verbindung zur ERP-db\n");
			fputs($f,"\$ERPhost=\"".$_POST["ERPhost"]."\";\n");
			fputs($f,"\$ERPport=\"".$_POST["ERPport"]."\";\n");
			fputs($f,"\$ERPdbname=\"".$_POST["ERPdbname"]."\";\n");
			fputs($f,"\$ERPuser=\"".$_POST["ERPuser"]."\";\n");
			fputs($f,"\$ERPpass=\"".$_POST["ERPpass"]."\";\n");
			fputs($f,"\$ERPusrN=\"".$_POST["ERPusrN"]."\";\n");
			fputs($f,"\$ERPusrID=\"".$_POST["ERPusrID"]."\";\n");
			fputs($f,"\$ERPftpuser=\"".$_POST["ERPftpuser"]."\";\n");
			fputs($f,"\$ERPftppwd=\"".$_POST["ERPftppwd"]."\";\n");
			fputs($f,"\$debug=\"".$_POST["debug"]."\";\n");
			fputs($f,"\$fehlendeSKU=\"".$_POST["fehlendeSKU"]."\";\n");
			fputs($f,"\$platzhalterFehlendeSKU=\"".$_POST["platzhalterFehlendeSKU"]."\";\n");
			fputs($f,"\$versandkosten=\"".$_POST["versandkosten"]."\";\n");
			fputs($f,"\$geschenkverpackung=\"".$_POST["geschenkverpackung"]."\";\n");
			fputs($f,"\$standardsprache=\"".$_POST["standardsprache"]."\";\n");
			fputs($f,"\$standardprojekt=\"".$_POST["standardprojekt"]."\";\n");
			fputs($f,"\$daysBeforeFrom=\"".$_POST["daysBeforeFrom"]."\";\n");
			fputs($f,"\$portoemail=\"".$_POST["portoemail"]."\";\n");
			fputs($f,"\$ersatzSprache=\"".$_POST["ersatzSprache"]."\";\n");
			fputs($f,"\$pricewarning=\"".$_POST["pricewarning"]."\";\n");
			fputs($f,"\$pricewarningemailsender=\"".$_POST["pricewarningemailsender"]."\";\n");
			fputs($f,"\$pricewarningemail=\"".$_POST["pricewarningemail"]."\";\n");
			fputs($f,"\$pricewarninglist=\"".$_POST["pricewarninglist"]."\";\n");

			fputs($f,"\$Amazonaktiviert=\"".$_POST["Amazonaktiviert"]."\";\n");
			fputs($f,"\$AmazonFBAaktiviert=\"".$_POST["AmazonFBAaktiviert"]."\";\n");
			fputs($f,"\$AmazonMFNaktiviert=\"".$_POST["AmazonMFNaktiviert"]."\";\n");
			fputs($f,"\$AmazonAbteilungsname=\"".$_POST["AmazonAbteilungsname"]."\";\n");
			fputs($f,"\$AmazonBestellnummernprefix=\"".$_POST["AmazonBestellnummernprefix"]."\";\n");
			fputs($f,"\$MerchantID=\"".$_POST["MerchantID"]."\";\n");
			fputs($f,"\$AccessKeyID=\"".$_POST["AccessKeyID"]."\";\n");
			fputs($f,"\$SecretKey=\"".$_POST["SecretKey"]."\";\n");
			fputs($f,"\$EndpointUrl=\"".$_POST["EndpointUrl"]."\";\n");
			fputs($f,"\$SigMethod=\"".$_POST["SigMethod"]."\";\n");
			fputs($f,"\$SigVersion=\"".$_POST["SigVersion"]."\";\n");
			fputs($f,"\$MarketplaceID_DE=\"".$_POST["MarketplaceID_DE"]."\";\n");
			fputs($f,"\$MarketplaceID_GB=\"".$_POST["MarketplaceID_GB"]."\";\n");
			fputs($f,"\$MarketplaceID_FR=\"".$_POST["MarketplaceID_FR"]."\";\n");
			fputs($f,"\$MarketplaceID_IT=\"".$_POST["MarketplaceID_IT"]."\";\n");
			fputs($f,"\$MarketplaceID_ES=\"".$_POST["MarketplaceID_ES"]."\";\n");
			fputs($f,"\$zusatzProdukt=\"".$_POST["zusatzProdukt"]."\";\n");
			fputs($f,"\$ersatzSKU=\"".$_POST["ersatzSKU"]."\";\n");
			
			fputs($f,"\$Amazonaktiviert_COM=\"".$_POST["Amazonaktiviert_COM"]."\";\n");
			fputs($f,"\$AmazonFBAaktiviert_COM=\"".$_POST["AmazonFBAaktiviert_COM"]."\";\n");
			fputs($f,"\$AmazonMFNaktiviert_COM=\"".$_POST["AmazonMFNaktiviert_COM"]."\";\n");
			fputs($f,"\$MerchantID_COM=\"".$_POST["MerchantID_COM"]."\";\n");
			fputs($f,"\$AccessKeyID_COM=\"".$_POST["AccessKeyID_COM"]."\";\n");
			fputs($f,"\$SecretKey_COM=\"".$_POST["SecretKey_COM"]."\";\n");
			fputs($f,"\$EndpointUrl_COM=\"".$_POST["EndpointUrl_COM"]."\";\n");			
			fputs($f,"\$MarketplaceID_US=\"".$_POST["MarketplaceID_US"]."\";\n");
			fputs($f,"\$MarketplaceID_CA=\"".$_POST["MarketplaceID_CA"]."\";\n");

			fputs($f,"\$eBayaktiviert=\"".$_POST["eBayaktiviert"]."\";\n");
			fputs($f,"\$eBayAbteilungsname=\"".$_POST["eBayAbteilungsname"]."\";\n");
			fputs($f,"\$eBayBestellnummernprefix=\"".$_POST["eBayBestellnummernprefix"]."\";\n");
			fputs($f,"\$eBayServerUrl=\"".$_POST["eBayServerUrl"]."\";\n");
			fputs($f,"\$eBayDEVID=\"".$_POST["eBayDEVID"]."\";\n");
			fputs($f,"\$eBayAppID=\"".$_POST["eBayAppID"]."\";\n");
			fputs($f,"\$eBayCertID=\"".$_POST["eBayCertID"]."\";\n");
			fputs($f,"\$eBayUserToken=\"".$_POST["eBayUserToken"]."\";\n");
			
			fputs($f,"\$Joomlaaktiviert=\"".$_POST["Joomlaaktiviert"]."\";\n");
			fputs($f,"\$JoomlaAbteilungsname=\"".$_POST["JoomlaAbteilungsname"]."\";\n");
			fputs($f,"\$JoomlaBestellnummernprefix=\"".$_POST["JoomlaBestellnummernprefix"]."\";\n");
			fputs($f,"\$Joomlahost=\"".$_POST["Joomlahost"]."\";\n");
			fputs($f,"\$Joomlaport=\"".$_POST["Joomlaport"]."\";\n");
			fputs($f,"\$Joomladbname=\"".$_POST["Joomladbname"]."\";\n");
			fputs($f,"\$Joomlauser=\"".$_POST["Joomlauser"]."\";\n");
			fputs($f,"\$Joomlapass=\"".$_POST["Joomlapass"]."\";\n");
			
			fputs($f,"\$Rakutenaktiviert=\"".$_POST["Rakutenaktiviert"]."\";\n");
			fputs($f,"\$RakutenAbteilungsname=\"".$_POST["RakutenAbteilungsname"]."\";\n");
			fputs($f,"\$RakutenBestellnummernprefix=\"".$_POST["RakutenBestellnummernprefix"]."\";\n");
			fputs($f,"\$RakutenAPIUrl=\"".$_POST["RakutenAPIUrl"]."\";\n");
			fputs($f,"\$RakutenAPISchluessel=\"".$_POST["RakutenAPISchluessel"]."\";\n");

			fputs($f,"?>");
			fclose($f);
			echo "Konfiguration gesichert !<br><br>";
		}
		else
		{
			$ERPhost=$_POST["ERPhost"];
			$ERPport=$_POST["ERPport"];
			$ERPdbname=$_POST["ERPdbname"];
			$ERPuser=$_POST["ERPuser"];
			$ERPpass=$_POST["ERPpass"];
			$ERPusrN=$_POST["ERPusrN"];
			$ERPftpuser=$_POST["ERPftpuser"];
			$ERPftppwd=$_POST["ERPftppwd"];
			$debug=$_POST["debug"];
			$fehlendeSKU=$_POST["fehlendeSKU"];
			$platzhalterFehlendeSKU=$_POST["platzhalterFehlendeSKU"];
			$versandkosten=$_POST["versandkosten"];
			$geschenkverpackung=$_POST["geschenkverpackung"];
			$standardsprache=$_POST["standardsprache"];
			$standardprojekt=$_POST["standardprojekt"];
			$daysBeforeFrom=$_POST["daysBeforeFrom"];
			$portoemail=$_POST["portoemail"];
			$ersatzSprache=$_POST["ersatzSprache"];
			$pricewarning=$_POST["pricewarning"];
			$pricewarningemailsender=$_POST["pricewarningemailsender"];
			$pricewarningemail=$_POST["pricewarningemail"];
			$pricewarninglist=$_POST["pricewarninglist"];

			$Amazonaktiviert=$_POST["Amazonaktiviert"];
			$AmazonFBAaktiviert=$_POST["AmazonFBAaktiviert"];
			$AmazonMFNaktiviert=$_POST["AmazonMFNaktiviert"];
			$AmazonAbteilungsname=$_POST["AmazonAbteilungsname"];
			$AmazonBestellnummernprefix=$_POST["AmazonBestellnummernprefix"];
			$MerchantID=$_POST["MerchantID"];
			$AccessKeyID=$_POST["AccessKeyID"];
			$SecretKey=$_POST["SecretKey"];
			$EndpointUrl=$_POST["EndpointUrl"];
			$SigMethod=$_POST["SigMethod"];
			$SigVersion=$_POST["SigVersion"];
			$MarketplaceID_DE=$_POST["MarketplaceID_DE"];
			$MarketplaceID_GB=$_POST["MarketplaceID_GB"];
			$MarketplaceID_FR=$_POST["MarketplaceID_FR"];
			$MarketplaceID_IT=$_POST["MarketplaceID_IT"];
			$MarketplaceID_ES=$_POST["MarketplaceID_ES"];
			$zusatzProdukt=$_POST["zusatzProdukt"];
			$ersatzSKU=$_POST["ersatzSKU"];
			
			$Amazonaktiviert_COM=$_POST["Amazonaktiviert_COM"];
			$AmazonFBAaktiviert_COM=$_POST["AmazonFBAaktiviert_COM"];
			$AmazonMFNaktiviert_COM=$_POST["AmazonMFNaktiviert_COM"];
			$MerchantID_COM=$_POST["MerchantID_COM"];
			$AccessKeyID_COM=$_POST["AccessKeyID_COM"];
			$SecretKey_COM=$_POST["SecretKey_COM"];
			$EndpointUrl_COM=$_POST["EndpointUrl_COM"];
			$MarketplaceID_US=$_POST["MarketplaceID_US"];
			$MarketplaceID_CA=$_POST["MarketplaceID_CA"];

			$eBayaktiviert=$_POST["eBayaktiviert"];
			$eBayAbteilungsname=$_POST["eBayAbteilungsname"];
			$eBayBestellnummernprefix=$_POST["eBayBestellnummernprefix"];						
			$eBayServerUrl=$_POST["eBayServerUrl"];
			$eBayDEVID=$_POST["eBayDEVID"];
			$eBayAppID=$_POST["eBayAppID"];
			$eBayCertID=$_POST["eBayCertID"];
			$eBayUserToken=$_POST["eBayUserToken"];			
			
			$Joomlaaktiviert=$_POST["Joomlaaktiviert"];
			$JoomlaAbteilungsname=$_POST["JoomlaAbteilungsname"];
			$JoomlaBestellnummernprefix=$_POST["JoomlaBestellnummernprefix"];						
			$Joomlahost=$_POST["Joomlahost"];
			$Joomlaport=$_POST["Joomlaport"];
			$Joomladbname=$_POST["Joomladbname"];
			$Joomlauser=$_POST["Joomlauser"];
			$Joomlapass=$_POST["Joomlapass"];
			
			$Rakutenaktiviert=$_POST["Rakutenaktiviert"];
			$RakutenAbteilungsname=$_POST["RakutenAbteilungsname"];
			$RakutenBestellnummernprefix=$_POST["RakutenBestellnummernprefix"];						
			$RakutenAPIUrl=$_POST["RakutenAPIUrl"];
			$RakutenAPISchluessel=$_POST["RakutenAPISchluessel"];
		}
	}
	else
	{
		require "conf.php";
	}
?>
<html>
	<head>
		<style>
			body {font-family: "Lato", sans-serif;}
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
		</style>
		<script>
			function openTab(evt, tabName) {
			    var i, tabcontent, tablinks;
			    tabcontent = document.getElementsByClassName("tabcontent");
			    for (i = 0; i < tabcontent.length; i++) {
			        tabcontent[i].style.display = "none";
			    }
			    tablinks = document.getElementsByClassName("tablinks");
			    for (i = 0; i < tablinks.length; i++) {
			        tablinks[i].className = tablinks[i].className.replace(" active", "");
			    }
			    document.getElementById(tabName).style.display = "block";
			    evt.currentTarget.className += " active";
			}
			
			// Get the element with id="defaultOpen" and click on it
		</script>
	</head>
	<body>
		<div class="tab">
		  <button class="tablinks" onclick="openTab(event, 'Kivitendo')" id="defaultOpen">Kivitendo</button>
		  <button class="tablinks" onclick="openTab(event, 'Sprachen')">Sprachen</button>
		  <button class="tablinks" onclick="openTab(event, 'SKU')">SKU</button>
		  <button class="tablinks" onclick="openTab(event, 'Preiswarnungen')">Preiswarnungen</button>
		  <button class="tablinks" onclick="openTab(event, 'Porto')">Porto</button>
		  <button class="tablinks" onclick="openTab(event, 'Amazon')">Amazon</button>
		  <button class="tablinks" onclick="openTab(event, 'Ebay')">Ebay</button>
		  <button class="tablinks" onclick="openTab(event, 'Joomla')">Joomla</button>
		  <button class="tablinks" onclick="openTab(event, 'Rakuten')">Rakuten</button>
		</div>
			
		<form name="ConfEdit" method="post" action="confedit.php">
			<input type="hidden" name="ERPusrID" value="<?= $ERPusrID ?>">

			<div id="Kivitendo" class="tabcontent">
				<div class="spalte">Multishop Import/Confedit User</div><div class="spalte"><input type="text" name="ERPftpuser" size="50" value="<?= $ERPftpuser ?>"></div><br>
				<div class="spalte">Multishop Import/Confedit PWD</div><div class="spalte"><input type="text" name="ERPftppwd" size="50" value="<?= $ERPftppwd ?>"></div><br><br>
				
				<div class="spalte">Kivi-Host</div><div class="spalte"><input type="text" name="ERPhost" size="50" value="<?= $ERPhost ?>"></div><br>
				<div class="spalte">Kivi-Port</div><div class="spalte"><input type="text" name="ERPport" size="50" value="<?= $ERPport ?>"></div><br>
				<div class="spalte">Kivi-Database</div><div class="spalte"><input type="text" name="ERPdbname" size="50" value="<?= $ERPdbname ?>"></div><br>
				<div class="spalte">Kivi db-User Name</div><div class="spalte"><input type="text" name="ERPuser" size="50" value="<?= $ERPuser ?>"></div><br>
				<div class="spalte">Kivi db-User PWD</div><div class="spalte"><input type="text" name="ERPpass" size="50" value="<?= $ERPpass ?>"></div><br>
				<div class="spalte">Kivi User-ID</div><div class="spalte"><input type="text" name="ERPusrN" size="50" value="<?= $ERPusrN ?>"></div><br>
				<div class="spalte">Kivi DB Logging</div><div class="spalte">ein<input type="radio" name="debug" value="true" <?= ($debug=="true")?"checked":"" ?>>aus<input type="radio" name="debug" value="false" <?= ($debug!="true")?"checked":"" ?>></div><br>
				<div class="spalte">Bei fehlenden Produktnummern alle Daten von Shops uebernehmen</div><div class="spalte">ja<input type="radio" name="fehlendeSKU" value="true" <?= ($fehlendeSKU=="true")?"checked":"" ?>>nein<input type="radio" name="fehlendeSKU" value="false" <?= ($fehlendeSKU!="true")?"checked":"" ?>></div><br>
				<div class="spalte">Kivi-Artikel fehlender Produkte:</div><div class="spalte"><input type="text" name="platzhalterFehlendeSKU" size="50" value="<?= $platzhalterFehlendeSKU ?>"></div><br>
				<div class="spalte">Kivi-Artikel Versandkosten</div><div class="spalte"><input type="text" name="versandkosten" size="50" value="<?= $versandkosten ?>"></div><br>
				<div class="spalte">Kivi-Artikel Geschenkverpackung</div><div class="spalte"><input type="text" name="geschenkverpackung" size="50" value="<?= $geschenkverpackung ?>"></div><br>
				<div class="spalte">Standardprojekt (Bezeichnung)</div><div class="spalte"><input type="text" name="standardprojekt" size="50" value="<?= $standardprojekt ?>"></div><br>
				<div class="spalte">Standardzeitraum Datenimport in Tagen</div><div class="spalte"><input type="text" name="daysBeforeFrom" size="50" value="<?= $daysBeforeFrom ?>"></div><br>
			</div>
	
			<div id="Sprachen" class="tabcontent">
				<div class="spalte">Standardsprache (Kuerzel)</div><div class="spalte"><input type="text" name="standardsprache" size="50" value="<?= $standardsprache ?>"></div><br>
				<div class="spalte">Liste Sprachzuordnungen<br>Marketplace -> Sprache<br><br>(eine pro Zeile, | ist Trenner)</div><div class="spalte"><textarea name="ersatzSprache" cols="100" rows="25"><?= $ersatzSprache ?></textarea></div><br>
			</div>
	
			<div id="SKU" class="tabcontent">
				<div class="spalte">Liste Zusatzprodukte pro SKU, diese Liste wird vor den zu ersetzenden SKU verarbeitet<br><br>SKU Produkt[@Marktplatz1@Marktplatz2...(optional)]|SKU Zusatzprodukt|Anzahl (* oder Ganzzahl)|Bezeichnung (optional)<br><br>(eins pro Zeile, | ist Trenner)</div><div class="spalte"><textarea name="zusatzProdukt" cols="100" rows="25"><?= $zusatzProdukt ?></textarea></div><br>
				<div class="spalte">Liste mit den zu ersetzenden SKU<br><br>Amazon -> Kivi<br><br>(eine pro Zeile, | ist Trenner)</div><div class="spalte"><textarea name="ersatzSKU" cols="100" rows="25"><?= $ersatzSKU ?></textarea></div><br>
			</div>
	
			<div id="Preiswarnungen" class="tabcontent">
				<div class="spalte">Preiswarnungen verschicken</div><div class="spalte">ein<input type="radio" name="pricewarning" value="true" <?= ($pricewarning=="true")?"checked":"" ?>>aus<input type="radio" name="pricewarning" value="false" <?= ($pricewarning!="true")?"checked":"" ?>></div><br>
				<div class="spalte">Emailabsender Preiswarnungen</div><div class="spalte"><input type="text" name="pricewarningemailsender" size="50" value="<?= $pricewarningemailsender ?>"></div><br>
				<div class="spalte">Emailempfaenger Preiswarnungen</div><div class="spalte"><input type="text" name="pricewarningemail" size="50" value="<?= $pricewarningemail ?>"></div><br>
				<div class="spalte">Liste Preiswarnungen<br>SKU -> Betrag<br>(eine pro Zeile, | ist Trenner)</div><div class="spalte"><textarea name="pricewarninglist" cols="50" rows="25"><?= $pricewarninglist ?></textarea></div><br>
			</div>

			<div id="Porto" class="tabcontent">
				<div class="spalte">Emailempfaenger Porto</div><div class="spalte"><input type="text" name="portoemail" size="50" value="<?= $portoemail ?>"></div><br>
			</div>
	
			<div id="Amazon" class="tabcontent">
				<div class="spalte">Amazon aktiviert</div><div class="spalte"><input type="checkbox" name="Amazonaktiviert" value="checked" <?php if ($Amazonaktiviert == "checked") { echo "checked=\"checked\""; } ?> ></div><br>
				<div class="spalte">Amazon FBA aktiviert</div><div class="spalte"><input type="checkbox" name="AmazonFBAaktiviert" value="checked" <?php if ($AmazonFBAaktiviert == "checked") { echo "checked=\"checked\""; } ?> ></div><br>
				<div class="spalte">Amazon MFN aktiviert</div><div class="spalte"><input type="checkbox" name="AmazonMFNaktiviert" value="checked" <?php if ($AmazonMFNaktiviert == "checked") { echo "checked=\"checked\""; } ?> ></div><br>
				<div class="spalte">Amazon Abteilungsname</div><div class="spalte"><input type="text" name="AmazonAbteilungsname" size="50" value="<?= $AmazonAbteilungsname ?>"></div><br>
				<div class="spalte">Amazon Bestellnummernprefix</div><div class="spalte"><input type="text" name="AmazonBestellnummernprefix" size="50" value="<?= $AmazonBestellnummernprefix ?>"></div><br>
				<div class="spalte">Amazon MerchantID</div><div class="spalte"><input type="text" name="MerchantID" size="50" value="<?= $MerchantID ?>"></div><br>
				<div class="spalte">Amazon AccessKeyID</div><div class="spalte"><input type="text" name="AccessKeyID" size="50" value="<?= $AccessKeyID ?>"></div><br>
				<div class="spalte">Amazon SecretKey</div><div class="spalte"><input type="text" name="SecretKey" size="50" value="<?= $SecretKey ?>"></div><br>
				<div class="spalte">Amazon EndpointUrl</div><div class="spalte"><input type="text" name="EndpointUrl" size="50" value="<?= $EndpointUrl ?>"></div><br>
				<div class="spalte">Amazon SigMethod</div><div class="spalte"><input type="text" name="SigMethod" size="50" value="<?= $SigMethod ?>"></div><br>
				<div class="spalte">Amazon SigVersion</div><div class="spalte"><input type="text" name="SigVersion" size="50" value="<?= $SigVersion ?>"></div><br>
				<div class="spalte">Amazon MarketplaceID_DE</div><div class="spalte"><input type="text" name="MarketplaceID_DE" size="50" value="<?= $MarketplaceID_DE ?>"></div><br>
				<div class="spalte">Amazon MarketplaceID_GB</div><div class="spalte"><input type="text" name="MarketplaceID_GB" size="50" value="<?= $MarketplaceID_GB ?>"></div><br>
				<div class="spalte">Amazon MarketplaceID_FR</div><div class="spalte"><input type="text" name="MarketplaceID_FR" size="50" value="<?= $MarketplaceID_FR ?>"></div><br>
				<div class="spalte">Amazon MarketplaceID_IT</div><div class="spalte"><input type="text" name="MarketplaceID_IT" size="50" value="<?= $MarketplaceID_IT ?>"></div><br>
				<div class="spalte">Amazon MarketplaceID_ES</div><div class="spalte"><input type="text" name="MarketplaceID_ES" size="50" value="<?= $MarketplaceID_ES ?>"></div><br><br>
				<div class="spalte">Amazon.com aktiviert</div><div class="spalte"><input type="checkbox" name="Amazonaktiviert_COM" value="checked" <?php if ($Amazonaktiviert_COM == "checked") { echo "checked=\"checked\""; } ?>></div><br>
				<div class="spalte">Amazon.com FBA aktiviert</div><div class="spalte"><input type="checkbox" name="AmazonFBAaktiviert_COM" value="checked" <?php if ($AmazonFBAaktiviert_COM == "checked") { echo "checked=\"checked\""; } ?>></div><br>
				<div class="spalte">Amazon.com MFN aktiviert</div><div class="spalte"><input type="checkbox" name="AmazonMFNaktiviert_COM" value="checked" <?php if ($AmazonMFNaktiviert_COM == "checked") { echo "checked=\"checked\""; } ?>></div><br>
				<div class="spalte">Amazon.com MerchantID</div><div class="spalte"><input type="text" name="MerchantID_COM" size="50" value="<?= $MerchantID_COM ?>"></div><br>
				<div class="spalte">Amazon.com AccessKeyID</div><div class="spalte"><input type="text" name="AccessKeyID_COM" size="50" value="<?= $AccessKeyID_COM ?>"></div><br>
				<div class="spalte">Amazon.com SecretKey</div><div class="spalte"><input type="text" name="SecretKey_COM" size="50" value="<?= $SecretKey_COM ?>"></div><br>
				<div class="spalte">Amazon.com EndpointUrl</div><div class="spalte"><input type="text" name="EndpointUrl_COM" size="50" value="<?= $EndpointUrl_COM ?>"></div><br>
				<div class="spalte">Amazon.com MarketplaceID_US</div><div class="spalte"><input type="text" name="MarketplaceID_US" size="50" value="<?= $MarketplaceID_US ?>"></div><br>
				<div class="spalte">Amazon.com MarketplaceID_CA</div><div class="spalte"><input type="text" name="MarketplaceID_CA" size="50" value="<?= $MarketplaceID_CA ?>"></div><br>
			</div>
	
			<div id="Ebay" class="tabcontent">
				<div class="spalte">eBay aktiviert</div><div class="spalte"><input type="checkbox" name="eBayaktiviert" value="checked" <?php if ($eBayaktiviert == "checked") { echo "checked=\"checked\""; } ?>></div><br>
				<div class="spalte">eBay Abteilungsname</div><div class="spalte"><input type="text" name="eBayAbteilungsname" size="50" value="<?= $eBayAbteilungsname ?>"></div><br>
				<div class="spalte">eBay Bestellnummernprefix</div><div class="spalte"><input type="text" name="eBayBestellnummernprefix" size="50" value="<?= $eBayBestellnummernprefix ?>"></div><br>
				<div class="spalte">eBay ServerUrl</div><div class="spalte"><input type="text" name="eBayServerUrl" size="50" value="<?= $eBayServerUrl ?>"></div><br>
				<div class="spalte">eBay DEVID</div><div class="spalte"><input type="text" name="eBayDEVID" size="50" value="<?= $eBayDEVID ?>"></div><br>
				<div class="spalte">eBay AppID</div><div class="spalte"><input type="text" name="eBayAppID" size="50" value="<?= $eBayAppID ?>"></div><br>
				<div class="spalte">eBay CertID</div><div class="spalte"><input type="text" name="eBayCertID" size="50" value="<?= $eBayCertID ?>"></div><br>
				<div class="spalte">eBay UserToken</div><div class="spalte"><input type="text" name="eBayUserToken" size="50" value="<?= $eBayUserToken ?>"></div><br>
			</div>
	
			<div id="Joomla" class="tabcontent">
				<div class="spalte">Joomla aktiviert</div><div class="spalte"><input type="checkbox" name="Joomlaaktiviert" value="checked" <?php if ($Joomlaaktiviert == "checked") { echo "checked=\"checked\""; } ?>></div><br>
				<div class="spalte">Joomla Abteilungsname</div><div class="spalte"><input type="text" name="JoomlaAbteilungsname" size="50" value="<?= $JoomlaAbteilungsname ?>"></div><br>
				<div class="spalte">Joomla Bestellnummernprefix</div><div class="spalte"><input type="text" name="JoomlaBestellnummernprefix" size="50" value="<?= $JoomlaBestellnummernprefix ?>"></div><br>
				<div class="spalte">Joomla-Host</div><div class="spalte"><input type="text" name="Joomlahost" size="50" value="<?= $Joomlahost ?>"></div><br>
				<div class="spalte">Joomla-Port</div><div class="spalte"><input type="text" name="Joomlaport" size="50" value="<?= $Joomlaport ?>"></div><br>
				<div class="spalte">Joomla-Database</div><div class="spalte"><input type="text" name="Joomladbname" size="50" value="<?= $Joomladbname ?>"></div><br>
				<div class="spalte">Joomla db-User Name</div><div class="spalte"><input type="text" name="Joomlauser" size="50" value="<?= $Joomlauser ?>"></div><br>
				<div class="spalte">Joomla db-User PWD</div><div class="spalte"><input type="text" name="Joomlapass" size="50" value="<?= $Joomlapass ?>"></div><br>
			</div>
	
			<div id="Rakuten" class="tabcontent">
				<div class="spalte">Rakuten aktiviert</div><div class="spalte"><input type="checkbox" name="Rakutenaktiviert" value="checked" <?php if ($Rakutenaktiviert == "checked") { echo "checked=\"checked\""; } ?>></div><br>
				<div class="spalte">Rakuten Abteilungsname</div><div class="spalte"><input type="text" name="RakutenAbteilungsname" size="50" value="<?= $RakutenAbteilungsname ?>"></div><br>
				<div class="spalte">Rakuten Bestellnummernprefix</div><div class="spalte"><input type="text" name="RakutenBestellnummernprefix" size="50" value="<?= $RakutenBestellnummernprefix ?>"></div><br>
				<div class="spalte">Rakuten API-Url</div><div class="spalte"><input type="text" name="RakutenAPIUrl" size="50" value="<?= $RakutenAPIUrl ?>"></div><br>
				<div class="spalte">Rakuten API-Schluessel</div><div class="spalte"><input type="text" name="RakutenAPISchluessel" size="50" value="<?= $RakutenAPISchluessel ?>"></div><br>
			</div>
			<br>
			<div><input type="submit" name="ok" value="Alle Daten von alle Tabpages sichern"></div>
		</form>
		<script>
			document.getElementById("defaultOpen").click();
		</script>		
	</body>
</html>
<?php
}
?>
