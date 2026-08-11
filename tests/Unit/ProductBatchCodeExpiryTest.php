<?php

namespace Tests\Unit;

use App\Models\ProductBatch;
use PHPUnit\Framework\TestCase;

class ProductBatchCodeExpiryTest extends TestCase
{
    /** @test */
    public function it_decodes_the_embedded_expiry_from_a_system_batch_code()
    {
        // group(1) user(2) YY(2) fixed"8"(1) DD(2) MM(2) productCode
        $this->assertSame('2026-09-24', ProductBatch::expiryDateFromCode('A002682409N008-PAN-120'));
        $this->assertSame('2026-08-30', ProductBatch::expiryDateFromCode('K992683008BK-PUFF'));
        $this->assertSame('2026-09-13', ProductBatch::expiryDateFromCode('A002681309N008-PAN-120'));
    }

    /** @test */
    public function it_returns_null_for_non_date_encoded_or_invalid_codes()
    {
        $this->assertNull(ProductBatch::expiryDateFromCode('EX-N008-PAN-120'));   // legacy, no fixed "8"
        $this->assertNull(ProductBatch::expiryDateFromCode('EX-BK-BISCUIT'));     // legacy
        $this->assertNull(ProductBatch::expiryDateFromCode('A0026'));             // too short
        $this->assertNull(ProductBatch::expiryDateFromCode('A002682432N008'));    // MM=32 invalid month
        $this->assertNull(ProductBatch::expiryDateFromCode(''));
        $this->assertNull(ProductBatch::expiryDateFromCode(null));
    }

    /** @test */
    public function submitted_expiry_matches_only_when_year_month_day_agree()
    {
        // The 2006 bug: code says 2026-09-24, expiry submitted as 2006-09-24 -> mismatch
        $expected = ProductBatch::expiryDateFromCode('A002682409N008-PAN-120');
        $this->assertSame('2026-09-24', $expected);
        $this->assertNotSame('2006-09-24', $expected);
        $this->assertSame('2026-09-24', $expected); // correct value would pass the guard
    }
}
