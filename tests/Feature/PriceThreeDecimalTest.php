<?php

namespace Tests\Feature;

use App\Models\InvoiceDetail;
use App\Models\Product;
use App\Models\SpecialPrice;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the move of unit prices + invoice line totals from 2 to 3 decimal
 * places (migration 2026_08_05_000000_widen_price_columns_to_3dp).
 *
 * Runs against the shared dev database (DatabaseTransactions), so every row it
 * creates is rolled back. There are no FK constraints on these tables, so
 * arbitrary reference ids are safe.
 */
class PriceThreeDecimalTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * The four in-scope columns must be stored as double(10,3).
     */
    public function test_price_columns_have_three_decimal_scale(): void
    {
        $targets = [
            ['products', 'price'],
            ['invoice_details', 'price'],
            ['invoice_details', 'totalprice'],
            ['special_prices', 'price'],
        ];

        foreach ($targets as [$table, $column]) {
            // SHOW COLUMNS ... LIKE does not accept bound parameters; the table
            // and column come from a fixed in-code list, so inlining is safe.
            $type = DB::select("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'")[0]->Type;
            $this->assertSame(
                'double(10,3)',
                strtolower($type),
                "{$table}.{$column} should be double(10,3), got {$type}"
            );
        }
    }

    /**
     * A product price with 3 decimals must persist without truncation.
     */
    public function test_product_price_persists_three_decimals(): void
    {
        $product = Product::create([
            'unit_code' => 'UT/' . uniqid(),
            'name'      => 'UT Product ' . uniqid(),
            'price'     => 1.235,
            'status'    => 1,
        ]);

        // Compare on the 3dp-formatted value: double is binary-imprecise, so
        // 1.235 round-trips as 1.2349999...; what matters is the stored value
        // rounds to 1.235, which is exactly what every display path shows.
        $this->assertSame('1.235', number_format($product->fresh()->price, 3, '.', ''));
    }

    /**
     * A special price with 3 decimals must persist without truncation.
     */
    public function test_special_price_persists_three_decimals(): void
    {
        $special = SpecialPrice::create([
            'product_id'  => 999999,
            'customer_id' => 999999,
            'price'       => 2.675,
            'status'      => 1,
        ]);

        $this->assertSame('2.675', number_format($special->fresh()->price, 3, '.', ''));
    }

    /**
     * An invoice detail must keep a 3-decimal unit price and compute its line
     * total (quantity * price) at full 3-decimal precision via the model's
     * boot mutator.
     */
    public function test_invoice_detail_price_and_line_total_use_three_decimals(): void
    {
        $detail = InvoiceDetail::create([
            'invoice_id'       => 999999,
            'product_id'       => 999999,
            'product_batch_id' => 999999,
            'quantity'         => 3,
            'price'            => 1.235,
            'remark'           => 'UT 3dp',
        ]);

        $fresh = $detail->fresh();

        $this->assertSame('1.235', number_format($fresh->price, 3, '.', ''));
        // 3 * 1.235 = 3.705 — the third decimal must survive.
        $this->assertSame('3.705', number_format($fresh->totalprice, 3, '.', ''));
    }

    /**
     * The formatted-price accessors must render 3 decimals.
     */
    public function test_invoice_detail_formatted_accessors_show_three_decimals(): void
    {
        $detail = new InvoiceDetail([
            'quantity' => 3,
            'price'    => 1.235,
        ]);
        $detail->totalprice = 3.705;

        $this->assertSame('1.235', $detail->formatted_price);
        $this->assertSame('3.705', $detail->formatted_total_price);
    }
}
