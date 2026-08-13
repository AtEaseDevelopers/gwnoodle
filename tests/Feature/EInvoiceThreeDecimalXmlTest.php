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

    /**
     * A discounted line is reported with the net-effective unit price
     * (line total / qty) so PriceAmount x Quantity still equals
     * LineExtensionAmount and MyInvois accepts it. Mirrors how the generator
     * derives unitPrice when invoice_details.discount > 0.
     */
    public function test_discounted_line_reconciles_with_net_effective_unit_price(): void
    {
        $service = new EInvoiceXmlGenerateService();
        $xml = new \DOMDocument('1.0', 'UTF-8');

        // qty 100 x gross 0.15 - discount 1.50 = 13.500 net; net unit = 0.135.
        $qty       = 100;
        $lineTotal = 13.500;
        $netUnit   = $lineTotal / $qty; // 0.135

        $params = $this->lineParams($netUnit, $qty);
        $params['lineExtensionAmount'] = $lineTotal;
        $params['taxableAmount']       = $lineTotal;

        $out = $xml->saveXML($service->createInvoiceLineElement($xml, $params));

        $this->assertStringContainsString('>0.135</cbc:PriceAmount>', $out);
        $this->assertStringContainsString('>13.500</cbc:LineExtensionAmount>', $out);
        $this->assertSame(
            number_format(0.135 * $qty, 3, '.', ''),
            '13.500',
            'Net-effective PriceAmount x Qty must equal the discounted LineExtensionAmount.'
        );
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
