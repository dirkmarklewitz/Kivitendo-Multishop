<?php

$LAND = array(	"DE" => "Deutschland",

				"AT" => "�sterreich",
				"BE" => "Belgien",
				"BG" => "Bulgarien",
				"CY" => "Zypern",
				"CZ" => "Tschechische Republik",
				"DK" => "D�nemark",
				"EE" => "Estland",
				"GR" => "Griechenland",
				"ES" => "Spanien",
				"FI" => "Finnland",
				"FR" => "Frankreich",
				"FX" => "Frankreich",
				"HU" => "Ungarn",
				"IE" => "Irland",
				"IT" => "Italien",
				"LT" => "Litauen",
				"LU" => "Luxemburg",
				"LV" => "Lettland",
				"MT" => "Malta",
				"NL" => "Niederlande",
				"PL" => "Polen",
				"PT" => "Portugal",
				"RO" => "Rum�nien",
				"SE" => "Schweden",
				"SI" => "Slowenien",
				"SK" => "Slowakei",
				"UK" => "United Kingdom",
				"GB" => "United Kingdom",

				"AL" => "Albanien",
				"AU" => "Australien",
				"BR" => "Brasilien",
				"CH" => "Schweiz",
				"CA" => "Kanada",
				"GF" => "Franz�sisch Guyana",
				"GG" => "Guernsey",
				"GI" => "Gibraltar",
				"HK" => "Hong Kong",
				"IN" => "Indien",
				"JE" => "Jersey (Kanalinsel)",
				"KR" => "S�d Korea",
				"MC" => "Monaco",
				"MX" => "Mexiko",
				"MY" => "Malaysia",
				"NG" => "Nigeria",
				"NO" => "Norwegen",
				"NZ" => "Neuseeland",
				"PH" => "Philippinen",
				"RU" => "Russische F�deration",
				"SA" => "Saudi Arabien",
				"SG" => "Singapur",
				"TH" => "Thailand",
				"TR" => "T�rkei",
				"TW" => "Republik China (Taiwan)",
				"US" => "USA",
				"XK" => "Kosovo, Republik");
				
$TAXID = array(	"DE" => 0,	// Steuerschluessel Deutschland

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
				"HU" => 2,
				"IE" => 2,
				"IT" => 2,
				"LT" => 2,
				"LU" => 2,
				"LV" => 2,
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
					"MFN" => "H�ndler");


$paramsOrders = array(	"MarketplaceId", "SalesChannel",
						"OrderType", "OrderStatus", "SellerOrderId", "AmazonOrderId", "FulfillmentChannel",
						"ShipmentServiceLevelCategory", "ShipServiceLevel",
						"Amount", "CurrencyCode", "PaymentMethod",
						"NumberOfItemsShipped", "NumberOfItemsUnshipped",
						"PurchaseDate", "LastUpdateDate",
						"BuyerName",
						"Title", "Name", "AddressLine1", "AddressLine2", "PostalCode", "City", "StateOrRegion", "CountryCode",
						"recipient-title", "recipient-name", "ship-address-1", "ship-address-2", "ship-address-3", "ship-postal-code", "ship-city", "ship-state", "ship-country", "ship-phone-number",
						"BuyerEmail", "Phone", "OrderComment", "carrier", "tracking-number", "Language");

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