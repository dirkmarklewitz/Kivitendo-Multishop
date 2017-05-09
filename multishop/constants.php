<?php

$LAND = array(	"DE" => "Deutschland",

				"AT" => "Österreich",
				"BE" => "Belgien",
				"BG" => "Bulgarien",
				"CY" => "Zypern",
				"CZ" => "Tschechische Republik",
				"DK" => "Dänemark",
				"EE" => "Estland",
				"GR" => "Griechenland",
				"ES" => "Spanien",
				"FI" => "Finnland",
				"FR" => "Frankreich",
				"FX" => "Frankreich",
				"HR" => "Kroatien",				
				"HU" => "Ungarn",
				"IE" => "Irland",
				"IT" => "Italien",
				"LT" => "Litauen",
				"LU" => "Luxemburg",
				"LV" => "Lettland",
				"MC" => "Monaco",
				"MT" => "Malta",
				"NL" => "Niederlande",
				"PL" => "Polen",
				"PT" => "Portugal",
				"RO" => "Rumänien",
				"SE" => "Schweden",
				"SI" => "Slowenien",
				"SK" => "Slowakei",
				"GB" => "United Kingdom",				
				"UK" => "United Kingdom",

				"AD" => "Andorra",
				"AE" => "Vereinigte Arabische Emirate",
				"AL" => "Albanien",
				"AR" => "Argentinien",
				"AU" => "Australien",
				"BH" => "Bahrain",
				"BR" => "Brasilien",
				"CA" => "Kanada",
				"CH" => "Schweiz",
				"CK" => "Cook Inseln",
				"CL" => "Chile",
				"CN" => "China",
				"EG" => "Ägypten",
				"GF" => "Französisch Guyana",
				"GG" => "Guernsey",
				"GI" => "Gibraltar",
				"GP" => "Guadeloupe",
				"HK" => "Hong Kong",
				"IL" => "Israel",
				"IN" => "Indien",
				"IS" => "Island",
				"JE" => "Jersey (Kanalinsel)",
				"KR" => "Süd Korea",
				"LI" => "Liechtenstein",
				"MD" => "Moldawien",
				"ML" => "Mali",
				"MX" => "Mexiko",
				"MY" => "Malaysia",
				"NC" => "Neukaledonien",
				"NG" => "Nigeria",
				"NO" => "Norwegen",
				"NZ" => "Neuseeland",
				"PH" => "Philippinen",
				"PR" => "Puerto Rico",
				"QA" => "Katar",
				"RE" => "Réunion",
				"RU" => "Russische Föderation",
				"SA" => "Saudi Arabien",
				"SG" => "Singapur",
				"TH" => "Thailand",
				"TR" => "Türkei",
				"TW" => "Republik China (Taiwan)",
				"US" => "USA",
				"XK" => "Kosovo, Republik");
				
$TAXID = array(	"EU_ID" => 1,	// EU mit USt-ID Nummer
				"EU_OHNE" => 2,	// EU ohne USt-ID Nummer
				"WORLD" => 3,	// Außerhalb EU
				"DEUT" => 4,	// Inland

				"DE" => 4,	// Steuerschluessel Deutschland

				"AT" => 2,	// Steuerschluessel EU
				"BE" => 2,
				"BG" => 2,
				"CY" => 2,
				"CZ" => 2,
				"DK" => 2,
				"EE" => 2,
				"GR" => 2,
				"ES" => 2,
				"FI" => 2,
				"FR" => 2,
				"FX" => 2,
				"HR" => 2,
				"HU" => 2,
				"IE" => 2,
				"IT" => 2,
				"LT" => 2,
				"LU" => 2,
				"LV" => 2,
				"MC" => 2,
				"MT" => 2,
				"NL" => 2,
				"PL" => 2,
				"PT" => 2,
				"RO" => 2,
				"SE" => 2,
				"SI" => 2,
				"SK" => 2,
				"UK" => 2,
				"GB" => 2,
				
				"CH" => 3,		// Steuerschluessel Welt (also keine USt.)
				"GF" => 3,
				"GI" => 3);

$VERSAND = array(	"AFN" => "Amazon",
					"MFN" => "Händler");


$paramsOrders = array(	"MarketplaceId" => "MarketplaceId", "SalesChannel" => "SalesChannel",
						"OrderType" => "OrderType", "OrderStatus" => "OrderStatus", "SellerOrderId" => "SellerOrderId", "AmazonOrderId" => "AmazonOrderId", "FulfillmentChannel" => "FulfillmentChannel",
						"ShipmentServiceLevelCategory" => "ShipmentServiceLevelCategory", "ShipServiceLevel" => "ShipServiceLevel",
						"Amount" => "Amount", "CurrencyCode" => "CurrencyCode", "PaymentMethod" => "PaymentMethod",
						"NumberOfItemsShipped" => "NumberOfItemsShipped", "NumberOfItemsUnshipped" => "NumberOfItemsUnshipped",
						"PurchaseDate" => "PurchaseDate", "LastUpdateDate" => "LastUpdateDate",
						"BuyerName" => "BuyerName",
						"Title" => "Title", "Name" => "Name", "AddressLine1" => "AddressLine1", "AddressLine2" => "AddressLine2", "PostalCode" => "PostalCode", "City" => "City", "StateOrRegion" => "StateOrRegion", "CountryCode" => "CountryCode",
						"recipient-title" => "Title", "recipient-name" => "Name", "ship-address-1" => "AddressLine1", "ship-address-2" => "AddressLine2", "ship-address-3" => "", "ship-postal-code" => "PostalCode", "ship-city" => "City", "ship-state" => "StateOrRegion", "ship-country" => "CountryCode", "ship-phone-number" => "Phone",
						"BuyerEmail" => "BuyerEmail", "Phone" => "Phone", "OrderComment" => "OrderComment", "carrier" => "carrier", "tracking-number" => "tracking-number", "Language" => "Language");

$paramsOrderItems = array(	"OrderItemId", "SellerSKU", "ASIN", "Title",
							"ItemPrice", "ItemTax", "PromotionDiscount", "ShippingPrice", "ShippingTax", "ShippingDiscount", "GiftWrapPrice", "GiftWrapTax",
							"QuantityOrdered", "QuantityShipped");


$paramsOrdersReportFBA = array(	"MarketplaceId" => "", "SalesChannel" => "sales-channel",
								"OrderType" => "ship-service-level", "OrderStatus" => "", "SellerOrderId" => "merchant-order-id", "AmazonOrderId" => "amazon-order-id", "FulfillmentChannel" => "fulfillment-channel",
								"ShipmentServiceLevelCategory" => "ship-service-level", "ShipServiceLevel" => "ship-service-level",
								"Amount" => "item-price", "CurrencyCode" => "currency", "PaymentMethod" => "",
								"NumberOfItemsShipped" => "quantity-shipped", "NumberOfItemsUnshipped" => "",
								"PurchaseDate" => "purchase-date", "LastUpdateDate" => "shipment-date",
								"BuyerName" => "buyer-name",
								"Title" => "", "Name" => "buyer-name", "AddressLine1" => "bill-address-1", "AddressLine2" => "bill-address-2", "PostalCode" => "bill-postal-code", "City" => "bill-city", "StateOrRegion" => "bill-state", "CountryCode" => "bill-country",
								"recipient-title" => "", "recipient-name" => "recipient-name", "ship-address-1" => "ship-address-1", "ship-address-2" => "ship-address-2", "ship-address-3" => "ship-address-3", "ship-postal-code" => "ship-postal-code", "ship-city" => "ship-city", "ship-state" => "ship-state", "ship-country" => "ship-country", "ship-phone-number" => "ship-phone-number",
								"BuyerEmail" => "buyer-email", "Phone" => "buyer-phone-number", "OrderComment" => "", "carrier" => "carrier", "tracking-number" => "tracking-number", "Language" => "sales-channel");

$paramsOrderItemsReportFBA = array(	"OrderItemId" => "amazon-order-item-id", "SellerSKU" => "sku", "ASIN" => "", "Title" => "product-name",
									"ItemPrice" => "item-price", "ItemTax" => "item-tax", "PromotionDiscount" => "item-promotion-discount", "ShippingPrice" => "shipping-price", "ShippingTax" => "shipping-tax", "ShippingDiscount" => "ship-promotion-discount", "GiftWrapPrice" => "gift-wrap-price", "GiftWrapTax" => "gift-wrap-tax",
									"QuantityOrdered" => "quantity-shipped", "QuantityShipped" => "quantity-shipped");


$paramsOrdersReportMFN = array(	"MarketplaceId" => "", "SalesChannel" => "sales-channel",
								"OrderType" => "ship-service-level", "OrderStatus" => "", "SellerOrderId" => "", "AmazonOrderId" => "order-id", "FulfillmentChannel" => "",
								"ShipmentServiceLevelCategory" => "ship-service-level", "ShipServiceLevel" => "ship-service-level",
								"Amount" => "item-price", "CurrencyCode" => "currency", "PaymentMethod" => "",
								"NumberOfItemsShipped" => "", "NumberOfItemsUnshipped" => "quantity-purchased",
								"PurchaseDate" => "purchase-date", "LastUpdateDate" => "payments-date",
								"BuyerName" => "buyer-name",
								"Title" => "", "Name" => "buyer-name", "AddressLine1" => "bill-address-1", "AddressLine2" => "bill-address-2", "PostalCode" => "bill-postal-code", "City" => "bill-city", "StateOrRegion" => "bill-state", "CountryCode" => "bill-country",
								"recipient-title" => "", "recipient-name" => "recipient-name", "ship-address-1" => "ship-address-1", "ship-address-2" => "ship-address-2", "ship-address-3" => "ship-address-3", "ship-postal-code" => "ship-postal-code", "ship-city" => "ship-city", "ship-state" => "ship-state", "ship-country" => "ship-country", "ship-phone-number" => "ship-phone-number",
								"BuyerEmail" => "buyer-email", "Phone" => "buyer-phone-number", "OrderComment" => "", "carrier" => "", "tracking-number" => "", "Language" => "sales-channel");

$paramsOrderItemsReportMFN = array(	"OrderItemId" => "order-item-id", "SellerSKU" => "sku", "ASIN" => "", "Title" => "product-name",
									"ItemPrice" => "item-price", "ItemTax" => "item-tax", "PromotionDiscount" => "", "ShippingPrice" => "shipping-price", "ShippingTax" => "shipping-tax", "ShippingDiscount" => "", "GiftWrapPrice" => "", "GiftWrapTax" => "",
									"QuantityOrdered" => "quantity-purchased", "QuantityShipped" => "");
?>