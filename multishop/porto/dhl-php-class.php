<?php
class DHLBusinessShipment {
    private $credentials;
    private $info;
    private $client;
    protected $sandbox;
    
    /**
     * Constructor for Shipment SDK
     *
     * @param type $dhl_api_credentials
     * @param type $customer_info
     * @param boolean $sandbox use sandbox or production environment
     */
    function __construct($order_id) {
	global $dhl_api_credentials, $sender_info;
	$this->order_id = $order_id;

	$this->credentials = $dhl_api_credentials;
	$this->info        = $sender_info;
	$this->sandbox = $this->credentials['sandbox'];

	$this->response                    = array();
	$this->response['status'] = 0;
	$this->response['error_message'] = '';
	$this->response['warning_message'] = '';
    }
    
    private function exchange_rate($from_Currency, $to_Currency) {
	if ($from_Currency ==  $to_Currency) { return 1;};

	$from_Currency = urlencode($from_Currency);
	$to_Currency = urlencode($to_Currency);
	$this->response['exchange_rate']=1;

	$url = "http://www.google.com/finance/converter?a=1&from=$from_Currency&to=$to_Currency";
	
	$ch = curl_init();
	$timeout = 0;
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

	curl_setopt ($ch, CURLOPT_USERAGENT,
            "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$rawdata = curl_exec($ch);
	curl_close($ch);
	if(!$rawdata) {
	    $this->response['warning_message'] .= ' - ' . __FILE__ . ": Could not convert currency from:$from_Currency, to:$to_Currency with rate exchange website, assuming 1 to 1.\n"; 
	    return 1;
	}
	$data = explode('bld>', $rawdata);
	if(!$data) {
	    $this->response['warning_message'] .= ' - ' . __FILE__ . ": Could not convert currency from:$from_Currency, to:$to_Currency with rate exchange website, assuming 1 to 1 (data).\n"; 
	    return 1;
	}
	$data = explode($to_Currency, $data[1]);
	$this->response['exchange_rate']=round($data[0],2);
	return $this->response['exchange_rate'];
    }

    private function buildClient() {
	$header = $this->buildAuthHeader();
	if ($this->sandbox) {
	    $location = DHL_SANDBOX_URL;
	} else {
	    $location = DHL_PRODUCTION_URL;
	}
	
	$auth_params = array(
	    'login'    => $this->credentials['api_user'],
		'password' => $this->credentials['api_password'],
		'location' => $location,
		'trace'    => 1,
		'exceptions' =>0
	);

	$this->client = new SoapClient( DHL_API_URL, $auth_params );
	$this->client->__setSoapHeaders( $header );
    }

    private function downloadPDFLabel() {
	$url  = $this->response['label_url'];
	$path = LABEL_DOWNLOAD_PATH . '/';
	$file =  $this->date . '-' . $this->response['shipment_number'] . '.pdf';

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($ch);
	curl_close($ch);

	$result = file_put_contents($path . $file, $data);
	
	return $file;

    }

    private function cropPdf($first_page,$last_page,$filename) {
	// initiate FPDI
	$pdf = new FPDI();
	
	// get the page count
	$pageCount = $pdf->setSourceFile($filename);
	// iterate through all pages
	for ($pageNo = $first_page; $pageNo <= $last_page; $pageNo++) {
	    // import a page
	    $templateId = $pdf->importPage($pageNo);
	    // get the size of the imported page
	    $size = $pdf->getTemplateSize($templateId);
	    
	    // create a page (landscape or portrait depending on the imported page size)
	    if ($size['w'] > $size['h']) {
		$pdf->AddPage('L', array($size['w'], $size['h']));
	    } else {
		$pdf->AddPage('P', array($size['w'], $size['h']));
	    }
	    
	    // use the imported page
	    $pdf->useTemplate($templateId);
	}
	$pdf->Output($filename);
    }

    private function getExportDocuments() {
	$export_doc['Version'] = array( 'majorRelease' => '1', 'minorRelease' => '0' );
	$export_doc['ShipmentNumber']['shipmentNumber'] = $this->response['shipment_number'];
	$response = $this->client->GetExportDocDD( $export_doc );
	
	$path = LABEL_DOWNLOAD_PATH . '/';
	$file =  $this->date . '-' . $this->response['shipment_number'] . DHL_EXPORT_DOC_POSTFIX . '.pdf';
	
	$result = file_put_contents($path . $file,  
	    base64_decode($response->ExportDocData->ExportDocPDFData));
	
	if ( is_soap_fault( $response ) || $response->status->StatusCode != 0 ) {
	    $this->response['status'] = -1;
	    $this->response['error_message'] = __FILE__ . ": ";
	    
	    if ( is_soap_fault( $response ) ) {
		$this->response['error_message'] .= $response->faultstring;
	    } else {
		$this->response['error_message'] .= $response->status->StatusMessage;
		if (isset($response->CreationState) && is_array($response->CreationState->StatusMessage)) {
		    foreach ($response->CreationState->StatusMessage as $message) {
			$this->response['error_message'] .= ' - ' . $message;
		    }
		} else if (isset($response->CreationState)) {
		    $this->response['error_message'] .= ' - ' . $response->CreationState->StatusMessage;		
		}
		if (isset($response->ExportDocData->Status->StatusMessage)) {
		    $this->response['error_message'] .= ' - ' . $response->ExportDocData->Status->StatusMessage;
		}
	    }
	} else { //good
		$this->cropPdf(3,4,$path.$file);
		$this->response['export_document_file'] = $file;
	}
    }
    
    function createBusinessShipment( $customer_details, $package_details, $export_details=null) {
	$this->buildClient();
	$this->date=date('Y-m-d');
	$shipment = array();
	// Version
	$shipment['Version'] = array( 'majorRelease' => '1', 'minorRelease' => '0' );
	// Order
	$shipment['ShipmentOrder'] = array();
	// FIXME: what is this?
	$shipment['ShipmentOrder']['SequenceNumber'] = '1';

	if(!$customer_details['street_number']) {
	    $customer_details['street_number']='.';
	}
	if (isset($customer_details['line2']) && strlen($customer_details['line2'])) {
	    if (!isset($customer_details['line3']) || $customer_details['line3']=='') {
		$customer_details['line3']=$customer_details['line2'];
		$customer_details['line2']='';
	    }
	}
	if (!isset($customer_details['line3']) || $customer_details['line3']=='') {
	    $customer_details['line3']='.';
	}

	//HACK Filter out extra starting letters for non-letter postcodes
	switch ($customer_details['country_ISO_code']) {
	    case 'AR':
	    case 'BN':
	    case 'CA':
	    case 'IE':
	    case 'JM':
	    case 'KZ':
	    case 'MT':
	    case 'NL':
	    case 'PE':
	    case 'SO':
	    case 'SZ':
	    case 'UK': 
	    case 'GB': 
		break;
	    default:
		$customer_details['zip']=preg_replace("/^\D*(.*)/", "$1", $customer_details['zip']);
		break;
	}

	if ($export_details) {
	    //HACK: DHL API allows to specify currencies, but always ovewrites them with EUR, so it is better to convert sums to EUR first.
	    $exchange_rate=$this->exchange_rate($export_details['customs_currency'], 'EUR');

	    $export=array();
	    $export['InvoiceType'] = DHL_INVOICE_TYPE;
	    $export['InvoiceDate'] = $export_details['invoice_date'];
	    if (isset($export_details['invoice_number'])) {
		$export['InvoiceNumber'] = $export_details['invoice_number'];
	    }
	    $export['ExportType'] = DHL_EXPORT_TYPE;
	    $export['ExportTypeDescription'] = DHL_EXPORT_TYPE_DESCRIPTION;
	    $export['CommodityCode'] = $export_details['positions'][0]['customs_commodity_code'];
	    $export['TermsOfTrade'] = DHL_TERMS_OF_TRADE;
	    $export['Description'] = OVERALL_GOODS_DESCRIPTION;
	    $export['CountryCodeOrigin'] = DHL_COUNTRY_CODE_ORIGIN_GOODS;
	    $export['ExportDocPosition'] = array();
	    $total_positions=0;$total_value=0;
	    foreach ($export_details['positions'] as $position) {
		$export_position['Description'] = $position['goods_description'];
		$export_position['CountryCodeOrigin'] = DHL_COUNTRY_CODE_ORIGIN_GOODS;
		$export_position['CommodityCode'] = $position['customs_commodity_code'];
		$export_position['Amount'] = $position['item_amount'];
		$export_position['NetWeightInKG'] = $position['net_weight_kg'];
		$export_position['GrossWeightInKG'] = $position['gross_weight_kg'];
		$export_position['CustomsValue'] = round($position['customs_value']*$exchange_rate);
		$total_value += $export_position['CustomsValue'];
		$export_position['CustomsCurrency'] = 'EUR';//$export_details['customs_currency'];
		$export['ExportDocPosition'][]=$export_position;
		$total_positions++;;
	    }
	    $export['Amount'] = $total_positions;
	    $export['CustomsValue'] = $total_value;
	    $export['CustomsCurrency'] = 'EUR';//$export_details['customs_currency'];

	    $shipment['ShipmentOrder']['Shipment']['ExportDocument'] = $export;
	}

	// Shipment
	$s                 = array();
	if($customer_details['country_ISO_code'] == 'DE') {
	    $s['ProductCode']  = 'EPN';
	} else {
	    $s['ProductCode']  = 'BPI';
	};
	$s['ShipmentDate'] = $this->date;
	$s['EKP']          = $this->credentials['ekp'];
	$s['Attendance']              = array();
	$s['Attendance']['partnerID'] = '01';
	
	$s['ShipmentItem']               = array();
	$s['ShipmentItem']['WeightInKG'] = $package_details['weight_kg'];
	$s['ShipmentItem']['LengthInCM'] = $package_details['length_cm'];
	$s['ShipmentItem']['WidthInCM']  = $package_details['width_cm'];
	$s['ShipmentItem']['HeightInCM'] = $package_details['height_cm'];
	// FIXME: What is this? PK = Package?
	$s['ShipmentItem']['PackageType'] = 'PK';
	
	$s['CustomerReference']=$customer_details['reference_number'];
	if(isset($export)) {
	    $s['DeclaredValueOfGoods']=$export['CustomsValue'];
	    $s['DeclaredValueOfGoodsCurrency']= 'EUR'; //$export['CustomsCurrency'];
	}

	$shipment['ShipmentOrder']['Shipment']['ShipmentDetails'] = $s;
	$shipper                                = array();
	$shipper['Company']                     = array();
	$shipper['Company']['Company']          = array();
	$shipper['Company']['Company']['name1'] = $this->info['company_name'];
	$shipper['Address']                                                = array();
	$shipper['Address']['streetName']                                  = $this->info['street_name'];
	$shipper['Address']['streetNumber']                                = $this->info['street_number'];
	$shipper['Address']['Zip']                                         = array();

	if($this->info['country_ISO_code'] == 'DE') {
	    $shipper['Address']['Zip']['germany'] = $this->info['zip'];
        } else {
	    $shipper['Address']['Zip']['other'] = $this->info['zip'];
	};
	$shipper['Address']['city']                                        = $this->info['city'];
	$shipper['Address']['Origin']['countryISOCode'] = $this->info['country_ISO_code'];
	$shipper['Communication']                  = array();
	$shipper['Communication']['email']         = $this->info['email'];
	$shipper['Communication']['phone']         = $this->info['phone'];
	$shipper['Communication']['internet']      = $this->info['internet'];
	$shipper['Communication']['contactPerson'] = $this->info['contact_person'];
	$shipment['ShipmentOrder']['Shipment']['Shipper'] = $shipper;
	$receiver = array();
 	$receiver['Company']                        = array();
	$receiver['Company']['Company']              = array();
	$receiver['Company']['Company']['name1'] = $customer_details['name'];
	$receiver['Company']['Company']['name2']  = $customer_details['line2'];
	if (isset($customer_details['packstation_number'])) {
	    $receiver['Packstation']['PackstationNumber']=$customer_details['packstation_number'];
	    $receiver['Packstation']['PostNumber']=$customer_details['post_kundennumber'];
	    $receiver['Packstation']['Zip']=$customer_details['zip'];
	    $receiver['Packstation']['City']=$customer_details['city'];
	} else if (isset($customer_details['postfiliale_number'])) {
	    $receiver['Postfiliale']['PostfilialNumber']=$customer_details['postfiliale_number'];
	    $receiver['Postfiliale']['PostNumber']=$customer_details['post_kundennumber'];
	    $receiver['Postfiliale']['Zip']=$customer_details['zip'];
	    $receiver['Postfiliale']['City']=$customer_details['city'];
	} else {
	    $receiver['Address']                                                      = array();
	    $receiver['Address']['streetName']                                        = $customer_details['street_name'];
	    $receiver['Address']['streetNumber']                                      = $customer_details['street_number'];
	    if(isset($customer_details['c/o'])) {
		$receiver['Address']['careOfName'] = $customer_details['c/o'];
	    }
	    $receiver['Address']['Zip']                                               = array();
//	    if (strlen($customer_details['zip'])>DHL_MAX_ZIP_LENGTH) {
//		$customer_details['zip'] = substr($customer_details['zip'],0,DHL_MAX_ZIP_LENGTH);
//	    }
	    if($customer_details['country_ISO_code'] == 'DE') {
		$receiver['Address']['Zip']['germany'] = $customer_details['zip'];
	    } else if ($this->info['country_ISO_code'] == 'UK') {
		$receiver['Address']['Zip']['england'] = $customer_details['zip'];
	    } else {
		$receiver['Address']['Zip']['other'] = $customer_details['zip'];
	    };
	    $receiver['Address']['city']                                              = $customer_details['city'];
	    $receiver['Address']['Origin'] = array( 'countryISOCode' => $customer_details['country_ISO_code']);
	    $receiver['Address']['languageCodeISO'] = $customer_details['country_ISO_code'];
	}
	$receiver['Communication']                                                = array();
	$receiver['Communication']['contactPerson']  = $customer_details['line3'];
	if($customer_details['email']) {
	    $receiver['Communication']['email'] = $customer_details['email'];
	}
	if($customer_details['telephone']) {
	    $receiver['Communication']['phone'] = $customer_details['telephone'];
	}
	$shipment['ShipmentOrder']['Shipment']['Receiver'] = $receiver;

	$response = $this->client->CreateShipmentDD( $shipment );
//	  echo "====== REQUEST HEADERS =====" . PHP_EOL;
//	  var_dump($this->client->__getLastRequestHeaders());
//	  echo "========= REQUEST ==========" . PHP_EOL;
//	  var_dump($this->client->__getLastRequest());
//	  echo "========= RESPONSE ==========" . PHP_EOL;
//	  var_dump($this->client->__getLastResponse());

	if ( is_soap_fault( $response ) || $response->status->StatusCode != 0 ) {
	    $this->response['status'] = -1;
	    $this->response['error_message'] = __FILE__ . ": ";
	    if ( is_soap_fault( $response ) ) {
		$this->response['error_message'] .= $response->faultstring;
	    } else {
		$this->response['error_message'] .= $response->status->StatusMessage;
		if (isset($response->CreationState) && is_array($response->CreationState->StatusMessage)) {
		    foreach ($response->CreationState->StatusMessage as $message) {
			$this->response['error_message'] .= ' - ' . $message;
		    }
		} else if (isset($response->CreationState)) {
		    $this->response['error_message'] .= ' - ' . $response->CreationState->StatusMessage;		
		}
		if (isset($response->ExportDocData->Status->StatusMessage)) {
		    $this->response['error_message'] .= ' - ' . $response->ExportDocData->Status->StatusMessage;
		}
	    }
	} else {
	    $this->response['shipment_number'] = (String) $response->CreationState->ShipmentNumber->shipmentNumber;
	    $this->response['piece_number']    = (String) $response->CreationState->PieceInformation->PieceNumber->licensePlate;
	    $this->response['label_url']       = (String) $response->CreationState->Labelurl;
	    $this->response['shipment_label_file'] = $this->downloadPDFLabel();
	    
	    if($export_details) {
		$this->getExportDocuments();
	    }
	    
	}
	return $this->response;
    }

    private function buildAuthHeader() {
	$head = $this->credentials;
	$auth_params = array(
	    'user'      => $this->credentials['user'],
		'signature' => $this->credentials['signature'],
		'type'      => 0
	);
	return new SoapHeader( 'http://dhl.de/webservice/cisbase', 'Authentification', $auth_params );
    }
}
?>
