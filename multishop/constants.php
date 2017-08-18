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
				"BM" => "Bermuda",
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


$paramsOrders = array(	"MarketplaceId" => "MarketplaceId",    
						"SalesChannel" => "SalesChannel",

						"OrderType" => "OrderType",
						"OrderStatus" => "OrderStatus",
						"SellerOrderId" => "SellerOrderId",
						"AmazonOrderId" => "AmazonOrderId",
						"purchase-order-number" => "",
						"FulfillmentChannel" => "FulfillmentChannel",
						"IsReplacementOrder" => "IsReplacementOrder",
						"IsBusinessOrder" => "IsBusinessOrder",
						"IsPremiumOrder" => "IsPremiumOrder",
						"IsPrime" => "IsPrime",

						"ShipmentServiceLevelCategory" => "ShipmentServiceLevelCategory",
						"ShipServiceLevel" => "ShipServiceLevel",

						"Amount" => "Amount",
						"CurrencyCode" => "CurrencyCode",
						"price-designation" => "",
						"PaymentMethod" => "PaymentMethod",
						"PaymentMethodDetail" => "PaymentMethodDetail",	// Invoice ist hier das Zeichen für verspätete Verbuchung
																		// PaymentMethodDetails -> kann enthalten/ enthält mehrere PaymentMethodDetail
						"payments-date" => "",			// Datum, an dem die Bezahlung verifiziert ist (bzw. an dem die Rechnung gestellt wird (nicht das Faelligkeits der Rechnung))

						"NumberOfItems" => "",

						"PurchaseDate" => "PurchaseDate",
						"LastUpdateDate" => "LastUpdateDate",
						"LatestShipDate" => "LatestShipDate",
						"EarliestShipDate" => "EarliestShipDate",
						"shipment-date" => "",		// Datum, an dem die Ware verschickt wurde
						"reporting-date" => "",		// Datum, an dem die Infoemail von Amazon kommt
						"delivery-time-zone" => "",
						"delivery-Instructions" => "",

						"BuyerName" => "BuyerName",
						"Title" => "",
						"Name" => "",
						"AddressLine1" => "",
						"AddressLine2" => "",
						"AddressLine3" => "",
						"PostalCode" => "",
						"City" => "",
						"StateOrRegion" => "",
						"CountryCode" => "",
						"buyer-cst-number" => "",
						"buyer-vat-number" => "",

						"recipient-title" => "Title",
						"recipient-name" => "",
						"ship-address-1" => "",
						"ship-address-2" => "",
						"ship-address-3" => "",
						"ship-postal-code" => "",
						"ship-city" => "",
						"ship-state" => "",
						"ship-country" => "",
						"ship-phone-number" => "",
						
						"BuyerEmail" => "BuyerEmail",
						"Phone" => "Phone",
						"OrderComment" => "OrderComment",

						"carrier" => "",
						"tracking-number" => "",
						"estimated-arrival-date" => "",
						"fulfillment-center-id" => "",
						"fulfillment-channel" => "",

						"Language" => "SalesChannel",
						
						"ShippedByAmazonTFM" => "ShippedByAmazonTFM",
						"LatestDeliveryDate" => "LatestDeliveryDate",
						"EarliestDeliveryDate" => "EarliestDeliveryDate",
						
						"tax_number" => "",
						"tax_included" => ""
						);

$paramsOrderItems = array(	"OrderItemId" => "",
							"SellerSKU" => "",
							"ASIN" => "",
							"Title" => "",

							"shipment-id" => "",
							"shipment-item-id" => "",
							"merchant-order-item-id" => "",

							"ItemPrice" => "",
							"ItemTax" => "",
							"PromotionDiscount" => "",
							"ShippingPrice" => "",
							"ShippingTax" => "",
							"ShippingDiscount" => "",
							"GiftWrapPrice" => "",
							"GiftWrapTax" => "",

							"QuantityOrdered" => "",
							"QuantityShipped" => "",
							
							"SerialNumber" => ""
							);

$paramsOrdersReportFBA = array(	"MarketplaceId" => "",
								"SalesChannel" => "sales-channel",

								"OrderType" => "",
								"OrderStatus" => "",
								"SellerOrderId" => "merchant-order-id",
								"AmazonOrderId" => "amazon-order-id",
								"purchase-order-number" => "",
								"FulfillmentChannel" => "fulfillment-channel",
								"IsReplacementOrder" => "",
								"IsBusinessOrder" => "",
								"IsPremiumOrder" => "",
								"IsPrime" => "",

								"ShipmentServiceLevelCategory" => "",
								"ShipServiceLevel" => "ship-service-level",

								"Amount" => "",
								"CurrencyCode" => "currency",
								"price-designation" => "",
								"PaymentMethod" => "",
								"PaymentMethodDetail" => "",					// Invoice ist hier das Zeichen für verspätete Verbuchung
																				// PaymentMethodDetails -> kann enthalten/ enthält mehrere PaymentMethodDetail
								"payments-date" => "payments-date",				// Datum, an dem die Bezahlung verifiziert ist (bzw. an dem die Rechnung gestellt wird (nicht das Faelligkeits der Rechnung))

								"NumberOfItems" => "quantity-shipped",
								
								"PurchaseDate" => "purchase-date",
								"LastUpdateDate" => "",
								"LatestShipDate" => "",
								"EarliestShipDate" => "",
								"shipment-date" => "shipment-date",		// Datum, an dem die Ware verschickt wurde
								"reporting-date" => "reporting-date",	// Datum, an dem die Infoemail von Amazon kommt
								"delivery-time-zone" => "",
								"delivery-Instructions" => "",

								"BuyerName" => "",
								"Title" => "",
								"Name" => "buyer-name",
								"AddressLine1" =>"bill-address-1",
								"AddressLine2" => "bill-address-2",
								"AddressLine3" => "bill-address-3",
								"PostalCode" => "bill-postal-code",
								"City" => "bill-city",
								"StateOrRegion" => "bill-state",
								"CountryCode" => "bill-country",
								"buyer-cst-number" => "",
								"buyer-vat-number" => "",

								"recipient-title" => "",
								"recipient-name" => "recipient-name",
								"ship-address-1" => "ship-address-1",
								"ship-address-2" => "ship-address-2",
								"ship-address-3" => "ship-address-3",
								"ship-postal-code" => "ship-postal-code",
								"ship-city" => "ship-city",
								"ship-state" => "ship-state",
								"ship-country" => "ship-country",
								"ship-phone-number" => "ship-phone-number",

								"BuyerEmail" => "buyer-email",
								"Phone" => "buyer-phone-number",
								"OrderComment" => "",

								"carrier" => "carrier",
								"tracking-number" => "tracking-number",
								"estimated-arrival-date" => "estimated-arrival-date",
								"fulfillment-center-id" => "fulfillment-center-id",
								"fulfillment-channel" => "fulfillment-channel",

								"Language" => "sales-channel",
								
								"ShippedByAmazonTFM" => "",
								"LatestDeliveryDate" => "",
								"EarliestDeliveryDate" => "",
								
								"tax_number" => "",
								"tax_included" => ""
								);

$paramsOrderItemsReportFBA = array(	"OrderItemId" => "amazon-order-item-id",
									"SellerSKU" => "sku",
									"ASIN" => "",
									"Title" => "product-name",

									"shipment-id" => "shipment-id",
									"shipment-item-id" => "shipment-item-id",
									"merchant-order-item-id" => "merchant-order-item-id",

									"ItemPrice" => "item-price",
									"ItemTax" => "item-tax",
									"PromotionDiscount" => "item-promotion-discount",
									"ShippingPrice" => "shipping-price",
									"ShippingTax" => "shipping-tax",
									"ShippingDiscount" => "ship-promotion-discount",
									"GiftWrapPrice" => "gift-wrap-price",
									"GiftWrapTax" => "gift-wrap-tax",

									"QuantityOrdered" => "quantity-shipped",
									"QuantityShipped" => "quantity-shipped",
							
									"SerialNumber" => ""
									);

$paramsOrdersReportMFN = array(	"MarketplaceId" => "",
								"SalesChannel" => "sales-channel",
								
								"OrderType" => "",
								"OrderStatus" => "",
								"SellerOrderId" => "",
								"AmazonOrderId" => "order-id",
								"purchase-order-number" => "purchase-order-number",
								"FulfillmentChannel" => "",
								"IsReplacementOrder" => "",
								"IsBusinessOrder" => "is-business-order",
								"IsPremiumOrder" => "",
								"IsPrime" => "",

								"ShipmentServiceLevelCategory" => "ship-service-level",
								"ShipServiceLevel" => "ship-service-level",
								
								"Amount" => "",
								"CurrencyCode" => "currency",
								"price-designation" => "price-designation",
								"PaymentMethod" => "",
								"PaymentMethodDetail" => "",					// Invoice ist hier das Zeichen für verspätete Verbuchung
																				// PaymentMethodDetails -> kann enthalten/ enthält mehrere PaymentMethodDetail
								"payments-date" => "payments-date",				// Datum, an dem die Bezahlung verifiziert ist (bzw. an dem die Rechnung gestellt wird (nicht das Faelligkeits der Rechnung))

								"NumberOfItems" => "quantity-purchased",
								
								"PurchaseDate" => "purchase-date",
								"LastUpdateDate" => "",
								"LatestShipDate" => "delivery-end-date",
								"EarliestShipDate" => "delivery-start-date",
								"shipment-date" => "",		// Datum, an dem die Ware verschickt wurde
								"reporting-date" => "",		// Datum, an dem die Infoemail von Amazon kommt
								"delivery-time-zone" => "delivery-time-zone",
								"delivery-Instructions" => "delivery-Instructions",


								"BuyerName" => "",
								"Title" => "",
								"Name" => "buyer-name",
								"AddressLine1" => "bill-address-1",
								"AddressLine2" => "bill-address-2",
								"AddressLine3" => "bill-address-3",
								"PostalCode" => "bill-postal-code",
								"City" => "bill-city",
								"StateOrRegion" => "bill-state",
								"CountryCode" => "bill-country",
								"buyer-cst-number" => "buyer-cst-number",
								"buyer-vat-number" => "buyer-vat-number",
								
								"recipient-title" => "",
								"recipient-name" => "recipient-name",
								"ship-address-1" => "ship-address-1",
								"ship-address-2" => "ship-address-2",
								"ship-address-3" => "ship-address-3",
								"ship-postal-code" => "ship-postal-code",
								"ship-city" => "ship-city",
								"ship-state" => "ship-state",
								"ship-country" => "ship-country",
								"ship-phone-number" => "ship-phone-number",
								
								"BuyerEmail" => "buyer-email",
								"Phone" => "buyer-phone-number",
								"OrderComment" => "",

								"carrier" => "",
								"tracking-number" => "",
								"estimated-arrival-date" => "",
								"fulfillment-center-id" => "",
								"fulfillment-channel" => "",

								"Language" => "sales-channel",

								"ShippedByAmazonTFM" => "",
								"LatestDeliveryDate" => "",
								"EarliestDeliveryDate" => "",
								
								"tax_number" => "",
								"tax_included" => ""
								);

$paramsOrderItemsReportMFN = array(	"OrderItemId" => "order-item-id",
									"SellerSKU" => "sku",
									"ASIN" => "",
									"Title" => "product-name",

									"shipment-id" => "",
									"shipment-item-id" => "",
									"merchant-order-item-id" => "",

									"ItemPrice" => "item-price",
									"ItemTax" => "item-tax",
									"PromotionDiscount" => "",
									"ShippingPrice" => "shipping-price",
									"ShippingTax" => "shipping-tax",
									"ShippingDiscount" => "",
									"GiftWrapPrice" => "", "GiftWrapTax" => "",
									
									"QuantityOrdered" => "quantity-purchased",
									"QuantityShipped" => "",
																
									"SerialNumber" => ""
									);
?>