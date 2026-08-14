<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The stock balance PDF (DomPDF) does not repeat rowspanned cells when a
 * product's batch rows spill onto a new page: the Item Code / Description /
 * Location / Avg. Cost / Total Cost cells live only on the first batch row, so
 * continuation rows lose them and the remaining cells shift left, leaving the
 * right-hand columns blank.
 *
 * The fix drops the rowspan and prints the shared columns on EVERY batch row so
 * every row is self-contained and survives a page break.
 */
class StockBalanceReportRowspanTest extends TestCase
{
    private function reportDataWithBatches(int $batchCount): array
    {
        $batches = [];
        for ($i = 0; $i < $batchCount; $i++) {
            $batches[] = [
                'batch_no'         => 'BATCH-' . $i,
                'expiry_date'      => '01-01-2030',
                'quantity'         => 10,
                'smallest_bal_qty' => 10,
            ];
        }

        return [
            'generated_at' => '01/01/2030 00:00:00',
            'summary' => [
                'warehouses_count' => 1,
                'total_products'   => 1,
                'total_quantity'   => 10 * $batchCount,
                'total_value'      => 10 * $batchCount * 0.40,
            ],
            'warehouses' => [
                [
                    'warehouse' => ['name' => 'WH1', 'location' => 'NO 18'],
                    'products' => [
                        [
                            'product_id'     => 1,
                            'product_code'   => 'ITEM-CODE-X',
                            'product_name'   => 'Mi Wantan',
                            'average_cost'   => 0.40,
                            'total_quantity' => 10 * $batchCount,
                            'total_value'    => 10 * $batchCount * 0.40,
                            'batches'        => $batches,
                        ],
                    ],
                    'total_quantity' => 10 * $batchCount,
                    'total_value'    => 10 * $batchCount * 0.40,
                    'product_count'  => 1,
                ],
            ],
        ];
    }

    public function test_shared_columns_repeat_on_every_batch_row(): void
    {
        $batchCount = 5;
        $html = view('reports.stock_balance', [
            'reportData' => $this->reportDataWithBatches($batchCount),
            'filters'    => [],
        ])->render();

        // The item code must appear once per batch row, not merged into one.
        $this->assertSame(
            $batchCount,
            substr_count($html, 'ITEM-CODE-X'),
            'Item code should be printed on every batch row so page-break continuation rows keep it'
        );

        // No rowspan-based merging that DomPDF drops across page breaks.
        $this->assertStringNotContainsString(
            'rowspan',
            $html,
            'Shared columns must not use rowspan (DomPDF loses them across page breaks)'
        );
    }

    public function test_every_batch_row_has_all_seven_columns(): void
    {
        $html = view('reports.stock_balance', [
            'reportData' => $this->reportDataWithBatches(3),
            'filters'    => [],
        ])->render();

        // Grab the tbody data rows and ensure each has 7 <td> cells so nothing
        // shifts left into a blank column.
        preg_match('/<tbody>(.*?)<\/tbody>/s', $html, $m);
        $this->assertNotEmpty($m, 'tbody should be present');

        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $m[1], $rows);
        $this->assertNotEmpty($rows[1], 'batch rows should be present');

        foreach ($rows[1] as $row) {
            $tdCount = substr_count($row, '<td');
            $this->assertSame(
                7,
                $tdCount,
                'Each batch row must render all 7 columns (Item Code, Description, Location, Batch, Qty, Avg. Cost, Total Cost)'
            );
        }
    }
}
