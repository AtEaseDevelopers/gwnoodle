<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesAnalysisReportService
{
    /**
     * Build the Sales Analysis Report: every invoice line in the date range,
     * optionally restricted to given customers and/or products, grouped by
     * customer with a subtotal per customer and an overall grand total.
     *
     * Reads via a single joined query builder call (not Eloquent models with
     * relations) - a month across every customer/product can be many
     * thousands of lines, and hydrating full Invoice/Customer/Product
     * models plus their relations for each one is far heavier than this
     * report needs and was blowing past the memory limit.
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

        $rows = DB::table('invoice_details as d')
            ->join('invoices as i', 'i.id', '=', 'd.invoice_id')
            ->leftJoin('customers as c', 'c.id', '=', 'i.customer_id')
            ->leftJoin('products as p', 'p.id', '=', 'd.product_id')
            ->where('i.status', Invoice::STATUS_COMPLETED)
            ->whereBetween('i.date', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ])
            ->when($customerIds, fn($q) => $q->whereIn('i.customer_id', $customerIds))
            ->when($productIds, fn($q) => $q->whereIn('d.product_id', $productIds))
            ->select([
                'i.id as invoice_id',
                'i.date as invoice_date',
                'i.invoiceno',
                'i.customer_id',
                DB::raw('COALESCE(c.company, "N/A") as customer_name'),
                DB::raw('COALESCE(d.product_code, p.unit_code, "N/A") as product_code'),
                DB::raw('COALESCE(d.product_name, p.name, "N/A") as product_name'),
                DB::raw('COALESCE(d.uom, p.uom, "") as uom'),
                'd.quantity',
                'd.price as unit_price',
                'd.totalprice as total_price',
            ])
            ->orderBy('c.company')
            ->orderBy('i.date')
            ->orderBy('i.invoiceno')
            ->get();

        $groups = [];
        $invoiceIdsWithLines = [];

        foreach ($rows as $row) {
            $customerKey = $row->customer_id ?? 'unknown';

            if (!isset($groups[$customerKey])) {
                $groups[$customerKey] = [
                    'customer_name' => $row->customer_name,
                    'lines' => [],
                    'subtotal_quantity' => 0,
                    'subtotal_amount' => 0,
                ];
            }

            $groups[$customerKey]['lines'][] = [
                'date' => Carbon::parse($row->invoice_date)->format('d/m/Y'),
                'invoiceno' => $row->invoiceno,
                'product_code' => $row->product_code,
                'product_name' => $row->product_name,
                'uom' => $row->uom,
                'quantity' => $row->quantity,
                'unit_price' => $row->unit_price,
                'total_price' => $row->total_price,
            ];

            $groups[$customerKey]['subtotal_quantity'] += $row->quantity;
            $groups[$customerKey]['subtotal_amount'] += $row->total_price;

            $invoiceIdsWithLines[$row->invoice_id] = true;
            $reportData['summary']['total_lines']++;
            $reportData['summary']['total_quantity'] += $row->quantity;
            $reportData['summary']['total_amount'] += $row->total_price;
        }

        // Rows already arrive sorted by customer/date/invoiceno via the query,
        // so no further in-PHP sort is needed here.

        $reportData['customers'] = array_values($groups);
        $reportData['summary']['total_invoices'] = count($invoiceIdsWithLines);
        $reportData['summary']['total_customers'] = count($groups);

        return $reportData;
    }

    /**
     * Turn a request filter value into a plain array of ids, or null when it
     * means "no filter" (empty / missing / the legacy "ALL" option value "%").
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
