<?php

require_once "DB.php";
require_once "MDB2.php";
require "conf.php";
require "constants.php";
require "ecb.php";

$VERSANDKOSTEN = 0;
$GESCHENKVERPACKUNG = 0;
$ARTIKELPOSITION = 0;

$dsnP = array(	'phptype'  => 'pgsql',
				'username' => $ERPuser,
				'password' => $ERPpass,
				'hostspec' => $ERPhost,
				'database' => $ERPdbname,
				'port'     => $ERPport);

$log = false;
$erp = false;

/****************************************************
* Debugmeldungen in File schreiben
****************************************************/
if ($debug == "true")		// zum Debuggen
{
	$log = fopen("tmp/shop.log","a");
}
else
{
	$log = false;
}

/****************************************************
* ERPverbindung aufbauen
****************************************************/
$options = array('result_buffering' => false,);
$erp = @DB::connect($dsnP);
$erp = MDB2::factory($dsnP, $options);

if (!$erp)
{
	echo $erp->getMessage();
}
if (PEAR::isError($erp))
{
	$aktuelleZeit = date("Y-m-d H:i:s");
	if ($log)
	{
		fputs($log,$aktuelleZeit.": ERP-Connect\n");
	}
	echo $erp->getMessage();
	die ($erp->getMessage());
}
else
{
	if ($erp->autocommit)
	{
		$erp->autocommit();
	}
}

$erp->setFetchMode(MDB2_FETCHMODE_ASSOC);

/****************************************************
* SQL-Befehle absetzen
****************************************************/
function query($db, $sql, $function="--")
{
 	$aktuelleZeit = date("d.m.y H:i:s");
 	if ($GLOBALS["log"])
 	{
	 	fputs($GLOBALS["log"],$aktuelleZeit.": ".$function."\n".$sql."\n");
 	}
 	$rc = $GLOBALS[$db]->query($sql);
 	if ($GLOBALS["log"])
 	{
	 	fputs($GLOBALS["log"],print_r($rc,true)."\n");
 	}
    if(PEAR::isError($rc))
    {
		return -99;
 	}
 	else
 	{
		return true;
 	}
}

/****************************************************
* Datenbank abfragen
****************************************************/
function getAll($db, $sql, $function="--")
{
	$aktuelleZeit = date("d.m.y H:i:s");
	if ($GLOBALS["log"])
	{
		fputs($GLOBALS["log"],$aktuelleZeit.": ".$function."\n".$sql."\n");
	}
	
	$rs = $GLOBALS[$db]->queryAll($sql);
	
    if (array_key_exists('message', $rs) && $rs->message <> "")
    {
	    if ($GLOBALS["log"])
	    {
		    fputs($GLOBALS["log"],print_r($rs,true)."\n");
	    }
		return false;
	}
	else
	{
		return $rs;
	}
}

/****************************************************
* naechste_freie_Auftragsnummer() Naechste Auftragsnummer (ERP) holen
****************************************************/
function naechste_freie_Auftragsnummer()
{
	$sql="select * from defaults";
	$sql1="update defaults set sonumber=";
	$rs2=getAll("erp",$sql,"naechste_freie_Auftragsnummer");
	if ($rs2[0]["sonumber"]) {
		$auftrag=$rs2[0]["sonumber"]+1;
		$rc=query("erp",$sql1.$auftrag, "naechste_freie_Auftragsnummer");
		if ($rc === -99) {
			echo "Kann keine Auftragsnummer erzeugen - Abbruch";
			exit();
		}
		return $auftrag;
	} else {
		return false;
	}
}

/****************************************************
* naechste_freie_Kundennummer() Naechste Kundennummer (ERP) holen
****************************************************/
function naechste_freie_Kundennummer()
{
	$sql = "select * from defaults";
	$sql1 = "update defaults set customernumber='";
	$rs2 = getAll("erp", $sql, "naechste_freie_Kundennummer");
	if ($rs2[0]["customernumber"])
	{
		$kdnr = $rs2[0]["customernumber"] + 1;
		$rc = query("erp", $sql1.$kdnr."'", "naechste_freie_Kundennummer");
		if ($rc === -99)
		{
			echo "Kann keine Kundennummer erzeugen - Abbruch";
			exit();
		}
		return $kdnr;
	}
	else
	{
		return false;
	}
}

/**********************************************
* insert_Versandadresse($bestellung, $kundenid)
***********************************************/
function insert_Versandadresse($bestellung, $kundenid)
{
	$set = $kundenid;
	if ($bestellung["ship-address-2"] != "")
	{
		$set .= ",'".pg_escape_string($bestellung["recipient-name"])."','".pg_escape_string($bestellung["ship-address-1"])."','".pg_escape_string($bestellung["ship-address-2"])."',";
	}
	else
	{
		$set .= ",'".pg_escape_string($bestellung["recipient-name"])."','','".pg_escape_string($bestellung["ship-address-1"])."',";
	}
	$set .= "'".pg_escape_string(substr($bestellung["ship-postal-code"], 0, 10))."',";
	$set .= "'".pg_escape_string($bestellung["ship-city"])."',";
	
	if (array_key_exists($bestellung["ship-country"], $GLOBALS["LAND"]))
	{
		$set .= "'".utf8_encode($GLOBALS["LAND"][$bestellung["ship-country"]])."'";
	}
	else
	{
		$set .= "'".pg_escape_string($bestellung["ship-country"])."'";
	}
	
	$sql = "insert into shipto (trans_id, shiptoname, shiptodepartment_1, shiptostreet, shiptozipcode, shiptocity, shiptocountry, module) values ($set,'CT')";
	$rc = query("erp", $sql, "insert_Versandadresse");
	if ($rc === -99)
	{
		return false;
	}
	$sql = "select shipto_id from shipto where trans_id=$kundenid AND module='CT' order by itime desc limit 1";
	$rs = getAll("erp", $sql, "insert_Versandadresse");
	if ($rs[0]["shipto_id"] > 0)
	{
		$sid = $rs[0]["shipto_id"];
		return $sid;
	}
	else
	{
		echo "Fehler bei abweichender Anschrift ".$bestellung["recipient-name"];
		return false;
	}
}
            
/**********************************************
* check_update_Kundendaten($bestellung)
***********************************************/
function check_update_Kundendaten($bestellung)
{
	$rc = query("erp","BEGIN WORK","check_update_Kundendaten");
	
	if ($rc === -99)
	{
		echo "Probleme mit Transaktion. Abbruch!";
		exit();
	}
	if (checkCustomer($bestellung['BuyerEmail'], $bestellung['BuyerName']) == "vorhanden")  // Bestandskunde; BuyerEmail (Amazon eindeutig) oder BuyerName vorhanden
	{
		$msg = "update ";
		$kdnr = checke_update_alte_Kundendaten($bestellung, false);
		if ($kdnr == -1)		// Kunde nicht gefunden, neu anlegen.
		{
			$msg = "insert ";
			$kdnr = insert_neuen_Kunden($bestellung);
		}
		else if (!$kdnr)
		{
			echo $msg." ".$bestellung["BuyerName"]." fehlgeschlagen!<br>";
			continue;
		}
	}
	else	// Neukunde
	{
		$msg = "insert ";
		$kdnr = insert_neuen_Kunden($bestellung);
	}
	
	echo $bestellung["BuyerName"]." ".$bestellung["Name"]." $kdnr<br>";

	// Versandadressen immer eintragen (damit immer klar ist wohin eine Sendung verschickt wurde)
	$versandadressennummer = 0;
	if ($kdnr > 0)
	{
		// Neue Abfrage: wenn keine extra Shipping-Adresse vorhanden ist, dann wird Sie nun immer von der Rechnungsadresse übertragen
		if (trim($bestellung["recipient-name"]) == "")
		{
			$bestellung["recipient-title"] = $bestellung["Title"];
			$bestellung["recipient-name"] = $bestellung["Name"];
			$bestellung["ship-address-1"] = $bestellung["AddressLine1"];
			$bestellung["ship-address-2"] = $bestellung["AddressLine2"];
			$bestellung["ship-postal-code"] = $bestellung["PostalCode"];
			$bestellung["ship-city"] = $bestellung["City"];
			$bestellung["ship-state"] = $bestellung["StateOrRegion"];
			$bestellung["ship-country"] = $bestellung["CountryCode"];
		}
		$rc = insert_Versandadresse($bestellung, $kdnr);
		$versandadressennummer = $rc;
	}
	
	if (!$kdnr || $rc === -99)
	{
		echo $msg." ".$bestellung["BuyerName"]." fehlgeschlagen! ($kdnr, $rc)<br>";
		$rc = query("erp", "ROLLBACK WORK", "check_update_Kundendaten");
		if ($rc === -99)
		{
			echo "Probleme mit Transaktion. Abbruch!";
			exit();
		}
	}
	else
	{
		$rc = query("erp", "COMMIT WORK", "check_update_Kundendaten");
		if ($rc === -99)
		{
			echo "Probleme mit Transaktion. Abbruch!";
			exit();
		}
	}
	return $kdnr."|".$versandadressennummer;
}

/**********************************************
* check_update_Rechnungsadresse($bestellung)
***********************************************/
function check_update_Rechnungsadresse($bestellung)
{
	$rc = query("erp","BEGIN WORK","check_update_Rechnungsadresse");
	
	if ($rc === -99)
	{
		echo "Probleme mit Transaktion. Abbruch!";
		exit();
	}
	if (checkCustomer($bestellung['BuyerEmail'], $bestellung['BuyerName']) == "vorhanden")  // Bestandskunde; BuyerEmail (Amazon eindeutig) oder BuyerName vorhanden
	{
		$msg = "update ";
		$kdnr = checke_update_alte_Kundendaten($bestellung, true);
		if ($kdnr == -1 || !$kdnr)		// Kunde nicht gefunden, neu anlegen.
		{
			echo $msg." ".$bestellung["BuyerName"]." fehlgeschlagen!<br>";
			continue;
		}
	}
	
	echo $bestellung["BuyerName"]." ".$bestellung["Name"]." $kdnr<br>";

	if (!$kdnr || $rc === -99)
	{
		echo $msg." ".$bestellung["BuyerName"]." fehlgeschlagen! ($kdnr, $rc)<br>";
		$rc = query("erp", "ROLLBACK WORK", "check_update_Rechnungsadresse");
		if ($rc === -99)
		{
			echo "Probleme mit Transaktion. Abbruch!";
			exit();
		}
	}
	else
	{
		$rc = query("erp", "COMMIT WORK", "check_update_Rechnungsadresse");
		if ($rc === -99)
		{
			echo "Probleme mit Transaktion. Abbruch!";
			exit();
		}
	}
	return $kdnr;
}

/**********************************************
* checke_update_alte_Kundendaten($bestellung, $rechnungsadressenupdate)
***********************************************/
function checke_update_alte_Kundendaten($bestellung, $rechnungsadressenupdate)
{
	$sql = "select * from customer where ";
	if ($bestellung["BuyerEmail"] != "") { $sql .= "email = '".pg_escape_string($bestellung["BuyerEmail"])."'"; }
	if ($bestellung["BuyerEmail"] != "" && $bestellung["BuyerName"] != "") { $sql .= " OR "; }
	if ($bestellung["BuyerName"] != "") { $sql .= "user = '".pg_escape_string($bestellung["BuyerName"])."'"; }
	
	$rs = getAll("erp", $sql, "checke_update_alte_Kundendaten");
	
	if (!$rs || count($rs) != 1)	// Kunde nicht gefunden
	{
		return -1;
	}
	$set = "";
	// Wenn Kunde gefunden, ab hier die Kundendaten auf den neusten Stand bringen
	if($rechnungsadressenupdate)
	{
		$set.="notes=coalesce(notes, '') || '".pg_escape_string("\n[adressabgleich_".$bestellung['AmazonOrderId']."] ".$bestellung['PurchaseDate']."")."',";	
	}

	if ($rs[0]["name"] <> $bestellung["Name"])
	{
		$name = pg_escape_string($bestellung["Name"]);
		$set.="name='".$name."',";
	}
	if ($rs[0]["greeting"] <> $bestellung["Title"])
	{
		$set.="greeting='".pg_escape_string($bestellung["Title"])."',";
	}
	if ($bestellung["AddressLine2"] != "")
	{
		$department_1 = pg_escape_string($bestellung["AddressLine1"]);
		$street = pg_escape_string($bestellung["AddressLine2"]);
		if ($rs[0]["department_1"] <> $bestellung["AddressLine1"])
		{
			$set.="department_1='".$department_1."',";
		}
		if ($rs[0]["street"] <> $bestellung["AddressLine2"])
		{
			$set.="street='".$street."',";
		}
	}
	else 
	{
		$street = pg_escape_string($bestellung["AddressLine1"]);
		if ($rs[0]["street"] <> $bestellung["AddressLine1"])
		{
			$set.="department_1='',";
			$set.="street='".$street."',";
		}
	}
	if ($rs[0]["zipcode"] <> $bestellung["PostalCode"])
	{
		$set.="zipcode='".pg_escape_string(substr($bestellung["PostalCode"], 0, 10))."',";
	}
	if ($rs[0]["city"] <> $bestellung["City"])
	{
		$city = pg_escape_string($bestellung["City"]);
		$set.="city='".$city."',";
	}
	if (array_key_exists($bestellung["CountryCode"], $GLOBALS["LAND"]))
	{
		if ($rs[0]["country"] <> $GLOBALS["LAND"][$bestellung["CountryCode"]])
		{
			$set.="country='".pg_escape_string(utf8_encode($GLOBALS["LAND"][$bestellung["CountryCode"]]))."',";
		}
	}
	else
	{
		if ($rs[0]["country"] <> $bestellung["CountryCode"])
		{
			$set.="country='".pg_escape_string($bestellung["CountryCode"])."',";
		}
	}
	if (!empty($bestellung["Phone"]) && $rs[0]["phone"] <> $bestellung["Phone"])
	{
		$set.="phone='".pg_escape_string($bestellung["Phone"])."',";
	}
	if ($rs[0]["email"] <> $bestellung["BuyerEmail"])
	{
		$set.="email='".pg_escape_string($bestellung["BuyerEmail"])."',";
	}
	if (!empty($bestellung["BuyerName"]) && $rs[0]["username"] <> $bestellung["BuyerName"])
	{
		$set.="username='".pg_escape_string(substr($bestellung["BuyerName"], 0, 50))."',";
	}
	
	// Sprache setzen wenn vorhanden
	$sql= "select id from language where template_code = '".$bestellung["Language"]."'";
    $languagereturn = getAll("erp", $sql, "checke_update_alte_Kundendaten");
	$languageId = 0;
	if ($languagereturn != null && $languagereturn[0]["id"])	// LanguageCode vorhanden
	{
		$languageId = $languagereturn[0]["id"];
	}
	if ($languageId > 0)
	{
		if ($rs[0]["language_id"] <> $languageId)
		{
			$set .= "language_id=".$languageId.",";
		}
	}

	if (!empty($bestellung["tax_number"]))
	{
		$set .= "ustid='" . $bestellung["tax_number"] . "',";
	}
	
	$taxzonedata = getTaxzone($bestellung["CountryCode"], $bestellung["CountryCode"], $bestellung["tax_number"]);
	$localtaxid = getTaxzoneID($taxzonedata["NAME"]);
	$set .= "taxzone_id=$localtaxid ";

	if ($set)
	{
		$sql = "update customer set ".substr($set,0,-1)." where id=".$rs[0]["id"];
		$rc = query("erp", $sql, "checke_update_alte_Kundendaten");
		if ($rc === -99)
		{
			return false;
		}
		else
		{
			return $rs[0]["id"];
		}
	}
	else
	{
		return $rs[0]["id"];
	}
}

/**********************************************
* checke_ob_Kundendaten_aktuell($bestellung)
***********************************************/
function checke_ob_Kundendaten_aktuell($bestellung)
{
	$sql = "select * from customer where ";
	if ($bestellung["BuyerEmail"] != "") { $sql .= "email = '".pg_escape_string($bestellung["BuyerEmail"])."'"; }
	if ($bestellung["BuyerEmail"] != "" && $bestellung["BuyerName"] != "") { $sql .= " OR "; }
	if ($bestellung["BuyerName"] != "") { $sql .= "user = '".pg_escape_string(substr($bestellung["BuyerName"], 0, 50))."'"; }
	
	$rs = getAll("erp", $sql, "checke_ob_Kundendaten_aktuell");
	
	if (!$rs || count($rs) != 1)	// Kunde nicht gefunden
	{
		return "Kunde nicht gefunden";
	}
	$daten_aktuell = true;
	// Wenn Kunde gefunden, ab hier die Kundendaten auf den neusten Stand bringen
	if ($rs[0]["name"] <> $bestellung["Name"])
	{
		$daten_aktuell = false;
	}
	if ($rs[0]["greeting"] <> $bestellung["Title"])
	{
		$daten_aktuell = false;
	}
	if ($bestellung["AddressLine2"] != "")
	{
		if ($rs[0]["department_1"] <> $bestellung["AddressLine1"])
		{
			$daten_aktuell = false;
		}
		if ($rs[0]["street"] <> $bestellung["AddressLine2"])
		{
			$daten_aktuell = false;
		}
	}
	else 
	{
		if ($rs[0]["street"] <> $bestellung["AddressLine1"])
		{
			$daten_aktuell = false;
		}
	}
	if ($rs[0]["zipcode"] <> $bestellung["PostalCode"])
	{
		$daten_aktuell = false;
	}
	if ($rs[0]["city"] <> $bestellung["City"])
	{
		$daten_aktuell = false;
	}
	if (array_key_exists($bestellung["CountryCode"], $GLOBALS["LAND"]))
	{
		if ($rs[0]["country"] <> utf8_encode($GLOBALS["LAND"][$bestellung["CountryCode"]]))
		{
			$daten_aktuell = false;
		}
	}
	else
	{
		if ($rs[0]["country"] <> $bestellung["CountryCode"])
		{
			$daten_aktuell = false;
		}
	}
	if ($rs[0]["email"] <> $bestellung["BuyerEmail"])
	{
		$daten_aktuell = false;
	}

	$taxzonedata = getTaxzone($bestellung["CountryCode"], $bestellung["CountryCode"], $bestellung["tax_number"]);
	$localtaxid = getTaxzoneID($taxzonedata["NAME"]);

	if ($rs[0]["taxzone_id"] <> $localtaxid)
	{
		$daten_aktuell = false;
	}
	
	if ($rs[0]["ustid"] <> $bestellung["tax_number"])
	{
		$daten_aktuell = false;
	}

	return $daten_aktuell;
}

/**********************************************
* insert_neuen_Kunden($bestellung)
***********************************************/
function insert_neuen_Kunden($bestellung)
{
	$newID = uniqid(rand(time(),1));
	// Kundennummer generieren von der ERP
	$kdnr = naechste_freie_Kundennummer();

	$sql= "select count(*) as anzahl from customer where customernumber = '$kdnr'";
	$rs = getAll("erp", $sql, "insert_neuen_Kunden");
	if ($rs[0]["anzahl"] > 0)	// Kundennummer gibt es schon, eine neue aus ERP
	{
		$kdnr = naechste_freie_Kundennummer();
	}

    $sql= "select id from currencies where name = '".$bestellung["CurrencyCode"]."'";
    $rs = getAll("erp", $sql, "insert_neuen_Kunden");
	$currencyCode = 0;
	if ($rs[0]["id"])	// CurrencyCode vorhanden
	{
		$currencyCode = $rs[0]["id"];
	}
	
	$taxzonedata = getTaxzone($bestellung["CountryCode"], $bestellung["CountryCode"], $bestellung["tax_number"]);
	$localtaxid = getTaxzoneID($taxzonedata["NAME"]);

	$ustid = $bestellung["tax_number"];
	
	$sql = "insert into customer (name,customernumber,currency_id,taxzone_id,ustid) values ('$newID','$kdnr','$currencyCode','$localtaxid','$ustid')";
	$rc = query("erp", $sql, "insert_neuen_Kunden");
	if ($rc === -99)
	{
		return false;
	}
	
	$sql = "select * from customer where name = '$newID'";
	$rs = getAll("erp", $sql, "insert_neuen_Kunden");
	if (!$rs)
	{
		return false;
	}
	$name = pg_escape_string($bestellung["Name"]);
	$set = " name='".$name."',";
	if ($bestellung["Title"] != "")
	{
		$set .= "greeting='".pg_escape_string($bestellung["Title"])."',";
	}
	if ($bestellung["AddressLine2"] != "")
	{
		$department_1 = pg_escape_string($bestellung["AddressLine1"]);
		$street = pg_escape_string($bestellung["AddressLine2"]);
		$set .= "department_1='".$department_1."',";
		$set .= "street='".$street."',";
	}
	else 
	{
		$street = pg_escape_string($bestellung["AddressLine1"]);
		$set .= "street='".$street."',";
	}
	$set .= "zipcode='".substr($bestellung["PostalCode"], 0, 10)."',";
	$city = pg_escape_string($bestellung["City"]);
	$set .= "city='".$city."',";
	if (array_key_exists($bestellung["CountryCode"], $GLOBALS["LAND"]))
	{
		$set .= "country='".pg_escape_string(utf8_encode($GLOBALS["LAND"][$bestellung["CountryCode"]]))."',";
	}
	else
	{
		$set .= "country='".pg_escape_string($bestellung["CountryCode"])."',";
	}
	// Sprache setzen wenn vorhanden
	$sql= "select id from language where template_code = '".$bestellung["Language"]."'";
    $languagereturn = getAll("erp", $sql, "insert_neuen_Kunden");
	$languageId = 0;
	if ($languagereturn != null && $languagereturn[0]["id"])	// LanguageCode vorhanden
	{
		$languageId = $languagereturn[0]["id"];
	}
	if ($languageId > 0)
	{
		$set .= "language_id=".$languageId.",";
	}
	$set .= "phone='".pg_escape_string($bestellung["Phone"])."',";
	$set .= "email='".pg_escape_string($bestellung["BuyerEmail"])."',";
	$set .= "username='".pg_escape_string(substr($bestellung["BuyerName"], 0, 50))."' ";	

	$sql = "update customer set ".$set;
	$sql .= "where id=".$rs[0]["id"];
	$rc = query("erp", $sql, "insert_neuen_Kunden");
	if ($rc === -99)
	{
		return false;
	}
	else
	{
		return $rs[0]["id"];
	}
}

/**********************************************
* sku_ist_dienstleistung($abzufragende_sku)
***********************************************/
function sku_ist_dienstleistung($abzufragende_sku)
{
	$sql = "select inventory_accno_id from parts where partnumber='".preg_replace('/@.*/', '', $abzufragende_sku)."'";
	$rs2 = getAll("erp", $sql, "sku_ist_dienstleistung");
	if (Count($rs2) == 1)
	{
		// inventory_accno_id == NULL heisst SKU ist Dienstleistung
		if ($rs2[0]["inventory_accno_id"] == NULL)
		{
			return true;
		}
	}
	
	return false;
}

/**********************************************
* einfuegen_bestellte_Artikel($artikelliste, $AmazonOrderId, $zugehoerigeAuftragsID, $zugehoerigeAuftragsNummer)
***********************************************/
function einfuegen_bestellte_Artikel($artikelliste, $AmazonOrderId, $zugehoerigeAuftragsID, $zugehoerigeAuftragsNummer)
{
	require "conf.php";

	$ok = true;
	$GLOBALS["VERSANDKOSTEN"] = 0;
	$GLOBALS["GESCHENKVERPACKUNG"] = 0;
	$GLOBALS["ARTIKELPOSITION"] = 0;

	foreach ($artikelliste as $einzelartikel)
	{
		$sql = "select * from parts where partnumber='".$einzelartikel["SellerSKU"]."'";
		$rs2 = getAll("erp", $sql, "einfuegen_bestellte_Artikel");
		if ($rs2[0]["id"])
		{
			$artID = $rs2[0]["id"];
			$artNr = $rs2[0]["partnumber"];
			$ordnumber = $zugehoerigeAuftragsNummer;
			$lastcost = $rs2[0]["lastcost"];
			$text = $rs2[0]["description"];
			$longdescription = $rs2[0]["notes"];
			// Bei Zusatzartikeln ggf. Beschreibung korrigieren
			if ($einzelartikel['OrderItemId'] == "Zusatzartikel")
			{
				$longdescription = $einzelartikel['OrderItemId'];
				if (!empty($einzelartikel['Title']))
				{
					$text = $einzelartikel['Title'];
				}
			}
			$einzelpreis = round($einzelartikel["ItemPrice"] / $einzelartikel["QuantityOrdered"], 2, PHP_ROUND_HALF_UP) - round($einzelartikel["PromotionDiscount"] / $einzelartikel["QuantityOrdered"], 2, PHP_ROUND_HALF_UP);
			$GLOBALS["ARTIKELPOSITION"]++;
			
			$sql = "insert into orderitems (trans_id, ordnumber, parts_id, description, longdescription, qty, cusordnumber, sellprice, lastcost, unit, ship, discount, position, serialnumber) values (";
			$sql .= $zugehoerigeAuftragsID.","
					.$ordnumber.",'"
					.$artID."','"
					.pg_escape_string($text)."','"
					.pg_escape_string($longdescription)."',"
					.$einzelartikel["QuantityOrdered"].",'"
					.$AmazonOrderId."',"
					.$einzelpreis.","
					.$lastcost.","
					."'Stck',0,0,".$GLOBALS["ARTIKELPOSITION"].",'"
					.pg_escape_string($einzelartikel['SerialNumber'])."')";					
					
			echo " - Artikel:[ Artikel-ID:$artID Artikel-Nummer:<b>$artNr</b> ".$einzelartikel["Title"]." ]<br>";
			$rc = query("erp", $sql, "einfuegen_bestellte_Artikel");
			if ($rc === -99)
			{
				$ok = false;
				break;
			}
		}
		else if ($fehlendeSKU == "true")	// Artikel nicht im Kivitendo, -> Amazon-Werte übernehmen
		{
			$sql = "select id, partnumber from parts where partnumber='".$platzhalterFehlendeSKU."'";
			$rs3 = getAll("erp", $sql, "einfuegen_bestellte_Artikel");
			if ($rs3[0]["id"])
			{
				$artID = $rs3[0]["id"];
				$artNr = $rs3[0]["partnumber"]." (".$einzelartikel["SellerSKU"].")";
				$einzelpreis = round($einzelartikel["ItemPrice"] / $einzelartikel["QuantityOrdered"], 2, PHP_ROUND_HALF_UP) - round($einzelartikel["PromotionDiscount"] / $einzelartikel["QuantityOrdered"], 2, PHP_ROUND_HALF_UP);
				$text = $einzelartikel["Title"];
				$GLOBALS["ARTIKELPOSITION"]++;
				
				$sql = "insert into orderitems (trans_id, parts_id, description, qty, longdescription, sellprice, unit, ship, discount, position, serialnumber) values (";
				$sql .= $zugehoerigeAuftragsID.",'"
						.$artID."','"
						.pg_escape_string($text)."',"
						.$einzelartikel["QuantityOrdered"].",'"
						.$AmazonOrderId."',"
						.$einzelpreis.",'Stck',0,0,".$GLOBALS["ARTIKELPOSITION"].",'"
						.pg_escape_string($einzelartikel['SerialNumber'])."')";						
						
				echo " - Artikel:[ Artikel-ID:$artID Artikel-Nummer:<b>$artNr</b> ".$einzelartikel["Title"]." ]<br>";
				$rc = query("erp", $sql, "einfuegen_bestellte_Artikel");
				if ($rc === -99)
				{
					$ok = false;
					break;
				}
			}
		}
		$GLOBALS["VERSANDKOSTEN"] += $einzelartikel["ShippingPrice"] - abs($einzelartikel["ShippingDiscount"]);
		$GLOBALS["GESCHENKVERPACKUNG"] += $einzelartikel["GiftWrapPrice"];
	}
	return $ok;
}

/**********************************************
* hole_department_id($department_klarname)
***********************************************/
function hole_department_id($department_klarname)
{
	$sql = "select id from department where description='".$department_klarname."'";
	$abfrage = getAll("erp", $sql, "hole_department_id");
	if ($abfrage[0]["id"])
	{
		return $abfrage[0]["id"];
	}
	return "NULL";
}

/**********************************************
* hole_project_id($project_klarname)
***********************************************/
function hole_project_id($project_klarname)
{
	$sql = "select id from project where projectnumber='".$project_klarname."'";
	$abfrage = getAll("erp", $sql, "hole_project_id");
	if ($abfrage[0]["id"])
	{
		return $abfrage[0]["id"];
	}
	return "NULL";
}

/**********************************************
* hole_payment_id($zahlungsart)
***********************************************/
function hole_payment_id($zahlungsart)
{
	$sql = "select id from payment_terms where description='".$zahlungsart."'";
	$abfrage = getAll("erp", $sql, "hole_payment_id");
	if ($abfrage[0]["id"])
	{
		return $abfrage[0]["id"];
	}
	return "NULL";
}

/**********************************************
* erstelle_Auftrag($bestellung, $kundennummer, $versandadressennummer, $ERPusrID)
***********************************************/
function erstelle_Auftrag($bestellung, $kundennummer, $versandadressennummer, $ERPusrID)
{
	require "conf.php";
	
	// Hier beginnt die Transaktion
	$rc = query("erp","BEGIN WORK","erstelle_Auftrag");
	if ($rc === -99)
	{
		echo "Probleme mit Transaktion. Abbruch!"; exit();
	}
	$auftrag = naechste_freie_Auftragsnummer();

	$sql = "select count(*) as anzahl from oe where ordnumber = '$auftrag'";
	$rs = getAll("erp", $sql, "erstelle_Auftrag 1");
	if ($rs[0]["anzahl"] > 0)
	{
		$auftrag = naechste_freie_Auftragsnummer();
	}
	$newID = uniqid (rand());

        $sql= "select id from currencies where name = '".$bestellung["CurrencyCode"]."'";
        $rs = getAll("erp", $sql, "erstelle_Auftrag");
	$currencyCode = 0;
	if ($rs[0]["id"])	// CurrencyCode vorhanden
	{
		$currencyCode = $rs[0]["id"];
	}
	
	$brutto = $bestellung["Amount"];
	$netto = $bestellung["Amount"];
	
	// Steuerschluessel ermitteln, Nettobetrag errechnen
	$targetcountry = $bestellung["CountryCode"];
	if ($bestellung["ship-country"] != "")
	{
		$targetcountry = $bestellung["ship-country"];
	}
	$fulfillmentCenterIdData = checkFulfillmentCenterId($bestellung['fulfillment-center-id']);
	$shippingcountry = $notfallVersandland;
	if ($fulfillmentCenterIdData)
	{
		$shippingcountry = $fulfillmentCenterIdData['Country'];
	}
	$taxzonedata = getTaxzone($shippingcountry, $targetcountry, $bestellung["tax_number"]);
	$localtaxid = getTaxzoneID($taxzonedata["NAME"]);
	// Nettobetrag berechnen
	$netto = round($brutto / (1.0 + ($taxzonedata["RATE"]/100)) , 2, PHP_ROUND_HALF_UP);

	$sql = "insert into oe (notes,ordnumber,customer_id,currency_id,taxzone_id) values ('$newID','$auftrag','".$kundennummer."','$currencyCode','$localtaxid')";
	$rc = query("erp", $sql, "erstelle_Auftrag 2");
	if ($rc === -99)
	{
		echo "Auftrag ".$bestellung["AmazonOrderId"]." konnte nicht angelegt werden.<br>";
		$rc = query("erp", "ROLLBACK WORK", "erstelle_Auftrag");
		return false;
	}
	$sql = "select * from oe where notes = '$newID'";
	$rs2 = getAll("erp", $sql, "erstelle_Auftrag 3");
	if (!$rs2 > 0)
	{
		echo "Auftrag ".$bestellung["AmazonOrderId"]." konnte nicht angelegt werden.<br>";
		$rc = query("erp", "ROLLBACK WORK", "erstelle_Auftrag");
		return false;
	}

	$sql = "update oe set cusordnumber='".$bestellung["AmazonOrderId"]."', transdate='".$bestellung["PurchaseDate"]."', customer_id=".$kundennummer.", ";
	if ($versandadressennummer > 0)
	{
		$sql .= "shipto_id=".$versandadressennummer.", ";
	}
	$sql .= "department_id=".hole_department_id($bestellung["MarketplaceId"]).", ";
	
	$fulfillmentCenterIdData = checkFulfillmentCenterId($bestellung['fulfillment-center-id']);
	if ($fulfillmentCenterIdData)
	{
		$fulfillmentCenterText = $bestellung['fulfillment-center-id'] . " (" . $fulfillmentCenterIdData['City'] . ", " . $fulfillmentCenterIdData['Country'] . ")";
	}								
	else
	{
		$fulfillmentCenterText = $bestellung['fulfillment-center-id'] . " (unbekannt)";
	}
	$sql .= "shippingpoint='[Ort] ".utf8_encode($GLOBALS["VERSAND"][$bestellung["FulfillmentChannel"]] . "/ " . $fulfillmentCenterText . "/ " . $bestellung["ShipmentServiceLevelCategory"])."', ";
	$sql .= "globalproject_id=".hole_project_id($standardprojekt).", ";
	if ($taxzonedata["TYPE"] == $GLOBALS["EULAENDER"]["EU_MIT"] || $taxzonedata["TYPE"] == $GLOBALS["EULAENDER"]["WORLD"] || $bestellung["tax_included"] == "f")
	{
		$taxincluded = "f";
	}
	else
	{
		$taxincluded = "t";
	}
	$sql .= "amount=".$brutto.", netamount=".$netto.", reqdate='".$bestellung["LastUpdateDate"]."', taxincluded='".$taxincluded."', ";
	
	// Sprache setzen wenn vorhanden
	$sqllang= "select id from language where template_code = '".$bestellung["Language"]."'";
    $languagereturn = getAll("erp", $sqllang, "insert_neuen_Kunden");
	$languageId = 0;
	if ($languagereturn != null && $languagereturn[0]["id"])	// LanguageCode vorhanden
	{
		$languageId = $languagereturn[0]["id"];
	}
	if ($languageId > 0)
	{
		$sql .= "language_id=".$languageId.",";
	}
	
	// Versandinfos setzen wenn vorhanden
	if (trim($bestellung["carrier"]) != false)
	{
		$sql .= "shipvia='[porto] ".$bestellung["carrier"]."/".$bestellung["tracking-number"]."', ";
	}
	
	// Infos für EU-Umsatzsteuervoranmeldungen in Landeswährung, Währung ist immer die des Landes, in dem versteuert wird, ggf. werden hier die Daten mit dem generische Kurs umgerechnet
	$sql .= "transaction_description='" . generateVatInfoString($taxzonedata["NAME"], $brutto, $bestellung["CurrencyCode"], $documentnumber = "new") . "', ";
	
	$sql .= "taxzone_id=$localtaxid, ";
	if (hole_payment_id($bestellung["PaymentMethod"]) != "NULL")
	{
		$sql .= "payment_id=".hole_payment_id($bestellung["PaymentMethod"]).", ";  // Amazon
	}
	else
	{
		$sql .= "payment_id=".hole_payment_id($bestellung["PaymentMethodDetail"]).", ";
	}
	$waehrungstext = "";
	if ($bestellung["CurrencyCode"] != "EUR")
	{
		$waehrungstext = "[Originalbetrag] ".$bestellung["Amount"]." ".$bestellung["CurrencyCode"]." / [Kurs] 1 ".$bestellung["CurrencyCode"]." = x.xx EUR";
	}
	$kundenkommentar = pg_escape_string($bestellung["OrderComment"]);
	$sql .= "notes='".$kundenkommentar."', ";
	$sql .= "intnotes='"."[Bestelldatum] ".date("d.m.Y", strtotime($bestellung["PurchaseDate"])) . " / [Versanddatum] ".date("d.m.Y", strtotime($bestellung["LastUpdateDate"])).chr(13)
						."[SalesChannel] ".$bestellung["SalesChannel"]." (".$bestellung["CountryCode"].") / [Versand] ".utf8_encode($GLOBALS["VERSAND"][$bestellung["FulfillmentChannel"]]).chr(13)
						."[IsBusinessOrder] ".$bestellung["IsBusinessOrder"].chr(13)
						."[PaymentMethod] ".$bestellung["PaymentMethod"] . " " .$bestellung["PaymentMethodDetail"].chr(13)
						.$waehrungstext."', ";
	$sql .= "currency_id='".$currencyCode."', employee_id=".$ERPusrID.", vendor_id=NULL ";
	$sql .= "where id=".$rs2[0]["id"];
	
	$rc = query("erp",$sql,"erstelle_Auftrag 4");	
	if ($rc === -99)
	{
		echo "Auftrag ".$bestellung["AmazonOrderId"]." konnte nicht angelegt werden.<br>";
		$rc = query("erp", "ROLLBACK WORK", "erstelle_Auftrag");
		if ($rc === -99)
		{
			echo "Probleme mit Transaktion. Abbruch!"; exit();
		}
		return false;
	}
	echo "Auftrag:[ Buchungsnummer:".$rs2[0]["id"]." AuftragsNummer:<b>".$auftrag."</b> ]<br>";
	
	if (!einfuegen_bestellte_Artikel(array_values($bestellung['AmazonOrderIdProducts']), $bestellung["AmazonOrderId"], $rs2[0]["id"], $auftrag))
	{
		echo "Auftrag ".$bestellung["AmazonOrderId"]." konnte nicht angelegt werden.<br>";
		$rc = query("erp", "ROLLBACK WORK", "erstelle_Auftrag");
		if ($rc === -99)
		{
			echo "Probleme mit Transaktion. Abbruch!"; exit();
		}
		return false;
	}
	
	if ($GLOBALS["VERSANDKOSTEN"] > 0)
	{
		$sql = "select * from parts where partnumber='".$versandkosten."'";
		$rsversand = getAll("erp", $sql, "erstelle_Auftrag");
		
		if ($rsversand[0]["id"])
		{
			$artID = $rsversand[0]["id"];
			$artNr = $rsversand[0]["partnumber"];
			$einzelpreis = $GLOBALS["VERSANDKOSTEN"];
			$text = $rsversand[0]["description"];
			$sqlUebersetzung = "select * from translation where parts_id='".$rsversand[0]["id"]."' and language_id='".$languageId."'";
			$rsUebersetzung = getAll("erp", $sqlUebersetzung, "erstelle_Auftrag");
			if ($rsUebersetzung[0]["language_id"])
			{
				$text = pg_escape_string(strip_tags($rsUebersetzung[0]["longdescription"]));
			}
			$GLOBALS["ARTIKELPOSITION"]++;
		
			$sql = "insert into orderitems (trans_id, parts_id, description, qty, longdescription, sellprice, unit, ship, discount, position) values (";
			$sql .= $rs2[0]["id"].",'"	
					.$artID."','"
					.$text."',"
					."1,'"
					.$versandkosten."',"
					.$einzelpreis.",'Stck',0,0,".$GLOBALS["ARTIKELPOSITION"].")";
					
			echo " - Artikel:[ Artikel-ID:$artID Artikel-Nummer:<b>$artNr</b> ".$text." ]<br>";
			$rc = query("erp", $sql, "erstelle_Auftrag");
			if ($rc === -99)
			{
				echo "Auftrag $auftrag : Fehler bei den Versandkosten<br>";
			}
		}
	}
	
	if ($GLOBALS["GESCHENKVERPACKUNG"] > 0)
	{
		$sql = "select * from parts where partnumber='".$geschenkverpackung."'";
		$rsgeschenk = getAll("erp", $sql, "erstelle_Auftrag");
		if ($rsgeschenk[0]["id"])
		{
			$artID = $rsgeschenk[0]["id"];
			$artNr = $rsgeschenk[0]["partnumber"];
			$einzelpreis = $GLOBALS["GESCHENKVERPACKUNG"];
			$text = $rsgeschenk[0]["description"];
			$sqlUebersetzung = "select * from translation where parts_id='".$rsgeschenk[0]["id"]."' and language_id='".$languageId."'";
			$rsUebersetzung = getAll("erp", $sqlUebersetzung, "erstelle_Auftrag");
			if ($rsUebersetzung[0]["language_id"])
			{
				$text = pg_escape_string(strip_tags($rsUebersetzung[0]["longdescription"]));
			}
			$GLOBALS["ARTIKELPOSITION"]++;
			
			$sql = "insert into orderitems (trans_id, parts_id, description, qty, longdescription, sellprice, unit, ship, discount, position) values (";
			$sql .= $rs2[0]["id"].",'"
					.$artID."','"
					.$text."',"
					."1,'"
					.$geschenkverpackung."',"
					.$einzelpreis.",'Stck',0,0,".$GLOBALS["ARTIKELPOSITION"].")";
					
			echo " - Artikel:[ Artikel-ID:$artID Artikel-Nummer:<b>$artNr</b> ".$text." ]<br>";
			$rc = query("erp", $sql, "erstelle_Auftrag");
			if ($rc === -99)
			{
				echo "Auftrag $auftrag : Fehler bei den Geschenkverpackungskosten<br>";
			}
		}	
	}

	$rc = query("erp", "COMMIT WORK", "erstelle_Auftrag");
	if ($rc === -99)
	{
		echo "Probleme mit Transaktion. Abbruch!"; exit();
	}
	return true;
}

/**********************************************
* generateVatInfoString($taxzonename, $brutto, $bestellungCurrencyCode, $documentnumber = "new")
***********************************************/
function generateVatInfoString($taxzonename, $brutto, $bestellungCurrencyCode, $documentnumber = "new")
{
	$vatInfoString = "";
	
	$taxzonedata = getTaxzoneDataByName($taxzonename);
	
	// Nettobetrag berechnen
	$netto = round($brutto / (1.0 + ($taxzonedata["RATE"]/100)) , 2, PHP_ROUND_HALF_UP);

	// Infos für EU-Umsatzsteuervoranmeldungen in Landeswährung, Währung ist immer die des Landes, in dem versteuert wird, ggf. werden hier die Daten mit dem generische Kurs umgerechnet
	// Land für Versteuerung wird aus taxzone_id geholt
	// Daten werden entsprechend Vorgaben auf der Rechnung ausgegeben
	if ($bestellungCurrencyCode == $taxzonedata["CURR"])
	{
		$waehrungskurs = exchangerate($bestellungCurrencyCode, $waehrungspuffer);
		$vatInfoString = "[Document]".$documentnumber."[Land]".$taxzonedata["LAND"]."[Cur]".$bestellungCurrencyCode."[Kurs]".$waehrungskurs."[Brt]".number_format($brutto, 2, ',', '.')."[Net]".number_format($netto, 2, ',', '.')."[Ust]".number_format($brutto-$netto, 2, ',', '.');
	}
	else
	{
		$waehrungskurs = 1.00;
		if ($bestellungCurrencyCode == "EUR") // Keine Kreuzwährung, Rechnungsbetrag in EUR wird direkt in eine andere Landeswährung zur Versteuerung umgerechnet
		{
			$waehrungskurs = exchangerate($taxzonedata["CURR"], -1 * $waehrungspuffer); // Währungspuffer negativieren, damit der Fremdwährungssteuerbetrag im Steuerland zur Sicherheit etwas sinkt
			$brutto_lw = round($brutto * $waehrungskurs, 2, PHP_ROUND_HALF_UP);
			$netto_lw = round($netto * $waehrungskurs, 2, PHP_ROUND_HALF_UP);
		}
		else if ($taxzonedata["CURR"] == "EUR") // Keine Kreuzwährung, Rechnungsbetrag in Fremdwährung wird direkt in EUR Versteuerung umgerechnet
		{
			$waehrungskurs = exchangerate($bestellungCurrencyCode, $waehrungspuffer); // Währungspuffer so lassen, damit der zu meldende Wert im Steuerland zur Sicherheit etwas sinkt
			$brutto_lw = round($brutto / $waehrungskurs, 2, PHP_ROUND_HALF_UP);
			$netto_lw = round($netto / $waehrungskurs, 2, PHP_ROUND_HALF_UP);
		}
		else // Kreuzwährung, weder Bestellwährung noch Steuerzonenwährung ist EUR, Betrag zur Versteuerung wird über die beiden EUR-Kurse kreuzberechnet
		{
			$waehrungskurs_order = exchangerate($bestellungCurrencyCode, $waehrungspuffer / 2); // Währungspuffer halbieren, damit der zu meldende Wert im Steuerland zur Sicherheit etwas sinkt			
			$waehrungskurs_taxzone = exchangerate($taxzonedata["CURR"], -1 * $waehrungspuffer / 2); // Währungspuffer hablieren und negativieren, damit der Fremdwährungssteuerbetrag im Steuerland zur Sicherheit etwas sinkt
			$waehrungskurs = $waehrungskurs_order / $waehrungskurs_taxzone;
			$brutto_lw = round($brutto / $waehrungskurs, 2, PHP_ROUND_HALF_UP);
			$netto_lw = round($netto / $waehrungskurs, 2, PHP_ROUND_HALF_UP);
		}
		$vatInfoString = "[Document]".$documentnumber."[Land]".$taxzonedata["LAND"]."[Cur]".$taxzonedata["CURR"]."[Kurs]".$waehrungskurs."[Brt]".number_format($brutto_lw, 2, ',', '.')."[Net]".number_format($netto_lw, 2, ',', '.')."[Ust]".number_format($brutto_lw-$netto_lw, 2, ',', '.');
	}
	
	return $vatInfoString;
}

/**********************************************
* checkAmazonOrderId($AmazonOrderId)
***********************************************/
function checkAmazonOrderId($AmazonOrderId)
{
	require_once "DB.php";
	require "conf.php";
	
	$dsnP = array(
			'phptype'  => 'pgsql',
			'username' => $ERPuser,
			'password' => $ERPpass,
			'hostspec' => $ERPhost,
			'database' => $ERPdbname,
			'port'     => $ERPport
            );
            
	$status = "neu";
	
	$dbP = @DB::connect($dsnP);
	if (DB::isError($dbP)||!$dbP)
	{
		$status = "Keine Verbindung zur ERP<br>".$dbP->userinfo;
		$dbP = false;
	}
	else
	{
		// Auftraege checken
		$rs = $dbP->getall("select cusordnumber from oe where cusordnumber = '".$AmazonOrderId."'");
		if (count($rs) >= 1)
		{
			$status = "auftrag";
		}
		
		// Lieferscheine checken
		$rs = $dbP->getall("select cusordnumber from delivery_orders where cusordnumber = '".$AmazonOrderId."'");
		if (count($rs) >= 1)
		{
			$status = "lieferschein";
		}

		// Rechnungen checken
		$rs = $dbP->getall("select cusordnumber from ar where cusordnumber = '".$AmazonOrderId."'");
		if (count($rs) >= 1)
		{
			$status = "rechnung";
		}
		
		// Emails checken
		if ($status == "rechnung")
		{
			$rs = $dbP->getall("select cusordnumber from ar where cusordnumber = '".$AmazonOrderId."' and intnotes LIKE '%[email]%'");
			if (count($rs) >= 1)
			{
				$status = "email";
			}
		}
	}
	
	return $status;
}

/****************************************************
* checkFulfillmentCenterId() Versandzentren prüfen und Land holen
****************************************************/
function checkFulfillmentCenterId($testFulfillmentCenterId)
{
	require "conf.php";
	
	// FBA Versandzentren in Array umwandeln
	$fulfillmentCenters = array();
	foreach (explode("\n", $fulfillmentCenterIds) as $singleFulfillmentCenterId)
	{
		$zerlegteSingleFulfillmentCenterId = explode('|', $singleFulfillmentCenterId);
		if(count($zerlegteSingleFulfillmentCenterId) >= 2 && count($zerlegteSingleFulfillmentCenterId) <= 3)
		{
			$fulfillmentCenters[$zerlegteSingleFulfillmentCenterId[0]]['Country'] = trim($zerlegteSingleFulfillmentCenterId[1]);
			if(count($zerlegteSingleFulfillmentCenterId) == 3)
			{
				$fulfillmentCenters[$zerlegteSingleFulfillmentCenterId[0]]['City'] = trim($zerlegteSingleFulfillmentCenterId[2]);
			}
			else
			{
				$fulfillmentCenters[$zerlegteSingleFulfillmentCenterId[0]]['City'] = trim($zerlegteSingleFulfillmentCenterId[1]);
			}
		}
	}
	
	if(array_key_exists(trim(explode(';', $testFulfillmentCenterId)[0]), $fulfillmentCenters))
	{
		return $fulfillmentCenters[trim(explode(';', $testFulfillmentCenterId)[0])];
	}								
	else
	{
 		return false;
	}
}

/****************************************************
* getTaxzoneID($taxzoneName) tax_zones ID für Steuerzone holen
****************************************************/
function getTaxzoneID($taxzoneName)
{
	require_once "DB.php";
	require "conf.php";
	
	$dsnP = array(
			'phptype'  => 'pgsql',
			'username' => $ERPuser,
			'password' => $ERPpass,
			'hostspec' => $ERPhost,
			'database' => $ERPdbname,
			'port'     => $ERPport
            );
            
	$taxzone_id = -1;

	$dbP=@DB::connect($dsnP);
	
	if (DB::isError($dbP)||!$dbP)
	{
		echo "Keine Verbindung zur ERP<br>";
	}
	else
	{					
		$rs = $dbP->getall("select id from tax_zones where description = '".$taxzoneName."'");
		
		if (array_key_exists(0, $rs[0]))
		{
			$taxzone_id = $rs[0][0];
		}
	}

	return $taxzone_id;
}

/****************************************************
* getTaxzone($versandland, $zielland) Steuerzone holen
****************************************************/
function getTaxzone($versandland, $zielland, $tax_number)
{
	require "conf.php";
	
	$taxzones = array();
	$standardsteuerlandKuerzel = false;
	$taxzonename = false;
	$taxzonerate = 0;
	$taxzonetype = "";
	$taxzoneland = "";
	$taxzonecurrency = "";
	
	foreach (explode("\n", $Steuerlaender) as $number => $line)
	{
		$datenarray = explode("|", $line);
		if (trim($datenarray[0]) != false && trim($datenarray[1]) != false && trim($datenarray[2]) != false && trim($datenarray[3]) != false && trim($datenarray[4]) != false && trim($datenarray[5]) != false && trim($datenarray[6]) != false)
		{
			if ($number == 0)
			{
				$standardsteuerlandKuerzel = $datenarray[0];
			}
			$taxzones[$datenarray[0]] = array($datenarray[1], $datenarray[2], $datenarray[3], $datenarray[4], $datenarray[5], $datenarray[6]);
		}
	}

	$versandland_steuerland = false;
	if (array_key_exists($versandland, $taxzones))
	{
		$versandland_steuerland = true;
	}
	else
	{
		if ($standardsteuerlandKuerzel != false)
		{
			$versandland = $standardsteuerlandKuerzel;
			$versandland_steuerland = true;
		}
	}
	
	$zielland_steuerland = false;
	if (array_key_exists($zielland, $taxzones))
	{
		$zielland_steuerland = true;
	}
	
	if ($standardsteuerland == "versandland")
	{
		if ($versandland_steuerland == true)
		{
			if ($versandland == $zielland)
			{
				$taxzonename = $taxzones[$versandland][$GLOBALS["EULAENDER"]["INLAND"]];
				$taxzonerate = $taxzones[$versandland][$GLOBALS["EULAENDER"]["TAXRATE"]];
				$taxzonetype = $GLOBALS["EULAENDER"]["INLAND"];
				$taxzoneland = $versandland;
				$taxzonecurrency = $taxzones[$versandland][0];
			}
			else if (in_array($zielland, $GLOBALS["EULAENDER"]))
			{	
				$taxzonename = $taxzones[$versandland][$GLOBALS["EULAENDER"]["EU_OHNE"]];
				$taxzonerate = $taxzones[$versandland][$GLOBALS["EULAENDER"]["TAXRATE"]];
				$taxzonetype = $GLOBALS["EULAENDER"]["EU_OHNE"];
				$taxzoneland = $versandland;
				$taxzonecurrency = $taxzones[$versandland][0];
				if (trim($tax_number) != false)
				{
					$taxzonename = $taxzones[$versandland][$GLOBALS["EULAENDER"]["EU_MIT"]];
					$taxzonerate = 0;
					$taxzonetype = $GLOBALS["EULAENDER"]["EU_MIT"];
					$taxzoneland = $versandland;
					$taxzonecurrency = $taxzones[$versandland][0];
				}
			}
			else
			{
				$taxzonename = $taxzones[$versandland][$GLOBALS["EULAENDER"]["WORLD"]];	// Wenn nicht vorhanden, dann vermutlich Steuerschluessel Welt
				$taxzonerate = 0;
				$taxzonetype = $GLOBALS["EULAENDER"]["WORLD"];
				$taxzoneland = $versandland;
				$taxzonecurrency = $taxzones[$versandland][0];
			}
		}
		else
		{
			return $taxzonename;
		}
	}
	else
	{
		if ($zielland_steuerland == true)
		{
			if ($zielland == $versandland)
			{
				$taxzonename = $taxzones[$zielland][$GLOBALS["EULAENDER"]["INLAND"]];
				$taxzonerate = $taxzones[$zielland][$GLOBALS["EULAENDER"]["TAXRATE"]];
				$taxzonetype = $GLOBALS["EULAENDER"]["INLAND"];
				$taxzoneland = $zielland;
				$taxzonecurrency = $taxzones[$zielland][0];
			}
			else if ($versandland_steuerland == true || in_array($versandland, $GLOBALS["EULAENDER"]))
			{	
				$taxzonename = $taxzones[$zielland][$GLOBALS["EULAENDER"]["INLAND"]];
				$taxzonerate = $taxzones[$zielland][$GLOBALS["EULAENDER"]["TAXRATE"]];
				$taxzonetype = $GLOBALS["EULAENDER"]["INLAND"];
				$taxzoneland = $zielland;
				$taxzonecurrency = $taxzones[$zielland][0];
			}
		}
		else // Zielland nicht Steuerland, dann Steuer des Versandlandes nehmen
		{
			if ($versandland_steuerland == true)
			{
				if ($versandland == $zielland)
				{
					$taxzonename = $taxzones[$versandland][$GLOBALS["EULAENDER"]["INLAND"]];
					$taxzonerate = $taxzones[$versandland][$GLOBALS["EULAENDER"]["TAXRATE"]];
					$taxzonetype = $GLOBALS["EULAENDER"]["INLAND"];
					$taxzoneland = $versandland;
					$taxzonecurrency = $taxzones[$versandland][0];
				}
				else if (in_array($zielland, $GLOBALS["EULAENDER"]))
				{	
					$taxzonename = $taxzones[$versandland][$GLOBALS["EULAENDER"]["EU_OHNE"]];
					$taxzonerate = $taxzones[$versandland][$GLOBALS["EULAENDER"]["TAXRATE"]];
					$taxzonetype = $GLOBALS["EULAENDER"]["EU_OHNE"];
					$taxzoneland = $versandland;
					$taxzonecurrency = $taxzones[$versandland][0];
					if (trim($tax_number) != false)
					{
						$taxzonename = $taxzones[$versandland][$GLOBALS["EULAENDER"]["EU_MIT"]];
						$taxzonerate = 0;
						$taxzonetype = $GLOBALS["EULAENDER"]["EU_MIT"];
						$taxzoneland = $versandland;
						$taxzonecurrency = $taxzones[$versandland][0];
					}
				}
				else
				{
					$taxzonename = $taxzones[$versandland][$GLOBALS["EULAENDER"]["WORLD"]];	// Wenn nicht vorhanden, dann vermutlich Steuerschluessel Welt
					$taxzonerate = 0;
					$taxzonetype = $GLOBALS["EULAENDER"]["WORLD"];
					$taxzoneland = $versandland;
					$taxzonecurrency = $taxzones[$versandland][0];
				}
			}
			else
			{
				return $taxzonename;
			}
		}
	}
	
	return array( "NAME" => $taxzonename,	// Steuerzonen-Name, Steuerrate und Steuertype zurueckgeben
				  "RATE" => $taxzonerate,
				  "TYPE" => $taxzonetype,
				  "LAND" => $taxzoneland,
				  "CURR" => $taxzonecurrency);
}

/****************************************************
* getTaxzoneDataByName($taxzoneName) Steuerzone holen
****************************************************/
function getTaxzoneDataByName($taxzoneName)
{
	require "conf.php";
	
	$taxzones = array();

	$taxzonename = false;
	$taxzonerate = 0;
	$taxzonetype = "";
	$taxzoneland = "";
	$taxzonecurrency = "";
	
	foreach (explode("\n", $Steuerlaender) as $number => $line)
	{
		$datenarray = explode("|", $line);
		if (trim($datenarray[0]) != false && trim($datenarray[1]) != false && trim($datenarray[2]) != false && trim($datenarray[3]) != false && trim($datenarray[4]) != false && trim($datenarray[5]) != false && trim($datenarray[6]) != false)
		{
			$taxzones[$datenarray[0]] = array($datenarray[1], $datenarray[2], $datenarray[3], $datenarray[4], $datenarray[5], $datenarray[6]);
		}
		
		if (in_array($taxzoneName, $taxzones[$datenarray[0]]))
		{
			$taxzonename = $taxzoneName;
			$taxzonerate = $taxzones[$datenarray[0]][$GLOBALS["EULAENDER"]["TAXRATE"]];
			if ($taxzoneName == $datenarray[2])
			{
				$taxzonetype = "";
			}
			else if ($taxzoneName == $datenarray[3])
			{
				$taxzonetype = "";
			}
			else if($taxzoneName == $datenarray[4])
			{
				$taxzonetype = "";
			}
			else if ($taxzoneName == $datenarray[5])
			{
				$taxzonetype = "";
			}
			else
			{
				$taxzonetype = "";
			}
			$taxzoneland = $datenarray[0];
			$taxzonecurrency = $taxzones[$datenarray[0]][0];
			
			return array( "NAME" => $taxzonename,	// Steuerzonen-Name, Steuerrate und Steuertype zurueckgeben
						  "RATE" => $taxzonerate,
						  "TYPE" => $taxzonetype,
						  "LAND" => $taxzoneland,
						  "CURR" => $taxzonecurrency);			
		}
	}

	return  false;
}

/**********************************************
* checkPortostatusOfOrderId($OrderId)
***********************************************/
function checkPortostatusOfOrderId($CusOrdNumber, $OrderId = NULL)
{
	require_once "DB.php";
	require "conf.php";
	
	$dsnP = array(
			'phptype'  => 'pgsql',
			'username' => $ERPuser,
			'password' => $ERPpass,
			'hostspec' => $ERPhost,
			'database' => $ERPdbname,
			'port'     => $ERPport
            );
            
	$status = false;
	
	$dbP = @DB::connect($dsnP);
	if (DB::isError($dbP)||!$dbP)
	{
		$status = "Keine Verbindung zur ERP<br>".$dbP->userinfo;
		return $status;
	}
	else
	{
		// Auftraege checken
		if($OrderId == NULL || $OrderId == "")
		{
			$rs = $dbP->getall("select cusordnumber from oe where cusordnumber = '".$CusOrdNumber."' and shipvia LIKE '%[porto]%'");
		}
		else
		{
			$rs = $dbP->getall("select ordnumber from oe where ordnumber = '".$OrderId."' and shipvia LIKE '%[porto]%'");
		}
		if (count($rs) >= 1)
		{
			$status = true;
		}
	}
	
	return $status;
}

/**********************************************
* setPortostatusOfOrderId($CusOrdNumber, $Carrier, $TrackingNumber)
***********************************************/
function setPortostatusOfOrderId($CusOrdNumber, $Carrier, $TrackingNumber, $OrderId = NULL)
{
	require_once "DB.php";
	require "conf.php";
	
	$dsnP = array(
			'phptype'  => 'pgsql',
			'username' => $ERPuser,
			'password' => $ERPpass,
			'hostspec' => $ERPhost,
			'database' => $ERPdbname,
			'port'     => $ERPport
            );
            
	$status = false;
	
	$dbP = @DB::connect($dsnP);
	if (DB::isError($dbP)||!$dbP)
	{
		$status = "Keine Verbindung zur ERP<br>".$dbP->userinfo;
		$dbP = false;
	}
	else
	{
		// Daten in Auftrag eintragen
		if($OrderId == NULL || $OrderId == "")
		{
			$sql = "update oe set shipvia='[porto] ".$Carrier."/".$TrackingNumber."' where cusordnumber = '".$CusOrdNumber."'";
		}
		else
		{
			$sql = "update oe set shipvia='[porto] ".$Carrier."/".$TrackingNumber."' where ordnumber = '".$OrderId."'";
		}
		$rc = query("erp",$sql,"setPortostatusOfOrderId");	
		if ($rc === -99)
		{
			echo "Portostatus ".$CusOrdNumber." konnte nicht eingetragen werden.<br>";
			$rc = query("erp", "ROLLBACK WORK", "setPortostatusOfOrderId");
			if ($rc === -99)
			{
				echo "Probleme mit Transaktion. Abbruch!"; exit();
			}
			return false;
		}		
		$status = true;
	}
	
	return $status;
}

/**********************************************
* setPortostatusOfDeliveryOrderId($CusOrdNumber, $Carrier, $TrackingNumber, $Donumber)
***********************************************/
function setPortostatusOfDeliveryOrderId($CusOrdNumber, $Carrier, $TrackingNumber, $Donumber)
{
	require_once "DB.php";
	require "conf.php";
	
	$dsnP = array(
			'phptype'  => 'pgsql',
			'username' => $ERPuser,
			'password' => $ERPpass,
			'hostspec' => $ERPhost,
			'database' => $ERPdbname,
			'port'     => $ERPport
            );
            
	$status = false;
	
	$dbP = @DB::connect($dsnP);
	if (DB::isError($dbP)||!$dbP)
	{
		$status = "Keine Verbindung zur ERP<br>".$dbP->userinfo;
		$dbP = false;
	}
	else
	{
		// Daten in Lieferschein eintragen
		$sql = "update delivery_orders set shipvia='[porto] ".$Carrier."/".$TrackingNumber."' where donumber = '".$Donumber."'";
		$rc = query("erp",$sql,"setPortostatusOfDeliveryOrderId");	
		if ($rc === -99)
		{
			echo "Portostatus ".$CusOrdNumber." konnte nicht eingetragen werden.<br>";
			$rc = query("erp", "ROLLBACK WORK", "setPortostatusOfDeliveryOrderId");
			if ($rc === -99)
			{
				echo "Probleme mit Transaktion. Abbruch!"; exit();
			}
			return false;
		}		
		$status = true;
	}
	
	return $status;
}

/**********************************************
* checkCustomer($BuyerEmail, $BuyerName)
***********************************************/
function checkCustomer($BuyerEmail, $BuyerName)
{
	require_once "DB.php";
	require "conf.php";
	
	$dsnP = array(
			'phptype'  => 'pgsql',
			'username' => $ERPuser,
			'password' => $ERPpass,
			'hostspec' => $ERPhost,
			'database' => $ERPdbname,
			'port'     => $ERPport
            );
            
	$status = "neu";
	
	$dbP = @DB::connect($dsnP);
	if (DB::isError($dbP)||!$dbP)
	{
		$status = "Keine Verbindung zur ERP<br>".$dbP->userinfo;
		$dbP = false;
	}
	else if ($BuyerEmail == "" && $BuyerName == "")
	{
		$status = "-";
	}
	else
	{
		// Email checken
		if ($BuyerEmail != "")
		{
			$rs = $dbP->getall("select customernumber from customer where email = '".pg_escape_string($BuyerEmail)."'");
			if (count($rs) == 1)
			{
				$status = "vorhanden";
			}
		}

		if ($BuyerName != "")
		{
			// BuyerName checken
			$rs = $dbP->getall("select customernumber from customer where username = '".pg_escape_string(substr($BuyerName, 0, 50))."'");
			if (count($rs) == 1)
			{
				$status = "vorhanden";
			}
		}
	}
	
	return $status;
}

/**********************************************
* checkCustomerAdressabgleichErledigt($BuyerEmail, $BuyerName)
***********************************************/
function checkCustomerAdressabgleichErledigt($einzelbestellung)
{
	require_once "DB.php";
	require "conf.php";
	
	$dsnP = array(
			'phptype'  => 'pgsql',
			'username' => $ERPuser,
			'password' => $ERPpass,
			'hostspec' => $ERPhost,
			'database' => $ERPdbname,
			'port'     => $ERPport
            );
            
	$status = "Kunde nicht vorhanden";
	$kundennummer = -1;
	
	$dbP = @DB::connect($dsnP);
	if (DB::isError($dbP)||!$dbP)
	{
		$status = "Keine Verbindung zur ERP<br>".$dbP->userinfo;
		$dbP = false;
	}
	else if ($einzelbestellung['BuyerEmail'] == "" && $einzelbestellung['BuyerName'] == "")
	{
		$status = "Keine Eingabedaten";
	}
	else
	{
		// Email checken
		if ($einzelbestellung['BuyerEmail'] != "")
		{
			$rs = $dbP->getall("select customernumber from customer where email = '".pg_escape_string($einzelbestellung['BuyerEmail'])."'");
			if (count($rs) == 1)
			{
				$status = "vorhanden";
				$kundennummer = $rs[0][0];
			}
		}

		if ($einzelbestellung['BuyerName'] != "" && $status != "vorhanden")
		{
			// BuyerName checken
			$rs = $dbP->getall("select customernumber from customer where username = '".pg_escape_string(substr($bestellung["BuyerName"], 0, 50))."'");
			if (count($rs) == 1)
			{
				if ($kundennummer > -1 && $kundennummer != $rs[0][0])
				{
					$status = "Fehler: Mehrere passende Kundennummern gefunden";
				}
				else
				{
					$status = "vorhanden";
					$kundennummer = $rs[0][0];
				}
			}
		}
		
		if ($status == "vorhanden")
		{
			$adresseaktuell = checke_ob_Kundendaten_aktuell($einzelbestellung);
			$rs = $dbP->getall("select id from customer where customernumber = '".$kundennummer."' and notes LIKE '%[adressabgleich_".$einzelbestellung['AmazonOrderId']."]%'");
			if (count($rs) == 1 || $adresseaktuell)
			{
				$status = "ja|".$kundennummer;
			}
			else
			{
				$status = "nein|".$kundennummer;
			}
		}
	}
	
	return $status;
}

/**********************************************
* getSellingInfo($datum_von, $datum_bis)
***********************************************/
function convertToWindowsCharset($string)
{
	$charset =  mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true);
	$string =  mb_convert_encoding($string, "Windows-1252", $charset);
	return $string;
}
function getSellingInfo($datum_von, $datum_bis, $csvausgabe = false)
{
	require_once "DB.php";
	require "conf.php";
	
	$dsnP = array(
			'phptype'  => 'pgsql',
			'username' => $ERPuser,
			'password' => $ERPpass,
			'hostspec' => $ERPhost,
			'database' => $ERPdbname,
			'port'     => $ERPport
            );
            
	$dbP = @DB::connect($dsnP);
	if (DB::isError($dbP)||!$dbP)
	{
		$status = "Keine Verbindung zur ERP<br>".$dbP->userinfo;
		$dbP = false;
	}
	else
	{
		// Daten holen
		if ($csvausgabe == false)
		{
			$gruppierung = " saleschannel, artikel";
			$sortierung = " saleschannel, artikel";
			$selectanweisung = 	" trim(substring(ar.intnotes from E'SalesChannel]?(.*)\\\(..\\\)?')) AS saleschannel,"
								." trim(parts.partnumber) AS artikel,"
								." sum(CASE WHEN invoice.qty > 0 THEN invoice.qty ELSE 0 end) AS menge,"
								." sum(CASE WHEN invoice.qty < 0 THEN invoice.qty ELSE 0 end) AS returns";
		}
		else
		{
			$gruppierung = " saleschannel, artikelgruppe, abteilung, zielland, region";
			$sortierung = " artikelgruppe, saleschannel";
			$selectanweisung = 	" trim(partsgroup.partsgroup) AS artikelgruppe,"
								." regexp_split_to_array(trim(substring(ar.intnotes from E'Sales.*\\\(..\\\)?')), E' +') AS saleschannel,"
								." trim(department.description) AS abteilung,"
								." trim(customer.country) AS zielland,"
								." trim(tax_zones.description) AS region,"
								." sum(CASE WHEN invoice.qty > 0 THEN invoice.qty ELSE 0 end) AS menge,"
								." sum(CASE WHEN invoice.qty < 0 THEN invoice.qty ELSE 0 end) AS returns";
		}
		$rs = $dbP->getall("SELECT"
								.$selectanweisung
							." FROM"
								." ar"
							." INNER JOIN"
								." invoice ON ar.id = invoice.trans_id"
							." INNER JOIN"
								." customer ON ar.customer_id = customer.id"
							." INNER JOIN"
								." parts ON parts.id = invoice.parts_id"
							." INNER JOIN"
								." partsgroup ON partsgroup.id = parts.partsgroup_id"
							." LEFT OUTER JOIN"
								." department ON ar.department_id = department.id"
							." INNER JOIN"
								." tax_zones ON ar.taxzone_id = tax_zones.id"								
							." WHERE"
								." ar.transdate >= '".$datum_von."' AND ar.transdate <= '".$datum_bis."'"
							." GROUP BY"
								.$gruppierung
							." HAVING"
								." sum(CASE WHEN invoice.qty > 0 THEN invoice.qty ELSE 0 end) <> 0 OR sum(CASE WHEN invoice.qty < 0 THEN invoice.qty ELSE 0 end) <> 0" 
							." ORDER BY"
								.$sortierung);
		
		$returnvalue = array();
		
		foreach ($rs as $lfdNr => $zeile)
		{
			// var_dump($zeile); echo "<br>";
			if ($csvausgabe == false)
			{
				$newarray = explode(',', $zeile[0]);
				
				$returnvalue[$lfdNr][0] = $newarray[0];
				$returnvalue[$lfdNr][1] = $zeile[1];
				$returnvalue[$lfdNr][2] = $zeile[2];
				$returnvalue[$lfdNr][3] = $zeile[3];
			}
			else
			{
				$newarray = explode(',', $zeile[1]);
				
				$returnvalue[$lfdNr][0] = $zeile[0];
				$returnvalue[$lfdNr][1] = $newarray[1];
				$returnvalue[$lfdNr][2] = trim($newarray[2], "()}");
				$returnvalue[$lfdNr][3] = $zeile[2];
				$returnvalue[$lfdNr][4] = $zeile[3];
				$returnvalue[$lfdNr][5] = $zeile[4];
				$returnvalue[$lfdNr][6] = $zeile[5];
				$returnvalue[$lfdNr][7] = $zeile[6];
				$returnvalue[$lfdNr][8] = $zeile[7];
			}
		}
	}

	if ($csvausgabe == false)
	{
		return $returnvalue;
	}
	
	$csv_daten = array();
	$anzahl_zeilen = 0;
		
	$fp = fopen("table.csv", "rb");
	if(!$fp)
	{
		echo "Datei table.csv konnte nicht geoeffnet werden!\n";
	}
	else
	{
		while (($einzelzeile = fgetcsv($fp, 0, ";")) != FALSE)
		{
			$csv_daten[$anzahl_zeilen] = $einzelzeile;
			$anzahl_zeilen++;
		}
	}
	fclose($fp);

	$nichtgefunden = 0;
	foreach ($returnvalue as $lfdNr => $zeile)
	{
		$found = false;
		$returnfound = false;
		foreach ($csv_daten as $lfdNrCsv => $csvzeile)
		{
			if($found == false &&
				($zeile[1] == $csv_daten[$lfdNrCsv][0] ||
				(str_replace("EU mit", "EU ohne", $zeile[3].'.'.$zeile[5])) == $csv_daten[$lfdNrCsv][0]))
			{
				switch ($zeile[0]) {
				    case 'mobile':
				        $csv_daten[$lfdNrCsv][1] += $zeile[6];
				        break;
				    case 'cable':
				        $csv_daten[$lfdNrCsv][2] += $zeile[6];
				        break;
				    case 'micro':
				        $csv_daten[$lfdNrCsv][3] += $zeile[6];
				        break;
				    case 'mobile-hEar':
				        $csv_daten[$lfdNrCsv][4] += $zeile[6];
				        break;
				    case 'cable-hEar':
				        $csv_daten[$lfdNrCsv][5] += $zeile[6];
				        break;
				    case 'micro-hEar':
				        $csv_daten[$lfdNrCsv][6] += $zeile[6];
				        break;				        
					case 'ff-cable':
			        	$csv_daten[$lfdNrCsv][7] += $zeile[6];
				        break;
					case 'pmf-cable':
			        	$csv_daten[$lfdNrCsv][8] += $zeile[6];
				        break;				        
					case 'sonstiges':
			        	$csv_daten[$lfdNrCsv][9] += $zeile[6];
				        break;
					case '1921-cable':
			        	$csv_daten[$lfdNrCsv][10] += $zeile[6];
				        break;				        
					case 'mobile6-garde':
			        	$csv_daten[$lfdNrCsv][11] += $zeile[6];
				        break;
					case 'mobile6-garde-back':
			        	$csv_daten[$lfdNrCsv][12] += $zeile[6];
				        break;
					case 'mobile7-garde':
			        	$csv_daten[$lfdNrCsv][13] += $zeile[6];
				        break;
					case 'mobile7+-garde':
			        	$csv_daten[$lfdNrCsv][14] += $zeile[6];
				        break;
					case 'tablet-garde-9.7pro':
			        	$csv_daten[$lfdNrCsv][15] += $zeile[6];
			        	break;
					case 'pmf-mobile':
			        	$csv_daten[$lfdNrCsv][16] += $zeile[6];			        	
				        break;
				}
				$found = true;
			}
			if($returnfound == false &&
				(('Returns.'.$zeile[1]) == $csv_daten[$lfdNrCsv][0] ||
				(str_replace("EU mit", "EU ohne", 'Returns.'.$zeile[3].".".$zeile[5])) == $csv_daten[$lfdNrCsv][0]))
			{
				switch ($zeile[0]) {
				    case 'mobile':
				        $csv_daten[$lfdNrCsv][1] += $zeile[7];
				        break;
				    case 'cable':
				        $csv_daten[$lfdNrCsv][2] += $zeile[7];
				        break;
				    case 'micro':
				        $csv_daten[$lfdNrCsv][3] += $zeile[7];
				        break;
				    case 'mobile-hEar':
				        $csv_daten[$lfdNrCsv][4] += $zeile[7];
				        break;
				    case 'cable-hEar':
				        $csv_daten[$lfdNrCsv][5] += $zeile[7];
				        break;
				    case 'micro-hEar':
				        $csv_daten[$lfdNrCsv][6] += $zeile[7];
				        break;
					case 'ff-cable':
			        	$csv_daten[$lfdNrCsv][7] += $zeile[7];
				        break;
					case 'pmf-cable':
			        	$csv_daten[$lfdNrCsv][8] += $zeile[7];
				        break;
					case 'sonstiges':
			        	$csv_daten[$lfdNrCsv][9] += $zeile[7];
				        break;
					case '1921-cable':
			        	$csv_daten[$lfdNrCsv][10] += $zeile[7];
				        break;
					case 'mobile6-garde':
			        	$csv_daten[$lfdNrCsv][11] += $zeile[7];
				        break;
					case 'mobile6-garde-back':
			        	$csv_daten[$lfdNrCsv][12] += $zeile[7];
				        break;
					case 'mobile7-garde':
			        	$csv_daten[$lfdNrCsv][13] += $zeile[7];
				        break;
					case 'mobile7+-garde':
			        	$csv_daten[$lfdNrCsv][14] += $zeile[7];
				        break;
					case 'tablet-garde-9.7pro':
			        	$csv_daten[$lfdNrCsv][15] += $zeile[7];
				        break;
					case 'pmf-mobile':
			        	$csv_daten[$lfdNrCsv][16] += $zeile[7];			        	
				        break;
				}
				$returnfound = true;
			}
		}
		if ($found == false)
		{
			echo $zeile[0].";".$zeile[1].";".$zeile[2].";".$zeile[3].";".$zeile[4].";".$zeile[5].";".$zeile[6].";".$zeile[7]."\n";
			$nichtgefunden++;
		}
		if ($returnfound == false)
		{
			echo $zeile[0].";".$zeile[1].";".$zeile[2].";".$zeile[3].";".$zeile[4].";".$zeile[5].";".$zeile[6].";".$zeile[7]."\n";
			$nichtgefunden++;
		}
	}
	if ($nichtgefunden > 0)
	{
		echo "Nicht zugeordnet;".$nichtgefunden."\n\n";
	}
	
	$fileName = 'sellingdata.csv';

	header('Content-Description: File Transfer');
	header('Content-Encoding: Windows-1252');
	header('Content-type: text/csv; charset=Windows-1252');
	header('Content-Disposition: attachment; filename=' . $fileName);
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	
	foreach ($csv_daten as $zeile)
	{
		foreach ($zeile as $spalte)
		{
			echo convertToWindowsCharset($spalte.";");
		}
		echo "\n";
	}
	
	return $returnvalue;
}
?>
