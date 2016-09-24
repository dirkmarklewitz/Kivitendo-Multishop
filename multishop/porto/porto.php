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
		$customer = array ( 'name'			=> $_GET["name"],
						    'department'	=> $_GET["department"],
						    'street'		=> $_GET["street"],
						    'postcode'		=> $_GET["postcode"],
						    'city'			=> $_GET["city"],
						    'country_ISO_code' => $_GET["country_ISO_code"],
						    'country'		=> $_GET["country"],
						    'telephone'		=> $_GET["telephone"],
						    'email'			=> $_GET["email"]);
		$export_details = array
		(
	        'customs_currency' => $_GET["customs_currency"],
		);
	
		for($zaehler = 0; $zaehler < $_GET["count"]; $zaehler++)
		{
			$bestelldaten = explode("|", $_GET[$zaehler]);
			$export_details['positions'][$zaehler] = array ('sku'				=> $bestelldaten[0],
															'item_amount'		=> $bestelldaten[1],
															'customs_value'		=> floatval(str_replace(',', '.', str_replace('.', '', $bestelldaten[2]))),
															'goods_description' => $bestelldaten[3]);
		}
	}
	elseif (isset($_POST["cusordnumber"]))
	{
		$cusordnumber = $_POST["cusordnumber"];
		$order_id = $_POST["order_id"];
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
	
		$bestelldaten = explode("\n", $_POST["bestelldaten"]);
		foreach ($bestelldaten as $zaehler => $einzelartikel)
		{
			$einzelartikelarray = explode("|", $einzelartikel);
			if (sizeof($einzelartikelarray) > 1)
			{
				$export_details['positions'][$zaehler] = array ('sku'				=> $einzelartikelarray[0],
																'item_amount'		=> $einzelartikelarray[1],
																'customs_value'		=> floatval(str_replace(',', '.', str_replace('.', '', $einzelartikelarray[2]))),
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
				."<tr><td>Bestellnummer des Kunden</td><td><input type=\"text\" size=\"40\" name=\"cusordnumber\" value=\"".$cusordnumber."\"></td></tr>"
				."<tr><td>Auftragsnummer</td><td><input type=\"text\" size=\"40\" name=\"order_id\" value=\"".$order_id."\"></td></tr>"
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
	foreach ($export_details['positions'] as $einzelartikel)
	{
		echo $einzelartikel['sku']."|".$einzelartikel['item_amount']."|".$einzelartikel['customs_value']."|".$einzelartikel['goods_description']."\n";
	}
	echo "</textarea></td></tr>"
		."<tr><td>-----------------------</td><td>-------------------------------------------</td></tr>"
		."<tr><td>Email Portoempfaenger</td><td><input type=\"text\" size=\"40\" name=\"portoempfaenger\" value=\"".$portoemail."\"></td></tr>";
	echo "</table>";
	if (!isset($_POST["portoerstellen"]) && !checkPortostatusOfOrderId($cusordnumber, $order_id))
	{
		echo "<br><input type=\"submit\" name=\"portoerstellen\" value=\"Porto erstellen\">";
	}
	else
	{
		if (checkPortostatusOfOrderId($cusordnumber, $order_id))
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
				setPortostatusOfOrderId($cusordnumber, $response['carrier'], $response['shipment_number'], $order_id);
			}
	 		
			if ($response['status'] == 0)
			{
	 			$email = new PHPMailer();
	 			$email->From      = 'test-dhl-post@opis-tech.com';
	 	 	    $email->FromName  = 'Test DHL Post';
	 	 	    $email->Subject   = '[auto versand] '; 
	 	 	    foreach($export_details['positions'] as $position)
	 	 	    {
	 	 			$email->Subject .= $position['item_amount']."#".$position['sku']." | ";
	 	 	    }
	 	 	    $email->Body      = "Shipment Carrier: " . $response['carrier']."\nShipment Number : " . $response['shipment_number'];
	 	 	    $email->AddAddress( $portoempfaenger );
	 	 	    
	 	 	    $email->AddAttachment( LABEL_DOWNLOAD_PATH . $response['shipment_label_file'] );
	 		    if (array_key_exists('export_document_file', $response) && strlen($response['export_document_file']))
	 	 	    {
	 	 			$email->AddAttachment( LABEL_DOWNLOAD_PATH . $response['export_document_file'] );
	 	 		}
	 	 		return $email->Send();
		 	}		
		}
	}
	echo "</form>";
	echo "</body>";
	echo "</html>";
}
?>
