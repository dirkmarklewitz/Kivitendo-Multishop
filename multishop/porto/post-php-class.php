<?php
require_once('prepare-shipment-class.php');

class PostInternetmarke {
    private $credentials;
    private $info;
    private $client;
    public $errors;
    
    /**
     * Constructor for Shipment SDK
     *
     * @param type $dhl_api_credentials
     * @param type $customer_info
     */
    function __construct() {
	global $post_api_credentials, $sender_info;
	$this->credentials = $post_api_credentials;
	$this->info        = $sender_info;
	$this->date = date('Y-m-d');

	$this->response                    = array();
	$this->response['status'] = 0;
	$this->response['error_message'] = '';
	$this->response['warning_message'] = '';    }

    private function generate_export_pdf($customer_details,$parcel,$export_details) {
	global $sender_info;
	// initiate FPDI
	$pdf = new FPDI();
	$this->tmpfile=tempnam(TEMP_PATH, 'post-export-pdf-'  );
	
	$pdf->setSourceFile(POST_EMPTY_BRIEF_INTERNATIONAL_PDF);
	$templateId = $pdf->importPage(1);
	$size = $pdf->getTemplateSize($templateId);
	$pdf->AddPage('L', array($size['w'], $size['h']));
	// use the imported page
	$pdf->useTemplate($templateId);
	$pdf->SetAutoPageBreak(false);
	$pdf->SetMargins(0,0);

	$pdf->AddFont('DejaVuSans', '', './DejaVuSans.ttf', true);
	$pdf->SetFont('DejaVuSans','',9);
	$pdf->SetTextColor(0, 0, 0);
	$pdf->SetXY(6,23);  //Line 1
	$pdf->Write(0, $sender_info['company_name']);
	$pdf->SetXY(6,33);
	$pdf->Write(0,$sender_info['street_name'] . ' ' . $sender_info['street_number']);
	$pdf->SetXY(6,53);
	$pdf->Write(0,$sender_info['zip'] . ' ' . $sender_info['city']);

	$pdf->SetXY(78,48);
	$pdf->Write(0, $customer_details['name']);
	$pdf->SetXY(78,58);
	$pdf->Write(0, $customer_details['line2']);
	if ($customer_details['line3']) {
	    $customer_details['street_name'] = $customer_details['line3'] . ", " . $customer_details['street_name'];
	}
	if (strlen($customer_details['street_name']) > POST_MAX_ADDRESS_FIELD_LENGTH) {
	    $this->response['status']=1;
	    $this->response['warning_message'] .= ' - ' . __FILE__ . ": Not enough space. " . $customer_details['street_name'] . " is " . strlen($customer_details['street_name']) . 
		" characters, but only " .  POST_MAX_ADDRESS_FIELD_LENGTH . " are allowed. Overwriting...\n";
	} 

	$pdf->SetXY(78,68);
	$pdf->Write(0, $customer_details['street_name'] . " " . $customer_details['street_number']);
	$pdf->SetXY(78,78);
	$pdf->Write(0, $customer_details['zip'] . " " . $customer_details['city']);
	$pdf->SetXY(78,88);
	$pdf->Write(0, strtoupper($customer_details['country']));

	$pdf->SetFont('DejaVuSans','',6);
	$pdf->SetXY(193,33);
	$pdf->Write(0,'X');

	$positions = $export_details['positions'];
	
	
	$pdf->SetXY(149,44);
	$pdf->Write(0,$positions[0]['item_amount'] . " x " . $positions[0]['goods_description']);
	$pdf->SetXY(189,44);
	$pdf->Write(0,$positions[0]['gross_weight_kg']);
	$pdf->SetXY(198,44);
	$pdf->Write(0,round($positions[0]['customs_value']) . $export_details['customs_currency']);
	
	if(sizeof($positions)>1) {
	    $pdf->SetXY(149,47);
	    $pdf->Write(0,$positions[1]['item_amount'] . " x " . $positions[1]['goods_description']);
	    $pdf->SetXY(189,47);
	    $pdf->Write(0,$positions[1]['gross_weight_kg']);
	    $pdf->SetXY(198,47);
	    $pdf->Write(0,round($positions[1]['customs_value']) . $export_details['customs_currency']);
	}
	
	if(sizeof($positions)>2) {
	    $pdf->SetXY(149,50);
	    $pdf->Write(0,$positions[2]['item_amount'] . " x " . $positions[2]['goods_description']);
	    $pdf->SetXY(189,50);
	    $pdf->Write(0,$positions[2]['gross_weight_kg']);
	    $pdf->SetXY(198,50);
	    $pdf->Write(0,round($positions[2]['customs_value']) . $export_details['customs_currency']);
	}
	
	if(sizeof($positions)>3) {
	    $pdf->SetXY(149,53);
	    $pdf->Write(0,$positions[3]['item_amount'] . " x " . $positions[3]['goods_description']);
	    $pdf->SetXY(189,53);
	    $pdf->Write(0,$positions[3]['gross_weight_kg']);
	    $pdf->SetXY(198,53);
	    $pdf->Write(0,round($positions[3]['customs_value']) . $export_details['customs_currency']);
	}
	
	if(sizeof($positions)>4) {
	    $pdf->SetXY(149,56);
	    $pdf->Write(0,$positions[4]['item_amount'] . " x " . $positions[4]['goods_description']);
	    $pdf->SetXY(189,56);
	    $pdf->Write(0,$positions[4]['gross_weight_kg']);
	    $pdf->SetXY(198,56);
	$pdf->Write(0,round($positions[4]['customs_value']) . $export_details['customs_currency']);
	}
	
	$pdf->SetXY(149,71);
	$pdf->Write(0, $positions[0]['customs_commodity_code'] . ' / CN');
	$pdf->SetXY(189,71);
	$pdf->Write(0,$parcel['weight_kg']);
	$pdf->SetXY(198,71);
	$pdf->Write(0,round($export_details['total_sum']).$export_details['customs_currency']);
	
	$pdf->SetXY(149,99);
	$pdf->Write(0, date('Y-m-d') . ' / Opis Technology GmbH');
	
	$pdf->Output($this->tmpfile);
    }
    
    private function send_balance_refill_email() {
	$subject = "Post Portokasse below limit: " . ($this->balance/100) . "EUR";
	$body = "Please refill at: " . POST_BALANCE_REFILL_URL;
	$headers = "From: 'post-portokasse@opis-tech.com \r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/plain; charset=utf-8\r\n";
	$headers .="Content-Transfer-Encoding: 8bit";
	
	$body=htmlspecialchars_decode($body,ENT_QUOTES);//optional
	$email_address=PORTOKASSE_EMAIL_RECIPIENT;
	mail($email_address,  "=?utf-8?B?".base64_encode($subject)."?=", $body, $headers);
    }

    private function setTimestamp() {
	$this->timestamp = date("dmY-His");
    }

    private function setSignature($elements) {
	$combined='';
	foreach ($elements as $element) {
	    if($combined) {
		$combined .= '::';
	    };
	    $combined .= $element;
	}
		$this->credentials['key_dpwn_marketplace'];
	$this->md5=md5($combined);
	$this->md5=substr($this->md5,0,8); //only first 8 positions wanted by Post
    }

    private function buildAuthHeader() {
	$head = $this->credentials;
	
	$headers = array();
	$headers[] = new SoapHeader("POST_INTERNETMARKE", 'PARTNER_ID', $this->credentials['partner_id']);
	$headers[] = new SoapHeader("POST_INTERNETMARKE", 'REQUEST_TIMESTAMP', $this->timestamp);
	$headers[] = new SoapHeader("POST_INTERNETMARKE", 'KEY_PHASE', $this->credentials['key_phase']);
	$headers[] = new SoapHeader("POST_INTERNETMARKE", 'PARTNER_SIGNATURE', $this->md5);

	return $headers;
    }

    private function buildClient() {
	$headers = $this->buildAuthHeader();
	$location = POST_URL;
	
	$auth_params = array(
	    'location' => $location,
		'trace' => 1,
		'exception' => 1
	);

	$this->client = new SoapClient( POST_API_URL, $auth_params );
	$this->client->__setSoapHeaders( $headers );
    }

    private function deletePath($path){
	if (is_dir($path) === true) {
            $files = array_diff(scandir($path), array('.', '..'));
            foreach ($files as $file) {
		$this->deletePath(realpath($path) . '/' . $file);
            }
            return rmdir($path);
	}
	else if (is_file($path) === true) {
            return unlink($path);
	}
	return false;
    }
  /*  
    private function barcode($file) {
	$parameters = array('apikey' => POST_BARCODE_API_KEY,
	    'mode' => 'document_scan',
	    'file' =>'@'. $file); 
	
	//use HP webservice for barcode recognition
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, POST_BARCODE_API_URL);
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$result=curl_exec ($ch);
	curl_close ($ch);
	if ($result) {
	    $shipping_no_container=json_decode($result);
	};
//	var_dump($shipping_no_container);
 	if(isset($shipping_no_container->barcode[0]->text)
		&& $shipping_no_container->barcode[0]->text) {
	    $this->response['shipment_number']=$shipping_no_container->barcode[0]->text;
	    } else {
		$this->response['status'] = 1;
		$this->response['warning_message'] .= ' - ' . __FILE__ . ": ";
		$this->response['shipment_number']= uniqid();
		$this->response['warning_message'] .= "ERROR: Could not decode shipment number bar code using web service." . 
		    " Proceeding with unique id " . $this->response['shipment_number'] . " instead.";
	    }

	return $this->response;
    }
*/
    private function downloadLabel() {
	$unique_id = uniqid();
	$url  = $this->label_url;
	$path = LABEL_DOWNLOAD_PATH . '/';

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($ch);
	curl_close($ch);

	//Create temporary directory
	$tmppath = TEMP_PATH . "post_internetmarke_" . $unique_id . "/";
	mkdir($tmppath);

	$this->response['status']=0;
	$this->response['error_message'] = '';
	$tmpzip = $tmppath . 'archive.zip';

	$result = file_put_contents($tmpzip, $data);
	if(!$result){
	    $this->response['status']=-1;
	    $this->response['error_message']= __FILE__ . ': Saving zip to Temp file failed.';
	    return $this->response;
	} 

	$zip = new ZipArchive;
	$result = $zip->open($tmpzip);
	$imgname = $zip->getNameIndex(0);
	if(!$result){
	    $this->response['status']=-1;
	    $this->response['error_message']= __FILE__ . ': Opening zip Temp file failed.';
	    return $this->response;
	} 
	$zip->extractTo($tmppath);
	$zip->close();

	//barcode recognition
//	$response = $this->barcode($tmppath . $imgname); 

	$file =  $this->date . '-' . $this->response['shipment_number'] . '.png';
	copy ($tmppath . $imgname, $path . $file);

	$this->deletePath($tmppath);

	$this->response['shipment_label_file']=$file;

	$file = $this->date . '-' . $this->response['shipment_number'] . POST_EXPORT_DOC_POSTFIX . '.pdf';
	copy ($this->tmpfile, $path.$file);
	unlink($this->tmpfile);

	$this->response['export_document_file']=$file;

	return $this->response;
    }
    
    private function loginToInternetmarke() {
	$this->setTimestamp();
	$signature_elements= array();
	$signature_elements[] = $this->credentials['partner_id'];
	$signature_elements[] = $this->timestamp;
	$signature_elements[] = $this->credentials['key_phase'];
	$signature_elements[] = $this->credentials['key_dpwn_marketplace'];
	$this->setSignature($signature_elements);

	$this->buildClient();

	$data = array();
	$data['username'] = $this->credentials['username'];
        $data['password'] = $this->credentials['password'];

	$this->response = array();
	$this->response['status'] = 0;
	$this->response['error_message'] = '';
	try {
	    $response = $this->client->authenticateUser($data);
	} catch (SoapFault $e){
//	    var_dump($e);
	    $this->response['status'] = -1;
	    $this->response['error_message'] = __FILE__ . ": ";
	    $this->response['error_message'] .= $e->faultstring . " - " .
		$e->detail->AuthenticateUserException->errors->id . ': ' .
		$e->detail->AuthenticateUserException->errors->message;
	    return $this->response;
	}

	$this->userToken=$response->userToken;
	$this->balance=$response->walletBalance;

	return $this->response;
    }    

    private function checkOutCart($type) {
	$this->setTimestamp();
	$signature_elements= array();
	$signature_elements[] = $this->credentials['partner_id'];
	$signature_elements[] = $this->timestamp;
	$signature_elements[] = $this->credentials['key_phase'];
	$signature_elements[] = $this->credentials['key_dpwn_marketplace'];
	$this->setSignature($signature_elements);

	$this->buildClient();

	$data = array();
	$data['userToken'] = $this->userToken;
	$data['ppl'] = POST_PRODUCT_LIST_VERSION;
        $data['positions'] = array();

	$position['productCode'] = $type['id'];
	$position['voucherLayout'] = POST_VOUCHER_LAYOUT;
	$data['positions'][]=$position;

	$data['total']=$type['price'];

	$this->response = array();
	$this->response['status'] = 0;
	$this->response['error_message'] = '';
	try {
	    $response = $this->client->checkoutShoppingCartPNG($data);
	} catch (SoapFault $e){
//	    var_dump($e);
	    $this->response['status'] = -1;
	    $this->response['error_message'] = __FILE__ . ": ";
	    $this->response['error_message'] .= $e->faultstring . " - " .
		$e->detail->ShoppingCartValidationException->errors->id . ': ' .
		$e->detail->ShoppingCartValidationException->errors->message;
	    return $this->response;
	}
	$this->label_url = $response->link;
	$this->response['shipment_number'] = $response->shoppingCart->voucherList->voucher->trackId;
	$this->response['label_url'] = $response->link;
	$this->balance = $response->walletBallance;
	$this->response['euro_cents_remaining'] = $this->balance;
	if($this->balance < POST_BALANCE_REFILL_THRESHOLD) {
	    $this->send_balance_refill_email();
	}
	
	return $this->response;
    }
    
    function buyPostage ($type,$customer_details,$parcel,$export_details) {
	$this->generate_export_pdf($customer_details,$parcel,$export_details);
	$this->loginToInternetmarke();
	if ($this->response['status']>=0) {
	    $this->checkOutCart($type);
	}
	if ($this->response['status']>=0) {
	    $this->downloadLabel();
	}
	return $this->response;
    }
}
?>
