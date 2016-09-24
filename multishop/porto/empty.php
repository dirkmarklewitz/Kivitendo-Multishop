<?php

require_once('prepare-shipment-class.php');
require_once('./php-mailer/class.phpmailer.php');

$order_id='';

$export_details = array (
        'customs_currency' => '',

	    'positions' => array (
		0 => array (
		    'sku' => '',
			'item_amount' => 1,
			'goods_description' => '',
			'customs_value' => 
		),/*
		    1 => array (
			'sku' => 'T60mob-orange-EU',
			    'item_amount' => 1,
			    'goods_description' => 'Opis 60s mobile orange',
			    'customs_value' => 109
		    )*/
	    )
);

$customer = array (
	    'name' => '',
	    'department' => '',
	    'street' => '',
	    'postcode' => '',
	    'city' => '',
	    'country_ISO_code' => '',
	    'country' => '',
	    'telephone' => '',
	    'email' => '',
);


$shipment = new PrepareShipment($order_id);
$response = $shipment->handle_shipment($export_details, $customer);


var_dump($shipment);
print("\nResponse:\n");
var_dump($response);

if ($response['status']==0) {

    $email = new PHPMailer();
    $email->From      = 'test-dhl-post@opis-tech.com';
    $email->FromName  = 'Test DHL Post';
    $email->Subject   = '[auto versand] '; 
    foreach($export_details['positions'] as $position) {
	$email->Subject .= $position['item_amount']."#".$position['sku']." | ";
    }
    $email->Body      = "Shipment Number: " . $response['shipment_number'];
    $email->AddAddress( 'contact@opis-tech.com' );
    
    $email->AddAttachment( LABEL_DOWNLOAD_PATH . $response['shipment_label_file'] );
    if (array_key_exists('export_document_file', $response) && strlen($response['export_document_file'])) {
	$email->AddAttachment( LABEL_DOWNLOAD_PATH . $response['export_document_file'] );
    }
    
    return $email->Send();
}

?>
