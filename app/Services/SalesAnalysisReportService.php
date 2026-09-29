<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;

class SalesAnalysisReportService
{
    /**
     * Build the Sales Analysis Report: every invoice line in the date range,
     * optionally restricted to given customers and/or products, grouped by
     * customer with a subtotal per customer and an overall grand total.
     *
     * @param  string     $dateFrom
     * @param  string     $dateTo
     * @param  array      $filters      ['customer_id' => array|null, 'product_id' => array|null]
     * @return array
     */
    public function generateReport($dateFrom, $dateTo, array $filters = [])
    {
        $customerIds = $this->normalizeFilter($filters['customer_id'] ?? null);
        $productIds = $this->normalizeFilter($filters['product_id'] ?? null);

        $reportData = [
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'report_date' => Carbon::parse($dateFrom)->format('d/m/Y') . ' - ' . Carbon::parse($dateTo)->format('d/m/Y'),
            'report_no' => 'SAR' . Carbon::parse($dateFrom)->format('ymd') . '-' . Carbon::parse($dateTo)->format('ymd'),
            'summary' => [
                'total_invoices' => 0,
                'total_lines' => 0,
                'total_quantity' => 0,
                'total_amount' => 0,
                'total_customers' => 0,
            ],
            'customers' => [],
        ];

        $query = Invoice::with(['customer', 'invoicedetail.product'])
            ->whereBetween('date', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ])
            ->where('status', Invoice::STATUS_COMPLETED);

        if ($customerIds !== null) {
            $query->whereIn('customer_id', $customerIds);
        }

        $invoices = $query->get();

        $groups = [];
        $invoiceIdsWithLines = [];

        foreach ($invoices as $invoice) {
            foreach ($invoice->invoicedetail as $detail) {
                if ($productIds !== null && !in_array($detail->product_id, $productIds)) {
                    continue;
                }

                $product = $detail->product;
                $customerKey = $invoice->customer_id ?? 'unknown';

                if (!isset($groups[$customerKey])) {
                    $groups[$customerKey] = [
                        'customer_name' => $invoice->customer->company ?? 'N/A',
                        'lines' => [],
                        'subtotal_quantity' => 0,
                        'subtotal_amount' => 0,
                    ];
                }

                $groups[$customerKey]['lines'][] = [
                    'date' => Carbon::parse($invoice->date)->format('d/m/Y'),
                    'sort_date' => $invoice->date,
                    'invoiceno' => $invoice->invoiceno,
                    'product_code' => $detail->product_code ?? ($product->unit_code ?? 'N/A'),
                    'product_name' => $detail->product_name ?? ($product->name ?? 'N/A'),
                    'uom' => $detail->uom ?? ($product->uom ?? ''),
                    'quantity' => $detail->quantity,
                    'unit_price' => $detail->price,
                    'total_price' => $detail->totalprice,
                ];

                $groups[$customerKey]['subtotal_quantity'] += $detail->quantity;
                $groups[$customerKey]['subtotal_amount'] += $detail->totalprice;

                $invoiceIdsWithLines[$invoice->id] = true;
                $reportData['summary']['total_lines']++;
                $reportData['summary']['total_quantity'] += $detail->quantity;
                $reportData['summary']['total_amount'] += $detail->totalprice;
            }
        }

        // Sort each customer's lines by date then invoice number
        foreach ($groups as $key => $group) {
            usort($groups[$key]['lines'], function ($a, $b) {
                return [$a['sort_date'], $a['invoiceno']] <=> [$b['sort_date'], $b['invoiceno']];
            });
        }

        // Sort customers alphabetically
        uasort($groups, fn($a, $b) => strcmp($a['customer_name'], $b['customer_name']));

        $reportData['customers'] = array_values($groups);
        $reportData['summary']['total_invoices'] = count($invoiceIdsWithLines);
        $reportData['summary']['total_customers'] = count($groups);

        return $reportData;
    }

    /**
     * Turn a request filter value into a plain array of ids, or null when it
     * means "no filter" (empty / missing / the "ALL" option value "%").
     */
    private function normalizeFilter($value)
    {
        if (empty($value)) {
            return null;
        }

        $values = is_array($value) ? $value : [$value];

        if (in_array('%', $values)) {
            return null;
        }

        return array_map('intval', $values);
    }
}
