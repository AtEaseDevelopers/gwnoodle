<?php

namespace Tests\Feature;

use App\Services\EInvoiceXmlGenerateService;
use Tests\TestCase;

/**
 * Covers the LHDN MyInvois e-invoice XML carrying 3-decimal unit prices and
 * line totals consistently, so the MyInvois arithmetic validation
 * (Subtotal = PriceAmount x Quantity) holds exactly.
 *
 * Pure XML construction — no database needed.
 */
class EInvoiceThreeDecimalXmlTest extends TestCase
{
    private function lineParams(float $price, int $qty): array
    {
        return [
            'id'                     => 'P1',
            'invoicedQuantity'       => $qty,
            'unitCode'               => 'C62',
            'lineExtensionAmount'    => $price * $qty,
            'currencyID'             => 'MYR',
            'allowanceCharges'       => null,
            'taxAmount'              => 0,
            'taxableAmount'          => $price * $qty,
            'taxExemptionReason'     => 'exemption',
            'description'            => 'ZZZ-3DP test',
            'originCountryCode'      => 'MYS',
            'itemClassificationCodes' => [['code' => '003']],
            'priceAmount'            => $price,
            'amount'                 => $price,
        ];
    }

    public function test_invoice_line_xml_emits_three_decimal_price_and_line_total(): void
    {
        $service = new EInvoiceXmlGenerateService();
        $xml = new \DOMDocument('1.0', 'UTF-8');

        $line = $service->createInvoiceLineElement($xml, $this->lineParams(1.235, 3));
        $out = $xml->saveXML($line);

        // Unit price keeps its 3rd decimal (not rounded to 1.24).
        $this->assertStringContainsString('>1.235</cbc:PriceAmount>', $out);
        $this->assertStringContainsString('>1.235</cbc:Amount>', $out); // ItemPriceExtension
        $this->assertStringNotContainsString('>1.24</cbc:PriceAmount>', $out);

        // Line total is the exact 3dp product 1.235 * 3 = 3.705.
        $this->assertStringContainsString('>3.705</cbc:LineExtensionAmount>', $out);
    }

    public function test_price_times_quantity_equals_line_extension_amount(): void
    {
        $service = new EInvoiceXmlGenerateService();

        foreach ([[1.235, 3], [0.005, 7], [12.345, 4]] as [$price, $qty]) {
            $xml = new \DOMDocument('1.0', 'UTF-8');
            $out = $xml->saveXML($service->createInvoiceLineElement($xml, $this->lineParams($price, $qty)));

            $priceStr = number_format($price, 3, '.', '');
            $lineStr  = number_format($price * $qty, 3, '.', '');

            $this->assertStringContainsString(">{$priceStr}</cbc:PriceAmount>", $out);
            $this->assertStringContainsString(">{$lineStr}</cbc:LineExtensionAmount>", $out);
            // The MyInvois check: PriceAmount x Quantity must equal LineExtensionAmount.
            $this->assertSame(
                number_format((float) $priceStr * $qty, 3, '.', ''),
                $lineStr,
                "PriceAmount x Qty must equal LineExtensionAmount for {$price} x {$qty}"
            );
        }
    }
}
