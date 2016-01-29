<?php
////////////////////COMMON////////////////////////////
// your company info
$sender_info = array(
    'company_name'    => '',
    'street_name'     => '',
    'street_number'   => '',
    'zip'             => '',
    'country_ISO_code'=> '',
    'city'            => '',
    'email'           => '',
    'phone'           => '',
    'internet'        => '',
    'contact_person'  => ''
    
);

define ( 'OVERALL_GOODS_DESCRIPTION', 'phones and accessories');

define ( 'SEND_WARNING_EMAILS', true);
define ( 'ERROR_EMAIL_RECIPIENT', '');
define ( 'PORTOKASSE_EMAIL_RECIPIENT', '');
define ( 'WARNING_EMAIL_RECIPIENT', '');
define ( 'EMAIL_SENDER', '');
define ( 'MAX_PARCEL_WEIGHT', 15);
define ( 'MAX_EMAIL_LENGTH', 20);
define ( 'MAX_TELEPHONE_LENGTH', 20);
define ( 'LABEL_DOWNLOAD_PATH', './labels/');
define ( 'TEMP_PATH', 'tmp/');

////////////////////POST//////////////////////////////
define ( 'FPDF_FONTPATH', 'tfpdf/font/' );
$post_api_credentials = array(
    /* SANDBOX */
/*
    'partner_id' => 'AOHOP',
    'key_phase' => 1,
    'key_dpwn_marketplace' => '',
    'username' => '',
    'password' => ''

  /* PRODUCTION */

    'partner_id' => 'AOHOP',
    'key_phase' => 1,
    'key_dpwn_marketplace' => '',
    'username' => '',
    'password' => ''
/**/
);

define ( 'POST_API_URL', 'https://internetmarke.deutschepost.de/OneClickForAppV3?wsdl');
define ( 'POST_URL', 'https://internetmarke.deutschepost.de/OneClickForAppV3');

if (strtotime("now")<strtotime("2016-01-01")) {
    define ( 'POST_PRODUCT_LIST_VERSION', '32'); 
    //New List locations can be found at https://www.deutschepost.de/content/dam/mlm.nf/dpag/technische_downloads/update_internetmarke/ppl_update.xml
    $post_stamps = array (
	'grossbrief_international_einschreiben' => array (
	    'id' => 11056,
		'price' => 560 //in cents
	),
	    'maxibrief_international_1kg' => array (
		'id' => 11076,
	            'price' => 915 //in cents
	    ),
	    'maxibrief_international_2kg' => array (
		'id' => 11096,
		    'price' => 1915 //in cents
	    )
    );
} else {
    define ( 'POST_PRODUCT_LIST_VERSION', '33'); 
    //New List locations can be found at https://www.deutschepost.de/content/dam/mlm.nf/dpag/technische_downloads/update_internetmarke/ppl_update.xml
    $post_stamps = array (
	'grossbrief_international_einschreiben' => array (
	    'id' => 11056,
		'price' => 620 //in cents
	),
	    'maxibrief_international_1kg' => array (
		'id' => 11076,
		    'price' => 950 //in cents
	    ),
	    'maxibrief_international_2kg' => array (
		'id' => 11096,
		    'price' => 1950 //in cents
	    )
    );
}

define( 'POST_EXPORT_DOC_POSTFIX', '-exp');
define ('POST_MAX_ADDRESS_FIELD_LENGTH', 36);
    
define ( 'POST_VOUCHER_LAYOUT', 'FrankingZone');

define ( 'POST_BALANCE_REFILL_URL', 'https://internetmarke.deutschepost.de/internetmarke/start.do?user.showLogin=true&user.name=dhl@opis-tech.com' );
define ( 'POST_BALANCE_REFILL_THRESHOLD', 10000); //in cents

//define ( 'POST_BARCODE_API_URL', 'https://api.havenondemand.com/1/api/sync/recognizebarcodes/v1' );
//define ( 'POST_BARCODE_API_KEY', 'ba4f9ff0-789f-4a39-9932-67cc33cdf22f');

//Brief International Schein to fill in
define ( 'POST_EMPTY_BRIEF_INTERNATIONAL_PDF', './fpdf/post_empty_brief_international.pdf');

////////////////////DHL//////////////////////////

define( 'DHL_API_URL', 'https://cig.dhl.de/cig-wsdls/com/dpdhl/wsdl/geschaeftskundenversand-api/1.0/geschaeftskundenversand-api-1.0.wsdl' );
define( 'DHL_SANDBOX_URL', 'https://cig.dhl.de/services/sandbox/soap' );
define( 'DHL_PRODUCTION_URL', 'https://cig.dhl.de/services/production/soap' );

define( 'DHL_EXPORT_DOC_POSTFIX', '-exp');
define( 'DHL_INVOICE_TYPE', 'commercial'); 
define( 'DHL_EXPORT_TYPE', 0); //0=other, 1=gift, 2=documents, 3=goods return
define( 'DHL_EXPORT_TYPE_DESCRIPTION', 'sale'); //only for 0=other
define( 'DHL_TERMS_OF_TRADE', 'DDU'); // delivered duty unpaid
define( 'DHL_COUNTRY_CODE_ORIGIN_GOODS', 'CN'); //CN=China
define ('DHL_MAX_ADDRESS_FIELD_LENGTH', 28);

//define ('DHL_MAX_ZIP_LENGTH', 5);

// your customer and api credentials from/for dhl
$dhl_api_credentials = array(
    /* SANDBOX */
/*    'user' => '', 
    'signature' => '', 
    'ekp' => '',
    'api_user'  => '',
    'api_password'  => '',
    'sandbox' => true,
    'log' => true

    /* PRODUCTION */

    'user' => '',
    'signature' => '', 
    'ekp' => '',
    'api_user'  => '',
    'api_password'  => '',
    'sandbox' => false,
    'log' => true
/**/
);
?>
