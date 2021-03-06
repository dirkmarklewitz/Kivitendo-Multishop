<?php
if (!isset($_SERVER['PHP_AUTH_USER']))
{
	Header("WWW-Authenticate: Basic realm=\"Portoerstellung\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "Sie m&uuml;ssen sich autentifizieren\n";
	exit;
}
else
{
	require "../conf.php";

	if ($_SERVER['PHP_AUTH_USER']<>$ERPftpuser || $_SERVER['PHP_AUTH_PW']<>$ERPftppwd)
	{
		Header("WWW-Authenticate: Basic realm=\"Portoerstellung\"");
		Header("HTTP/1.0 401 Unauthorized");
		echo "Sie m&uuml;ssen sich autentifizieren\n";
		exit;
	}

	require_once "./prepare-shipment-class.php";
	require_once "./php-mailer/class.phpmailer.php";
	require_once "../erpfunctions.php";
	
	if (isset($_GET["cusordnumber"]))
	{
		$cusordnumber = $_GET["cusordnumber"];
		$order_id = $_GET["order_id"];
		$donumber; if (isset($_GET["donumber"])) { $donumber = $_GET["donumber"]; }
		$db_document_id = $_GET["db_document_id"];
		$customer = array ( 'name'			=> trim($_GET["name"]),
						    'department'	=> trim($_GET["department"]),
						    'street'		=> trim($_GET["street"]),
						    'postcode'		=> trim($_GET["postcode"]),
						    'city'			=> trim($_GET["city"]),
						    'country_ISO_code' => trim($_GET["country_ISO_code"]),
						    'country'		=> trim($_GET["country"]),
						    'telephone'		=> trim($_GET["telephone"]),
						    'email'			=> trim($_GET["email"]));
		$export_details = array
		(
	        'customs_currency' => trim($_GET["customs_currency"]),
		);
	
		for($zaehler = 0; $zaehler < $_GET["count"]; $zaehler++)
		{
			$bestelldaten = explode("|", trim($_GET[$zaehler]));
			
			if(!sku_ist_dienstleistung($bestelldaten[0]))
			{
				$export_details['positions'][$zaehler] = array ('sku'				=> explode("@", trim($bestelldaten[0]))[0],
																'item_amount'		=> $bestelldaten[1],
																'customs_value'		=> floatval(str_replace('.', '', substr($bestelldaten[2], 0, strlen($bestelldaten[2])-3)) . str_replace(',', '.', substr($bestelldaten[2], strlen($bestelldaten[2])-3))),
																'goods_description' => $bestelldaten[3]);
			}
															
			$export_details['emailpositions'][$zaehler] = array ('sku'				=> $bestelldaten[0],
																'item_amount'		=> $bestelldaten[1],
																'customs_value'		=> floatval(str_replace('.', '', substr($bestelldaten[2], 0, strlen($bestelldaten[2])-3)) . str_replace(',', '.', substr($bestelldaten[2], strlen($bestelldaten[2])-3))),
																'goods_description' => $bestelldaten[3]);															
		}
	}
	elseif (isset($_POST["cusordnumber"]))
	{
		$warenruecksendung = $_POST["warenruecksendung"];
		$cusordnumber = $_POST["cusordnumber"];
		$order_id = $_POST["order_id"];
		$donumber; if (isset($_POST["donumber"])) { $donumber = $_POST["donumber"]; }
		$db_document_id = $_POST["db_document_id"];
		$customer = array ( 'name'			=> $_POST["name"],
						    'department'	=> $_POST["department"],
						    'street'		=> $_POST["street"],
						    'postcode'		=> $_POST["postcode"],
						    'city'			=> $_POST["city"],
						    'country_ISO_code' => $_POST["country_ISO_code"],
						    'country'		=> $_POST["country"],
						    'telephone'		=> $_POST["telephone"],
						    'email'			=> $_POST["email"]);
		$export_details = array
		(
	        'customs_currency' => $_POST["customs_currency"],
		);
		
		if ($warenruecksendung == "checked")
		{
			$export_details['return'] = 1;
		}
		else
		{
			$export_details['return'] = 0;
		}
	
		$bestelldaten = explode("\n", $_POST["bestelldaten"]);
		foreach ($bestelldaten as $zaehler => $einzelartikel)
		{
			$einzelartikelarray = explode("|", $einzelartikel);
			if (sizeof($einzelartikelarray) > 1 && !sku_ist_dienstleistung($einzelartikelarray[0]))
			{
				$export_details['positions'][$zaehler] = array ('sku'				=> explode("@", trim($einzelartikelarray[0]))[0],
																'item_amount'		=> $einzelartikelarray[1],
																'customs_value'		=> floatval($einzelartikelarray[2]),
																'goods_description' => $einzelartikelarray[3]);
			}
			
			if (sizeof($einzelartikelarray) > 1)
			{
				$export_details['emailpositions'][$zaehler] = array ('sku'				=> $einzelartikelarray[0],
																'item_amount'		=> $einzelartikelarray[1],
																'customs_value'		=> floatval($einzelartikelarray[2]),
																'goods_description' => $einzelartikelarray[3]);
			}
		}
		$portoempfaenger = $_POST["portoempfaenger"];
	}
		
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/transitional.dtd\">";
	echo "<html>";
	echo "<head>";
	echo "	<title>Portoerstellung</title>";
	echo "	<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
	echo "</head>";
	echo "<body>";	
	echo "<form name=\"gesamtformular\" action=\"porto.php\" method=\"post\">";
	
	echo	"<table style=\"background-color:#cccccc\">"
				."<tr><td>Exportdokumente fuer Warenruecksendung</td><td><input type=\"checkbox\" name=\"warenruecksendung\" value=\"checked\""; if ($warenruecksendung == "checked") { echo "checked=\"checked\""; }; echo "></td></tr>"
				."<tr><td>Bestellnummer des Kunden</td><td><input type=\"text\" size=\"40\" name=\"cusordnumber\" value=\"".$cusordnumber."\"></td></tr>"
				."<tr><td>Auftragsnummer</td><td><input type=\"text\" size=\"40\" name=\"order_id\" value=\"".$order_id."\"></td></tr>"
				."<tr><td>Lieferscheinnummer</td><td><input type=\"text\" size=\"40\" name=\"donumber\" value=\"".$donumber."\"></td></tr>"
				."<tr><td>Interne Dokumentennummer</td><td><input type=\"text\" size=\"40\" name=\"db_document_id\" value=\"".$db_document_id."\"></td></tr>"
				."<tr><td>Name</td><td><input type=\"text\" size=\"40\" name=\"name\" value=\"".$customer['name']."\"></td></tr>"
				."<tr><td>Strasse 1</td><td><input type=\"text\" size=\"40\" name=\"department\" value=\"".$customer['department']."\"></td></tr>"
				."<tr><td>Strasse 2</td><td><input type=\"text\" size=\"40\" name=\"street\" value=\"".$customer['street']."\"></td></tr>"
				."<tr><td>PLZ</td><td><input type=\"text\" size=\"10\" name=\"postcode\" value=\"".$customer['postcode']."\"></td></tr>"
				."<tr><td>Stadt</td><td><input type=\"text\" size=\"40\" name=\"city\" value=\"".$customer['city']."\"></td></tr>"
				."<tr><td>Laendercode</td><td><input type=\"text\" size=\"10\" name=\"country_ISO_code\" value=\"".$customer['country_ISO_code']."\"></td></tr>"
				."<tr><td>Land</td><td><input type=\"text\" size=\"20\" name=\"country\" value=\"".$customer['country']."\"></td></tr>"
				."<tr><td>Telefon</td><td><input type=\"text\" size=\"20\" name=\"telephone\" value=\"".$customer['telephone']."\"></td></tr>"
				."<tr><td>Email</td><td><input type=\"text\" size=\"40\" name=\"email\" value=\"".$customer['email']."\"></td></tr>"
				."<tr><td>Waehrung</td><td><input type=\"text\" size=\"10\" name=\"customs_currency\" value=\"".$export_details['customs_currency']."\"></td></tr>"
		 		."<tr><td>Bestelldaten<br>(Die ersten 10 werden uebernommen)</td><td><textarea name=\"bestelldaten\" cols=\"150\" rows=\"8\">";
	foreach ($export_details['emailpositions'] as $einzelartikel)
	{
		echo $einzelartikel['sku']."|".$einzelartikel['item_amount']."|".$einzelartikel['customs_value']."|".$einzelartikel['goods_description']."\n";
	}
	echo "</textarea></td></tr>"
		."<tr><td>-----------------------</td><td>-------------------------------------------</td></tr>"

		."<tr><td>Email Portoempfaenger</td><td><input type=\"text\" size=\"40\" name=\"portoempfaenger\" value=\"".$portoemail."\"></td></tr>";
	echo "</table>";
	if (!isset($_POST["portoerstellen"]) && (!checkPortostatusOfOrderId($cusordnumber, $order_id) || !empty($db_document_id)))
	{
		echo "<br><input type=\"submit\" name=\"portoerstellen\" value=\"Porto erstellen\">";
		echo "</form>";
	}
	else
	{
		echo "</form>";
		if (!empty($donumber))
		{
			// Lieferschein
			echo "<form name=\"zurueckzukivi\" action=\"https://www.opis-tech.com/kivitendo/do.pl?action=edit&type=sales_delivery_order&id=".$db_document_id."\" target=\"_self\" method=\"post\">";
		}
		else
		{
			// Auftrag
			echo "<form name=\"zurueckzukivi\" action=\"https://www.opis-tech.com/kivitendo/oe.pl?action=edit&type=sales_order&vc=customer&id=".$db_document_id."\" target=\"_self\" method=\"post\">";			
		}
		if (checkPortostatusOfOrderId($cusordnumber, $order_id) && empty($db_document_id))
		{
			echo "<br> Porto bereits erstellt, Vorgang abgebrochen <br>";
		}
		else
		{
			$shipment = new PrepareShipment($cusordnumber);
			
	 		$response = $shipment->handle_shipment($export_details, $customer);

	 		echo "<br> Porto erstellt <br>";
			echo "<br> Status         : ".$response['status'];
			echo "<br> Error Message  : ".$response['error_message'];
			echo "<br> Warning Message: ".$response['warning_message'];
			echo "<br> Type           : ".$response['type'];
			echo "<br> Carrier        : ".$response['carrier'];
			echo "<br> Shipment Number: ".$response['shipment_number'];
			echo "<br> Shipment Label : ".$response['shipment_label_file'];
			echo "<br> Export Document: ".$response['export_document_file']."<br>";
			
			if ($response['status'] == 0)
			{
				if (!empty($donumber))
				{
					// Lieferschein
					setPortostatusOfDeliveryOrderId($cusordnumber, $response['carrier'], $response['shipment_number'], $donumber);
				}
				else
				{
					// Auftrag
					setPortostatusOfOrderId($cusordnumber, $response['carrier'], $response['shipment_number'], $order_id);
				}
				
	 			$email = new PHPMailer();
	 			$email->From      = 'porto@opis-tech.com';
	 	 	    $email->FromName  = 'Porto DHL Post';
	 	 	    $email->Subject   = '[auto versand] '; 
	 	 	    foreach($export_details['emailpositions'] as $position)
	 	 	    {
	 	 			$email->Subject .= $position['item_amount']."#".$position['sku']." | ";
	 	 	    }
	 	 	    $email->Body      = "Customer Ord.No.: " . $cusordnumber . "\n\n";
	 	 	    $email->Body     .= "Shipment Carrier: " . $response['carrier']."\nShipment Number : " . $response['shipment_number'] . "\n\n";
	 	 	    $email->Body     .= "Recipient       : " . $customer['name'] . "\n";
	 	 	    $email->Body     .= "                : " . $customer['department'] . "\n";
	 	 	    $email->Body     .= "                : " . $customer['street'] . "\n";
	 	 	    $email->Body     .= "                : " . $customer['postcode'] . " " . $customer['city'] . "\n";
	 	 	    $email->Body     .= "                : " . $customer['country_ISO_code'] . "\n";
	 	 	    
	 	 	    $email->AddAddress( $portoempfaenger );
	 	 	    
	 	 	    $email->AddAttachment( LABEL_DOWNLOAD_PATH . $response['shipment_label_file'] );
	 		    if (array_key_exists('export_document_file', $response) && strlen($response['export_document_file']))
	 	 	    {
	 	 			$email->AddAttachment( LABEL_DOWNLOAD_PATH . $response['export_document_file'] );
	 	 		}
	 	 		
	 	 		$email->Send();
	 	 		
	 	 		if (!empty($db_document_id))
	 	 		{
	 	 			echo "<script language=\"javascript\">";
					echo "document.zurueckzukivi.submit();";
					echo "</script>";
				}
		 	}
		 	else
		 	{
			 	if (!empty($db_document_id))
			 	{
					echo "<br><input type=\"submit\" name=\"zurueckzukivitendo\" value=\"Zurueck zu Kivitendo\">";
			 	}
		 	}
		}
	}
	echo "</body>";
	echo "</html>";
}
?>
