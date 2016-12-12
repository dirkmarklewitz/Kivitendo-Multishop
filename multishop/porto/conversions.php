<?php
require_once('config.php');

$eu_country_codes = array ( 
    'BE', 'BG', 'CZ', 'DE', 'DK', 'EE', 'IE', 'EL', 'ES', 'FR', 'HR', 'IT', 'CY',
	'LV', 'LT', 'LU', 'HU', 'MT', 'NL', 'AT', 'PL', 'PT', 'RO', 'SI', 'SK',
	'FI', 'SE', 'GB'
);

$near_eu_country_codes = array ( 
    'AX', 'AD', 'AL', 'BY', 'BA', 'EA', 'FO', 'GE', 'GI', 'GL', 'IS', 'GG', 'JE', 'IM', 'IC', 'XK', 'LI', 'MK', 'MD', 'ME', 'NO', 'CH', 'SM', 'RS'
);

$de_country_code = "DE";

//FIXME
$size_mapping = array (
    'T60mic' => 1,
    'T60adap' => 0.5,
    'T60psu' => 0.5,
    'T60rd' => 1,
    'Cmob6-garde' => 1,
    'T60cab' => 4,
    'Tffcab' => 4,
    'T1921cab' => 8)
;

//FIXME
$parcel_types = array(
    'micro_sleeve' => array (
	'name' => 'Versandtasche A4',
	'micro_units' => 1,
        'height_cm' => 15,
	'width_cm' => 15,
        'length_cm' => 25
    ),
    'box_2_micros' => array (
	'name' => 'Karton 250x150x150',
	'micro_units' => 2,
        'height_cm' => 15,
	'width_cm' => 15,
        'length_cm' => 25
    ),
    'box_60s_cable' => array (
	'name' => 'Karton 300x215x180',
	'micro_units' => 5,
        'height_cm' => 18,
	'width_cm' => 22,
        'length_cm' => 30
    ),
    'box_1921_cab' => array (
	'name' => 'Karton 300x250x200',
	'micro_units' => 8, 
        'height_cm' => 24,
	'width_cm' => 22,
        'length_cm' => 40
    ),
    'box_2x1921_cab' => array (
	'name' => 'Karton 500x300x200',
	'micro_units' => 16, 
        'height_cm' => 20,
	'width_cm' => 30,
        'length_cm' => 50
    ),
    'half_carton' => array (
	'name' => 'Halber Großkarton',
	'micro_units' => 22,
        'height_cm' => 16,
	'width_cm' => 51,
        'length_cm' => 51
    ),
    'carton' => array (
	'name' => 'Ganzer Großkarton',
	'micro_units' => 44,
        'height_cm' => 31,
	'width_cm' => 51,
        'length_cm' => 51
    )
);

$product_types = array (
    'T60mic' => array (
	'sku' => 'T60mic',
	'size' => 'T60mic',
	'customs_commodity_code' => '85183095',
	'net_weight_kg' => 0.4,
        'gross_weight_kg' => 0.5
    ),
    'T60mob' => array (
	'sku' => 'T60mob',
	'size' => 'T60cab',
	'customs_commodity_code' => '85171800',
	'net_weight_kg' => 1.1,
        'gross_weight_kg' => 1.7
    ),
    'T60cab' => array (
	'sku' => 'T60cab',
        'size' => 'T60cab',
	'customs_commodity_code' => '85171800',
	'net_weight_kg' => 1.0,
        'gross_weight_kg' => 1.6
    ),
    'Tffcab' => array (
	'sku' => 'Tffcab',
        'size' => 'Tffcab',
	'customs_commodity_code' => '85171800',
	'net_weight_kg' => 0.9,
        'gross_weight_kg' => 1.4
    ),
    'Tpmfcab' => array (
	'sku' => 'Tpmfcab',
        'size' => 'T60cab',
	'customs_commodity_code' => '85171800',
	'net_weight_kg' => 0.9,
        'gross_weight_kg' => 1.4
    ),
    'T1921cab' => array (
	'sku' => 'T1921cab',
        'size' => 'T1921cab',
	'customs_commodity_code' => '85171800',
	'net_weight_kg' => 1.9,
        'gross_weight_kg' => 2.3
    ),
    'Cmob6-garde' => array (
	'sku' => 'Cmob6-garde',
	'size' => 'Cmob6-garde',
	'customs_commodity_code' => '4202310090',
	'net_weight_kg' => 0.1,
        'gross_weight_kg' => 0.2
    ),
    'T60adap' => array (
	'sku' => 'T60adap',
	'size' => 'T60adap',
        'customs_commodity_code' => '8504403090',
	'net_weight_kg' => 0.1,
        'gross_weight_kg' => 0.2
    ),
    'T60psu' => array (
	'sku' => 'T60psu',
	'size' => 'T60psu',
        'customs_commodity_code' => '8504403090',
	'net_weight_kg' => 0.1,
        'gross_weight_kg' => 0.2
    ),
    'T60rd' => array (
	'sku' => 'T60rd',
	'size' => 'T60rd',
        'customs_commodity_code' => '8517709000',
	'net_weight_kg' => 0.2,
        'gross_weight_kg' => 0.3
    )
);


?>
