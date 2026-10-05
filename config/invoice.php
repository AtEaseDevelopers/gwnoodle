<?php

return [
    'name' => env('INVOICE_NAME'),
    'ssm' => env('INVOICE_SSM'),
    // New principal address, printed above the original address lines on all
    // invoice/receipt letterheads. Defaults here so no .env change is needed;
    // override with INVOICE_NEW_ADDRESS1..3 if it ever changes.
    'new_address1' => env('INVOICE_NEW_ADDRESS1', 'NO. 18 & 20, JALAN EKOPERNIAGAAN 3/4,'),
    'new_address2' => env('INVOICE_NEW_ADDRESS2', 'TAMAN EKOPERNIAGAAN,'),
    'new_address3' => env('INVOICE_NEW_ADDRESS3', '81100 JOHOR BAHRU, JOHOR.'),
    'address1' => env('INVOICE_ADDRESS1'),
    'address2' => env('INVOICE_ADDRESS2'),
    // Previously read with a bare env() in the receipt views, which returns
    // null when config is cached - routed through config like the others.
    'address3' => env('INVOICE_ADDRESS3'),
    'phone' => env('INVOICE_PHONE'),
];
