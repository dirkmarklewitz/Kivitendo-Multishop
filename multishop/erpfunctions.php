<?php

require_once "DB.php";
require_once "MDB2.php";
require "conf.php";
require "constants.php";

$VERSANDKOSTEN = 0;
$GESCHENKVERPACKUNG = 0;

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

if ($SHOPchar and ExportMode != "1")
{
    $erp->setCharset($SHOPchar);
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
	
    if ($rs->message <> "")
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
		// Neue Abfrage: wenn keine extra Shipping-Adresse vorhanden ist, dann wird Sie nun immer von der Rechnungsadresse �bertragen
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
		$set.="username='".pg_escape_string($bestellung["BuyerName"])."',";
	}
	
	if (array_key_exists($bestellung["CountryCode"], $GLOBALS["TAXID"]))
	{	
		$localtaxid = $GLOBALS["TAXID"][$bestellung["CountryCode"]];
	}
	else
	{
		$localtaxid = 3;	// Wenn nicht vorhanden, dann vermutlich Steuerschluessel Welt
	}
	if ($rs[0]["taxzone_id"] <> $localtaxid)
	{
		$set .= "taxzone_id=$localtaxid ";
	}

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
	if ($bestellung["BuyerName"] != "") { $sql .= "user = '".pg_escape_string($bestellung["BuyerName"])."'"; }
	
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
	
	if (array_key_exists($bestellung["CountryCode"], $GLOBALS["TAXID"]))
	{	
		$localtaxid = $GLOBALS["TAXID"][$bestellung["CountryCode"]];
	}
	else
	{
		$localtaxid = 3;	// Wenn nicht vorhanden, dann vermutlich Steuerschluessel Welt
	}
	if ($rs[0]["taxzone_id"] <> $localtaxid)
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
	$sql= "insert into customer (name,customernumber) values ('$newID','$kdnr')";
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
	$set .= "set name='".$name."',";
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
	$set .= "phone='".pg_escape_string($bestellung["Phone"])."',";
	$set .= "email='".pg_escape_string($bestellung["BuyerEmail"])."',";
	$set .= "username='".pg_escape_string($bestellung["BuyerName"])."',";

	if (array_key_exists($bestellung["CountryCode"], $GLOBALS["TAXID"]))
	{	
		$localtaxid = $GLOBALS["TAXID"][$bestellung["CountryCode"]];
	}
	else
	{
		$localtaxid = 3;	// Wenn nicht vorhanden, dann vermutlich Steuerschluessel Welt
	}

	$set .= "taxzone_id=$localtaxid ";

	$sql = "update customer ".$set;
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
* einfuegen_bestellte_Artikel($artikelliste, $AmazonOrderId, $zugehoerigeAuftragsID, $zugehoerigeAuftragsNummer)
***********************************************/
function einfuegen_bestellte_Artikel($artikelliste, $AmazonOrderId, $zugehoerigeAuftragsID, $zugehoerigeAuftragsNummer)
{
	require "conf.php";

	$ok = true;
	$GLOBALS["VERSANDKOSTEN"] = 0;
	$GLOBALS["GESCHENKVERPACKUNG"] = 0;

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
			$longdescription = $rs2[0]["notes"];
			$einzelpreis = round($einzelartikel["ItemPrice"] / $einzelartikel["QuantityOrdered"], 2, PHP_ROUND_HALF_UP) - round($einzelartikel["PromotionDiscount"] / $einzelartikel["QuantityOrdered"], 2, PHP_ROUND_HALF_UP);
			$text = $rs2[0]["description"];
			
			$sql = "insert into orderitems (trans_id, ordnumber, parts_id, description, longdescription, qty, cusordnumber, sellprice, lastcost, unit, ship, discount) values (";
			$sql .= $zugehoerigeAuftragsID.","
					.$ordnumber.",'"
					.$artID."','"
					.pg_escape_string($text)."','"
					.pg_escape_string($longdescription)."',"
					.$einzelartikel["QuantityOrdered"].",'"
					.$AmazonOrderId."',"
					.$einzelpreis.","
					.$lastcost.","
					."'Stck',0,0)";
					
			echo " - Artikel:[ Artikel-ID:$artID Artikel-Nummer:<b>$artNr</b> ".$einzelartikel["Title"]." ]<br>";
			$rc = query("erp", $sql, "einfuegen_bestellte_Artikel");
			if ($rc === -99)
			{
				$ok = false;
				break;
			}
		}
		else if ($fehlendeSKU == "true")	// Artikel nicht im Kivitendo, -> Amazon-Werte �bernehmen
		{
			$sql = "select id, partnumber from parts where partnumber='".$platzhalterFehlendeSKU."'";
			$rs3 = getAll("erp", $sql, "einfuegen_bestellte_Artikel");
			if ($rs3[0]["id"])
			{
				$artID = $rs3[0]["id"];
				$artNr = $rs3[0]["partnumber"]." (".$einzelartikel["SellerSKU"].")";
				$einzelpreis = round($einzelartikel["ItemPrice"] / $einzelartikel["QuantityOrdered"], 2, PHP_ROUND_HALF_UP) - round($einzelartikel["PromotionDiscount"] / $einzelartikel["QuantityOrdered"], 2, PHP_ROUND_HALF_UP);
				$text = $einzelartikel["Title"];
				
				$sql = "insert into orderitems (trans_id, parts_id, description, qty, longdescription, sellprice, unit, ship, discount) values (";
				$sql .= $zugehoerigeAuftragsID.",'"
						.$artID."','"
						.pg_escape_string($text)."',"
						.$einzelartikel["QuantityOrdered"].",'"
						.$AmazonOrderId."',"
						.$einzelpreis.",'Stck',0,0)";
						
				echo " - Artikel:[ Artikel-ID:$artID Artikel-Nummer:<b>$artNr</b> ".$einzelartikel["Title"]." ]<br>";
				$rc = query("erp", $sql, "einfuegen_bestellte_Artikel");
				if ($rc === -99)
				{
					$ok = false;
					break;
				}
			}
		}
		$GLOBALS["VERSANDKOSTEN"] += $einzelartikel["ShippingPrice"] - $einzelartikel["ShippingDiscount"];
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
	
	$brutto = $bestellung["Amount"];
	$netto = round($brutto / 1.19, 2, PHP_ROUND_HALF_UP);
	
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
	$sql = "insert into oe (notes,ordnumber,customer_id) values ('$newID','$auftrag','".$kundennummer."')";
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
	$sql .= "department_id=".hole_department_id($bestellung["MarketplaceId"]).", shippingpoint='".utf8_encode($GLOBALS["VERSAND"][$bestellung["FulfillmentChannel"]])."', ";
	$sql .= "amount=".$brutto.", netamount=".$netto.", reqdate='".$bestellung["LastUpdateDate"]."', taxincluded='t', ";
	// Versandadresse pr�fen (selbige gibt wenn vorhanden den Steuerschluessel vor!
	if ($bestellung["ship-country"] != "")
	{
		if (array_key_exists($bestellung["ship-country"], $GLOBALS["TAXID"]))
		{	
			$localtaxid = $GLOBALS["TAXID"][$bestellung["ship-country"]];
		}
		else
		{
			$localtaxid = 3;	// Wenn nicht vorhanden, dann vermutlich Steuerschluessel Welt
		}
	}
	else
	{
		if (array_key_exists($bestellung["CountryCode"], $GLOBALS["TAXID"]))
		{	
			$localtaxid = $GLOBALS["TAXID"][$bestellung["CountryCode"]];
		}
		else
		{
			$localtaxid = 3;	// Wenn nicht vorhanden, dann vermutlich Steuerschluessel Welt
		}
	}
	$sql .= "taxzone_id=$localtaxid, ";
	$sql .= "payment_id=".hole_payment_id($bestellung["PaymentMethod"]).", ";
	$bestelldatum = "Bestelldatum: ".date("d.m.Y", strtotime($bestellung["PurchaseDate"])).chr(13);
	$versanddatum = "Versanddatum: ".date("d.m.Y", strtotime($bestellung["LastUpdateDate"])).chr(13);
	if ($bestellung["CurrencyCode"] != "EUR")
	{
		$waehrungstext = chr(13)."Originalwaehrung: ".$bestellung["CurrencyCode"].chr(13)."Originalbetrag: ".$bestellung["Amount"]." ".$bestellung["CurrencyCode"].chr(13)."Kurs 1 ".$bestellung["CurrencyCode"]." = x.xx EUR";
	}
	$kundenkommentar = pg_escape_string($bestellung["OrderComment"]);
	$sql .= "notes='".$kundenkommentar."', intnotes='".$bestelldatum.$versanddatum."SalesChannel ".$bestellung["SalesChannel"]." (".$bestellung["CountryCode"].")".chr(13)."Versand durch ".utf8_encode($GLOBALS["VERSAND"][$bestellung["FulfillmentChannel"]]).$waehrungstext."', ";
	$sql .= "curr='".$bestellung["CurrencyCode"]."', employee_id=".$ERPusrID.", vendor_id=NULL ";
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
			
			$sql = "insert into orderitems (trans_id, parts_id, description, qty, longdescription, sellprice, unit, ship, discount) values (";
			$sql .= $rs2[0]["id"].",'"
					.$artID."','"
					.$text."',"
					."1,'"
					.$versandkosten."',"
					.$einzelpreis.",'Stck',0,0)";
					
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
		$rsversand = getAll("erp", $sql, "erstelle_Auftrag");
		if ($rsversand[0]["id"])
		{
			$artID = $rsversand[0]["id"];
			$artNr = $rsversand[0]["partnumber"];
			$einzelpreis = $GLOBALS["GESCHENKVERPACKUNG"];
			$text = $rsversand[0]["description"];
			
			$sql = "insert into orderitems (trans_id, parts_id, description, qty, longdescription, sellprice, unit, ship, discount) values (";
			$sql .= $rs2[0]["id"].",'"
					.$artID."','"
					.$text."',"
					."1,'"
					.$geschenkverpackung."',"
					.$einzelpreis.",'Stck',0,0)";
					
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
			$rs = $dbP->getall("select customernumber from customer where username = '".pg_escape_string($BuyerName)."'");
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
			$rs = $dbP->getall("select customernumber from customer where username = '".pg_escape_string($einzelbestellung['BuyerName'])."'");
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
		$rs = $dbP->getall("SELECT"
								." trim(partsgroup.partsgroup) AS artikelgruppe,"
								." regexp_split_to_array(trim(substring(ar.intnotes from E'Sales.*\\\(..\\\)?')), E' +') AS saleschannel,"
								." trim(department.description) AS abteilung,"
								." trim(customer.country) AS zielland,"
								." trim(tax_zones.description) AS region,"
								." sum(CASE WHEN invoice.qty > 0 THEN invoice.qty ELSE 0 end) AS menge,"
								." sum(CASE WHEN invoice.qty < 0 THEN invoice.qty ELSE 0 end) AS returns"
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
								." saleschannel, artikelgruppe, abteilung, zielland, region"
							." HAVING"
								." sum(CASE WHEN invoice.qty > 0 THEN invoice.qty ELSE 0 end) <> 0 OR sum(CASE WHEN invoice.qty < 0 THEN invoice.qty ELSE 0 end) <> 0" 
							." ORDER BY"
								." artikelgruppe, saleschannel");
		
		$returnvalue = array();
		
		foreach ($rs as $lfdNr => $zeile)
		{
			// var_dump($zeile); echo "<br>";
			
			$newarray = explode(',', $zeile[1]);
			
			$returnvalue[$lfdNr][0] = $zeile[0];
			$returnvalue[$lfdNr][1] = $newarray[1];
			$returnvalue[$lfdNr][2] = trim($newarray[2], "()}");
			$returnvalue[$lfdNr][3] = $zeile[2];
			$returnvalue[$lfdNr][4] = $zeile[3];
			$returnvalue[$lfdNr][5] = $zeile[4];
			$returnvalue[$lfdNr][6] = $zeile[5];
			$returnvalue[$lfdNr][7] = $zeile[6];

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