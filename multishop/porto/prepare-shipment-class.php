<?php
require_once('config.php');
require_once('conversions.php');
//need to use tfpdf as fpdf does not use UTF-8
require_once('tfpdf/tfpdf.php');
//map tFPDF to FPDF so that fpdi can extend the class
class FPDF extends tFPDF {};
require_once('fpdf/fpdi.php');
require_once('dhl-php-class.php');
require_once('post-php-class.php');

class PrepareShipment {
    function __construct($order_id)
    {
		$this->order_id=$order_id;
	
		$this->response = array();
		$this->response['status'] = 0;
		$this->response['error_message'] = '';
		$this->response['warning_message'] = '';
		$this->response['carrier'] = '';
    }

    private function send_email() {
	$body = "Order-ID:" . $this->order_id . "\n";
	$body .= "Status:" . $this->response['status'] . "\n\n";
	if ($this->response['status']<0) {
	    $type='ERROR';
	    $recipient=ERROR_EMAIL_RECIPIENT;
	    $body .= "Error Messages:\n" . $this->response['error_message'] ."\n\n";
	    $body .= "Warning Messages:\n" . $this->response['warning_message'] ."\n\n";
	} else {
	    $type='Warning';
	    $recipient=WARNING_EMAIL_RECIPIENT;
	    $body .= "Warning Messages:\n" . $this->response['warning_message'] ."\n\n";
	}

	$subject = "$type: automatic shipment generation";
	
	$headers = "From: " . EMAIL_SENDER  . "\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/plain; charset=utf-8\r\n";
	$headers .="Content-Transfer-Encoding: 8bit";
	
	$body=htmlspecialchars_decode($body,ENT_QUOTES);//optional
	mail($recipient,  "=?utf-8?B?".base64_encode($subject)."?=", $body, $headers);
    }
    
    private function calculate_parcels ($export_details) {
	global $product_types, $parcel_types, $size_mapping;
	$positions = $export_details['positions'];
	$export_details['total_sum'] = 0;
	$export_details['invoice_date'] = date('Y-m-d');;
	$found=array();
	$weight=0;
	$i=0;
	foreach ($positions as &$position) {
	    $sku_match=false;
	    $i++;
	    //FIXME
	    foreach ($product_types as $product_type) {
		if (!strncmp($product_type['sku'], $position['sku'], strlen($product_type['sku']))) {
		    $sku_match=true;
		    $position['goods_description'] = substr($position['goods_description'],0,26);
		    $position['customs_commodity_code'] = $product_type['customs_commodity_code'];
		    $position['net_weight_kg'] = $product_type['net_weight_kg'] * $position['item_amount'];
		    $position['gross_weight_kg'] = $product_type['gross_weight_kg'] * $position['item_amount'];
		    $export_details['total_sum'] += $position['customs_value'];
		    $weight += $position['gross_weight_kg'];
		    $position['size'] = $product_type['size'];
		    if(array_key_exists($product_type['size'],$found)) {
			$found[$product_type['size']] += $position['item_amount'];
		    } else {
			$found[$product_type['size']] = $position['item_amount'];
		    }
		}
	    }
	    if(!$sku_match) {
		$this->response['status']=-1;
		$this->response['error_message']= __FILE__.": SKU " . $position['sku'] . " could not be found in \$product_types. Cannot continue. Needs to be added!!\n";
		return $this->response;
	    }
	}

	if($i>5) {
	    $this->response['warning_message'] .= " - " . __FILE__ . ": More than 5 different items found. Only displaying first 5 as there is no more space.\n";
	    $positions=array_slice($positions,0,5);
	}
			
	$total=0;
	foreach($found as $k => $f) {
	    $total += $f * $size_mapping[$k];
	}
	$parcel='';
	foreach($parcel_types as $name => $type) {
	    if ($type['micro_units'] >= $total) {
		$parcel=$type;
		$parcel['id']=$name;
		$parcel['weight_kg']=$weight;
		break;
	    }
	}
	if(!$parcel) {
	    $this->response['status'] = -1;
	    $this->response['error_message'] = __FILE__ . ": Shipment (".$this->order_id.") is too big for one parcel. Cannot process. Please do it manually.";
	    return $this->response;
	} else if ($weight>MAX_PARCEL_WEIGHT) {
	    $this->response['status'] = -1;
	    $this->response['error_message'] = __FILE__ . ": Shipment (".$this->order_id.") is too heavy for one parcel ($weight kg). Cannot process. Please do it manually.";
	    return $this->response;
	}
	$this->parcel = $parcel;
	$this->export_details=$export_details;
	$this->export_details['positions'] = $positions;
	
    }
    private function prepare_address($customer) {
	global $laender;
	$customer_details=array();
	$remainder=''; 
	$customer_details['country_ISO_code']=$customer['country_ISO_code'];
	
	$field_length=$this->parcel_service_selector($customer_details['country_ISO_code']);

	//DIRKs Kivitendo address line remapping
	if ($customer['street'] == "")
	{
	    $customer['street'] = $customer['department'];
	    $customer['department']="";
	}

	$customer_details['reference_number'] = $this->order_id;
	$customer_details['name'] = $customer['name'];
	if(strlen($customer_details['name']) > $field_length) {
	    $remainder = substr($customer_details['name'], $field_length);
	    $customer_details['name'] = substr($customer_details['name'],0,$field_length);
	}
	$special=false;
	if(!strcmp($customer['country'], "Deutschland")) { //check Postfiliale and Packstation
	    if (!strncmp("Postfiliale", $customer['street'], strlen("Postfiliale"))){
		$customer_details['post_kundennumber'] = $customer['department'];
		$customer_details['postfiliale_number'] = filter_var($customer['street'], FILTER_SANITIZE_NUMBER_INT);
		$special=true;
	    } else if (!strncmp("Packstation", $customer['street'], strlen("Packstation"))){
		$customer_details['post_kundennumber'] = $customer['department'];
		$customer_details['packstation_number'] = filter_var($customer['street'], FILTER_SANITIZE_NUMBER_INT); 
		$special=true;
	    }
	} 
	if(!$special) {
	    $customer_details['line2']='';
	    $customer_details['line3']='';
	    $address_start='';

	    //if street name is too long, move part truncate overlapping front and save it in line 3
	    $customer_details['street_name'] = $customer['street'];
	    if(strlen($customer_details['street_name']) > $field_length){
		$index=strlen($customer_details['street_name'])-$field_length;
		for(;$index<strlen($customer_details['street_name']);$index++) {
		    if($customer_details['street_name'][$index]==' ') break;
		}
		if($index == $field_length) {
		    $this->response['status'] = -1;
		    $this->response['error_message'] = __FILE__ . ": Impossible to split street name." .
			" Cannot process. Please handle manually. (1)\n";
		    $this->customer_details = $customer_details;
		    return $this->response;
		}

		$address_start = substr($customer_details['street_name'],0,$index);
		$customer_details['street_name'] = substr($customer_details['street_name'],$index+1);
	    }
	    if(strlen($address_start) > $field_length){
		$index=strlen($address_start)-$field_length;
		for(;$index<strlen($address_start);$index++) {
		    if($address_start[$index]==' ') break;
		}
		if($index == $field_length) {
		    $this->response['status'] = -1;
		    $this->response['error_message'] = __FILE__ . ": Impossible to split street name." .
			" Cannot process. Please handle manually. (2)\n";
		    $this->customer_details = $customer_details;
		    return $this->response;
		}
	        $customer_details['line2']=substr($address_start,0,$index);
		$address_start = substr($address_start,$index+1);
	    }
	    //FIXME: Implement parsing???
	    $customer_details['street_number'] = '';

	    if($customer['department']) {
		$customer_details['line2'] = $customer['department'];
	    } 
	    if(strlen($remainder)) {
		if (strlen($customer_details['line2'])) { //append
		    $customer_details['line2'] = $remainder . ", " . $customer_details['line2'];
		} else {
		    $customer_details['line2'] = $remainder;
		}
	    }
	    if(strlen($customer_details['line2']) > $field_length) {
		$remainder = substr($customer_details['line2'], $field_length);
		$customer_details['line2'] = substr($customer_details['line2'],0,$field_length);
		$customer_details['line3'] = $remainder;
	    }
	    
	    if(strlen($address_start)) {
		if(strlen($customer_details['line3'])) {
		    $customer_details['line3'] .= ", " . $address_start;
		} else {
		    $customer_details['line3'] .= $address_start;
		}
	    }
	    if($field_length==POST_MAX_ADDRESS_FIELD_LENGTH) {
		//1 line less for post. let's hope it fits because the lines are longer.....
		$customer_details['line3'] = $customer_details['line2'] . " " .  $customer_details['line3'];
	    }

	    if(strlen($customer_details['line3']) > $field_length) {
		$this->response['status'] = -1;
		$this->response['error_message'] = __FILE__ . ": Address is too long. Missing ".(strlen($customer_details['line3'])-$field_length)." characters of space." . 
		    " Cannot process. Please handle manually.\n";
		$this->customer_details = $customer_details;
		return $this->response;
	    }
	}
	$customer_details['zip'] = $customer['postcode'];
	$customer_details['city'] = $customer['city'];
	if(array_key_exists('email', $customer)) {
	    if (strlen($customer['email'])<=MAX_EMAIL_LENGTH) {
		$customer_details['email'] = $customer['email'];
	    } else {
		$customer_details['email'] = '';
	    }
	}
	$customer_details['telephone'] = substr($customer['telephone'],0,MAX_TELEPHONE_LENGTH);
	
	$customer_details['country'] = $customer['country'];

	$this->customer_details = $customer_details;
    }

    private function check_eu ($country_iso_code) {
	global $eu_country_codes;
	foreach ($eu_country_codes as $code) {
	    if (!strcmp($country_iso_code,$code)) {
		return 0;
	    }
	}
	return -1;
    }

    private function check_near_eu ($country_iso_code) {
	global $near_eu_country_codes;
	foreach ($near_eu_country_codes as $code) {
	    if (!strcmp($country_iso_code,$code)) {
		return 0;
	    }
	}
	return -1;
    }

    private function parcel_service_selector ($country_iso_code) {
	global $de_country_code;
	$weight=$this->parcel['weight_kg'];
	if ($weight <= 0.5) {
	    if(!strcmp($country_iso_code, $de_country_code)) { //is DE
		$this->response['carrier']='dhl';
		$this->response['type']='no_export_doc';
	    } else {
		$this->response['carrier']='post';
		$this->response['type']='grossbrief_international_einschreiben';
	    }
	} else if ($weight <=1) {
	    if (!$this->check_eu($country_iso_code)) { //is EU
		$this->response['carrier']='dhl';
		$this->response['type']='no_export_doc';
	    } else {
		$this->response['carrier']='post';
		$this->response['type']='maxibrief_international_1kg';
	    }
	} else if ($weight <=2) {
	    if (!$this->check_eu($country_iso_code)) { //is EU
		$this->response['carrier']='dhl';
		$this->response['type']='no_export_doc';
	    } 
	    else if (!$this->check_near_eu($country_iso_code)) { //is near EU, e.g. CH
		$this->response['carrier']='dhl';
		$this->response['type']='export_doc';
	    } else {
		$this->response['carrier']='post';
		$this->response['type']='maxibrief_international_2kg';
	    }
	} else { //>2kg
	    if (!$this->check_eu($country_iso_code)) { //is EU
		$this->response['carrier']='dhl';
		$this->response['type']='no_export_doc';
	    } else {                                   //is ex-EU
		$this->response['carrier']='dhl';
		$this->response['type']='export_doc';
	    }
	    
	}
	if($this->response['carrier'] == 'post') {
	    return POST_MAX_ADDRESS_FIELD_LENGTH;
	} else {
	    return DHL_MAX_ADDRESS_FIELD_LENGTH;
	}
    }

    private function create_labels() {
	global $post_stamps;
	switch ($this->response['carrier']) {
	    case 'dhl':
		$dhl = new DHLBusinessShipment($this->order_id);
		switch ($this->response['type']) {
		    case 'no_export_doc':
			$response = $dhl->createBusinessShipment($this->customer_details, $this->parcel);
			break;
		    case 'export_doc':
			$response = $dhl->createBusinessShipment($this->customer_details, $this->parcel, $this->export_details);
			break;
		    default:
			$this->response['status'] = -1;
			$this->response['error_message'] = __FILE__ . ": bug: unknown dhl type determined (".$this->response['type'].") Needs to be fixed. Cannot process. Handle manually.\n";
			return $this->response;
			break;
		}
		break;
	    case 'post':
		$internetmarke = new PostInternetmarke();
		$response = $internetmarke->buyPostage($post_stamps[$this->response['type']],$this->customer_details, $this->parcel, $this->export_details);
		break;
	    default:
		$this->response['status'] = -1;
		$this->response['error_message'] = __FILE__ . ": bug: unknown carrier determined (".$this->response['carrier'].") Needs to be fixed. Cannot process. Handle manually.\n";
		return $this->response;
		break;
	    }
	$response['carrier']=$this->response['carrier'];
	if (array_key_exists('warning_message', $response) && strlen($response['warning_message'])) {
	    $response['warning_message'] = $this->response['warning_message'] . ' - ' . $response['warning_message'] ;
	} else {
	    $response['warning_message'] = $this->response['warning_message'];
	}
	    
	$this->response = $response;
	
	return $this->response;
    }

    function handle_shipment ($export_details, $customer) {
	$this->calculate_parcels($export_details);
	if ($this->response['status']>=0) {
	    $this->prepare_address($customer);
	}
	if ($this->response['status']>=0) {
	    $this->create_labels();
	}
	if ($this->response['error_message'] || $this->response['warning_message']) {
	    $this->send_email($this->order_id, $this->response);
	}
	return $this->response;
    }
}


?>
