<?php
if (!class_exists('FikenHtmlInvoice')) {

    class FikenHtmlInvoice
    {
        public static function getHtmlInvoice($data)
        {

            $html = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
                <title>" . __('Invoice', 'fiken') . "</title>
                <style type='text/css'>

@page {
	margin-top: 1cm;
	margin-bottom: 3cm;
	margin-left: 2cm;
	margin-right: 2cm;
}
body {
	background: #fff;
	color: #000;
	margin: 0cm;
	font-family: 'DejaVu Sans', sans-serif;
	font-size: 9pt;
}

h1,
h2,
h3,
h4 {
	font-size: 10pt;
	font-weight: bold;
	margin: 0;
}

ol,
ul {
	list-style: none;
	margin: 0;
	padding: 0;
}

li,
ul {
	margin-bottom: 0.75em;
}

p {
	margin: 0;
	padding: 0;
}

p + p {
	margin-top: 1.25em;
}

a {
	border-bottom: 1px solid;
	text-decoration: none;
}

table {
	border-collapse: collapse;
	border-spacing: 0;
	page-break-inside: avoid;
}

th, td {
	vertical-align: top;
}

table.container {
	width:100%;
	border: 0;
}

tr.no-borders,
td.no-borders {
	border: 0 !important;
	border-top: 0 !important;
	border-bottom: 0 !important;
	padding: 0 !important;
	width: auto;
}

td.header img {
	max-height: 3cm;
	width: auto;
}

td.header {
	font-size: 16pt;
	font-weight: 700;
}

td.header,
td.shop-info {
	margin-bottom: 10mm !important;
}

td.shop-info {
	width: 40%;
}
.document-type-label {
	text-transform: uppercase;
}

.order-date-label,
.order-number-label,
.order-payment-label {
	width: 4cm;
	display: inline-block;
}

table.order-details {
	width:100%;
	margin-top: 1cm;
	margin-bottom: 1cm;
}

.quantity-label,
.price-label {
	width: 20%;
}

.order-details tr {
	page-break-inside: avoid;
	page-break-after: auto;
}

.order-details td,
.order-details th {
	border-bottom: 1px #ccc solid;
	padding: 0.375em;
}

.order-details th {
	font-weight: bold;
	text-align: left;
}

.order-details thead th {
	color: white;
	background-color: black;
	border-color: black;
}

.order-details .description img {
	width: 1.5cm;
	height: auto;
}

table.product,
table.product td {
	margin: 0;
	border: 0;
	padding: 0;
}

table.product td.product-image {
	padding-right: 10px;
}


dl {
	margin: 4px 0;
}

dt, dd, dd p {
	display: inline;
	font-size: 7pt;
}

dd {
	margin-left: 5px;
}

dd:after {
	white-space: pre;
}

table.totals {
	width: 100%;
	margin-top: 5mm;
}

table.totals th,
table.totals td {
	border: 0;
	border-top: 1px solid #ccc;
	border-bottom: 1px solid #ccc;
}

table.totals th.description,
table.totals td.price {
	width: 20%;
}

table.totals tr:last-child td,
table.totals tr:last-child th {
	border-top: 2px solid #000;
	border-bottom: 2px solid #000;
	font-weight: bold;
}

table.totals td.price span:first-child {
	display: block;
}

#footer {
	position: absolute;
	bottom: -2cm;
	left: 0;
	right: 0;
	height: 2cm;
	text-align: center;
	border-top: 0.1mm solid gray;
	margin-bottom: 0;
	padding-top: 2mm;
}
                </style>
            </head>
            <body class='invoice'>
            <table class='head container'>
                <tr>
                    <td class='header'>" . __('Invoice', 'fiken') . "</td>
                    <td class='shop-info'>
                        <div class='shop-name'><h3>" . get_bloginfo('name') . " </h3></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h3 class='document-type-label'>" . __('Invoice', 'fiken') . "
                        </h3>
                    </td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td>
                        <div class='order-information'>
                            <span class='order-number-label'>" . __('Invoice Number:', 'fiken') . "</span>
                            <span class='order-number'>" . $data['invoice_number'] . "</span><br />

                            <span class='order-date-label'>" . __('Invoice Date:', 'fiken') . "</span>
                            <span class='order-date'>" . $data['order_date'] . "</span><br />

                            <span class='order-number-label'>" . __('Order Number:', 'fiken') . "</span>
                            <span class='order-number'>" . $data['invoice_number'] . "</span><br />
                            <span class='order-date-label'>" . __('Order Date:', 'fiken') . "</span>
                            <span class='order-date'>" . $data['order_date'] . "</span><br />
                            <span class='order-payment-label'>" . __('Payment Method:', 'fiken') . "</span>
                            <span class='order-payment'>" . $data['payment_method'] . "</span><br />
                        </div>
                    </td>
                    <td>
                        <div class='recipient-address'>" . $data['billing_address'] . "</div>
                    </td>
                </tr>
            </table>
            <table class='order-details'>
                <thead>
                <tr>
                    <th class='product-label'>" . __('Product', 'fiken') . "</th>
                    <th class='quantity-label'>" . __('Quantity', 'fiken') . "</th>
                    <th class='price-label'>" . __('Price', 'fiken') . "</th>
                </tr>
                </thead>
                <tbody>";

            if (sizeof($data['order_items']) > 0) {
                foreach ($data['order_items'] as $data['item']) {

                    $html .= "<tr><td class='description'>" . __('Description', 'fiken') . "
                    <span class='item-name'>" . $data['item']['name'] . "</span><span class='item-meta'>" . $data['item']['meta'] . "</span>
                    <dl class='meta'>";

                    if (!empty($data['item']['sku'])) {
                        $html .= " <dt>" . __('SKU:', 'fiken') . "</dt><dd>" . $data['item']['sku'] . "</dd> ";
                    };

                    if (!empty($data['item']['weight'])) {
                        $html .= " <dt>" . __('Weight:', 'fiken') . "</dt><dd>" . $data['item']['weight'] . " "
                            . get_option('woocommerce_weight_unit') . "</dd>";
                    };

                    $html .= "</dl></td><td class='quantity'>" . $data['item']['quantity'] . "</td>
                    <td class='price'>" . $data['item']['order_price'] . "</td></tr>";

                }
            }

            $html .= " </tbody><tfoot><tr class='no-borders'><td class='no-borders' colspan='3'><table class='totals'><tfoot>";

            foreach ($data['order_totals'] as $key => $total) {
                $html .= "<tr class='" . $key . "'><td class='no-borders'>&nbsp;</td><th class='description'>" . $total['label'] . "</th>
                <td class='price'><span class='totals-price'>" . $total['value'] . " </span></td></tr>";
            }

            $html .= " </tfoot></table></td></tr></tfoot></table><table class='notes container'>
                        <tr><td colspan='3'><div class='notes-shipping'>";

            if ($data['shipping_notes']) {
                $html .= "<h3>" . __('Customer Notes', 'fiken') . "</h3>" . $data['shipping_notes'];
            }
            $html .= "</div></td></tr></table></body></html>";
            return $html;
        }

    }
}