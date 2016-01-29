<?php

require_once "DB.php";
require_once "MDB2.php";
require "conf.php";
require "constants.php";
require "erpfunctions.php";
require "reviewmails/definitions.php";

$VERSANDKOSTEN = 0;
$GESCHENKVERPACKUNG = 0;

$dsnP = array(	'phptype'  => 'pgsql',
				'username' => $ERPuser,
				'password' => $ERPpass,
				'hostspec' => $ERPhost,
				'database' => $ERPdbname,
				'port'     => $ERPport);

/**********************************************
* getNewSales($datum_von, $datum_bis)
***********************************************/
function getNewSales($datum_von, $datum_bis, $country)
{
	require_once "DB.php";
	require "conf.php";
	require "reviewmails/definitions.php";
	print("\n\n\n");
	print("**********************************************\n");
	print("*****FIND WARRANTY EMAILS (VERSION 1)*********\n");
	print("**********************************************\n");
	print("from:$datum_von to:$datum_bis\n");
	
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
		$rs = $dbP->getall("SELECT DISTINCT ON (ar.invnumber)" 
				   ." trim(customer.email) AS customeremail,"
				   ." trim(customer.name) AS customername,"
				   ." trim(substring(ar.intnotes from 'SalesChannel\ Amazon\.([^ ]*)')) AS saleschannel,"
				   ." trim(parts.partnumber) AS sku,"
				   ." trim(partsgroup.partsgroup) AS category,"
				   ." trim(ar.cusordnumber) AS amazonid,"
				   ." trim(ar.invnumber) AS invoiceid,"
				   ." ar.transdate AS transdate,"
				   ." trim(ar.intnotes) AS intnotes,"
				   ." trim(ar.notes) AS notes"
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
				   ." AND ar.invnumber_for_credit_note IS NULL AND ar.department_id='1076'" //1076 = Amazon
				   ." AND (partsgroup.partsgroup = 'cable' OR partsgroup.partsgroup = 'cable-hEar'"
				   ." OR partsgroup.partsgroup = 'mobile' OR partsgroup.partsgroup = 'mobile-hEar'"
				   ." OR partsgroup.partsgroup = 'ff-cable') AND ar.shippingpoint='Amazon'"
				   ." ORDER BY ar.invnumber ASC,  ar.transdate ASC"
				   );
		$list='';
		$no=0;
		foreach ($rs as $lfdNr => $zeile)
		{
		  $customeremail=$zeile[0];
		  $customername=$zeile[1];
		  $sku=$zeile[3];
		  $category=$zeile[4];
		  //HACK: for now make hEar normal versions to save special texts, etc.
		  $category = str_replace("-hEar", "", $category);
		  $amazonid=$zeile[5];
		  $invoiceid=$zeile[6];
		  $transdate=$zeile[7];
		  $intnotes=$zeile[8];
		  $notes=$zeile[9];

		  $wrongchannel=false;
		  switch ($zeile[2]) 
		    {
		    case 'de':
		      $saleschannel="DE";
		      break;
		    case 'co.uk':
		      $saleschannel="UK";
		      break;
		    case 'fr':
		      $saleschannel="FR";
		      break;
		    case 'es':
		      $saleschannel="ES";
		      break;
		    case 'it':
		      $saleschannel="IT";
		      break;
		    case 'com':
		      $saleschannel="US";
		      break;
		    default:
		      print("\n\n******Wrong saleschannel received: $saleschannel (Customer: $customername, Email: $customeremail, Amazon-Id: $amazonid, Invoice-Id: $invoiceid, internal Notes: $intnotes, Notes: $notes)******\n\n");
		      $wrongchannel=true;
		    }

		  if ($wrongchannel) continue;
		  if ($category == "sonstiges") continue;
		  $rs2 = $dbP->getOne("SELECT COUNT(*)"
				      ." FROM"
				      ." ar"
				      ." WHERE"
				      ." invnumber='".$invoiceid."'"
				      ." AND EXISTS "
				      ." (" 
				      . "SELECT * FROM ar WHERE"
				      ." invnumber_for_credit_note='".$invoiceid."'"
				      ." AND amount < '-15'"
				      .")");
		  //$subject=${$saleschannel}[$category]['emailsubject'];
		  //$body=sprintf(${$saleschannel}[$category]['emailbody'],$customername,${$saleschannel}[$category]['urls'][$sku]);
		  if ($saleschannel == $country) {
		    print("Date:".$transdate."\n");
		    print("Amazon Order ID:".$amazonid."\n");
		    print("Customer Email:".$customeremail."\n");
		    print("Country:".$saleschannel."\n");
		    print("Product:".$sku."\n");

		    if($rs2[0]) {
		      print("************Skipping because of refund!************\n\n\n");
		      continue;
		    }
  		    $list .= $customername . " <" . $customeremail . ">, ";
		    $no++;
		  }

		  //		  print("Subject:".$subject."\n");
		  //  print("Body:\n".$body."\n");

		  //  print("Send? [n does not send, everything else sends]");
		  // $handle = fopen ("php://stdin","r");
		  // $line = fgets($handle);
		  /*		  if(trim($line) != 'n')
		    {
		      $headers = "From: $sender \r\n";
		      $headers .= "MIME-Version: 1.0\r\n";
		      $headers .= "Content-type: text/plain; charset=utf-8\r\n";
		      $headers .="Content-Transfer-Encoding: 8bit";

		      $body=htmlspecialchars_decode($body,ENT_QUOTES);//optional
		      //$customeremail="aron.zeh@opis-tech.com";
		      mail($customeremail,  "=?utf-8?B?".base64_encode($subject)."?=", $body, $headers);
		      echo "sent!\\n\n\n\n\n";
		    } 
		  else
		    {
		      echo "NOT SENT!\\n\n\n\n\n";
		    }
		  */
	

		}
	}
	print "\n\n\n$list\n\ntotal=$no\n\n";
}
$end_date=date('Y-m-d', strtotime('24 December 2014'));
$start_date=date('Y-m-d', strtotime('22 December 2014'));
getNewSales($start_date, $end_date, $argv[1]);
?>