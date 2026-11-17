<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockTransferReportService
{
    /**
     * Warehouse-to-warehouse stock transfers in a date range.
     *
     * A transfer is stored as two type-4 inventory_transactions rows written
     * back to back (WarehouseController::transferStock, DriverController's
     * transfer API): an outgoing row (negative qty, "Stock transferred to
     * warehouse: ...") and an incoming row (positive qty, "Stock received from
     * warehouse: ..."). There is no shared transfer id, so each outgoing row
     * is paired with the first matching incoming row (same batch, opposite
     * qty) just after it. Manual adjustments, also type 4, never match the
     * remark prefixes and are left out.
     *
     * @param array $filters from_warehouse_id, to_warehouse_id (0/empty = all),
     *                       product_id[] (ids), batch_id[] (batch codes);
     *                       empty or only '%' (ALL) = all
     */
    public function generateReport($dateFrom, $dateTo, array $filters = [])
    {
        // Dates picked the wrong way round would otherwise return nothing.
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // ponytail: pairs out/in rows by id proximity (next 20 ids); add a
        // transfer_id column to inventory_transactions if this ever mis-pairs.
        $sql = "SELECT o.id, o.date, -o.quantity AS quantity, o.user,
                       fw.name AS from_location, tw.name AS to_location,
                       p.name AS product_name, p.unit_code AS product_code,
                       pb.batch_code, pb.expiry_date
                FROM inventory_transactions o
                JOIN inventory_transactions i ON i.id = (
                    SELECT MIN(x.id) FROM inventory_transactions x
                    WHERE x.id > o.id AND x.id <= o.id + 20
                      AND x.type = 4 AND x.batch_id = o.batch_id
                      AND x.quantity = -o.quantity
                      AND x.remark LIKE 'Stock received from warehouse:%')
                LEFT JOIN warehouses fw ON fw.id = o.warehouse_id
                LEFT JOIN warehouses tw ON tw.id = i.warehouse_id
                LEFT JOIN products p ON p.id = o.product_id
                LEFT JOIN product_batches pb ON pb.id = o.batch_id
                WHERE o.type = 4 AND o.quantity < 0
                  AND o.remark LIKE 'Stock transferred to warehouse:%'
                  AND o.date BETWEEN ? AND ?";
        $bindings = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

        if (!empty($filters['from_warehouse_id'])) {
            $sql .= ' AND o.warehouse_id = ?';
            $bindings[] = (int) $filters['from_warehouse_id'];
        }
        if (!empty($filters['to_warehouse_id'])) {
            $sql .= ' AND i.warehouse_id = ?';
            $bindings[] = (int) $filters['to_warehouse_id'];
        }
        // ALL ('%') is preselected on the form; if the user also picks specific
        // entries without un-ticking ALL, filter by those entries.
        // Batches filter by code: a code can exist on more than one batch row.
        foreach (['product_id' => 'o.product_id', 'batch_id' => 'pb.batch_code'] as $key => $column) {
            $values = array_values(array_filter(
                (array) ($filters[$key] ?? []),
                fn ($v) => $v !== '' && $v !== null && $v !== '%'
            ));
            if ($values) {
                $sql .= ' AND ' . $column . ' IN (' . implode(',', array_fill(0, count($values), '?')) . ')';
                array_push($bindings, ...$values);
            }
        }

        $sql .= ' ORDER BY o.date, o.id';

        $items = array_map(function ($r) {
            return [
                'date' => Carbon::parse($r->date)->format('d/m/Y H:i'),
                'from_location' => $r->from_location ?? '-',
                'to_location' => $r->to_location ?? '-',
                'product_name' => $r->product_name ?? '-',
                'product_code' => $r->product_code ?? '-',
                'batch_code' => $r->batch_code ?? '-',
                'expiry_date' => $r->expiry_date ? Carbon::parse($r->expiry_date)->format('d/m/Y') : '-',
                'quantity' => (int) $r->quantity,
                'user' => $r->user ?? '-',
            ];
        }, DB::select($sql, $bindings));

        return [
            'generated_at' => Carbon::now()->format('d/m/Y H:i:s'),
            'date_from' => Carbon::parse($dateFrom)->format('d/m/Y'),
            'date_to' => Carbon::parse($dateTo)->format('d/m/Y'),
            'from_location' => $this->warehouseName($filters['from_warehouse_id'] ?? null),
            'to_location' => $this->warehouseName($filters['to_warehouse_id'] ?? null),
            'items' => $items,
            'total_quantity' => array_sum(array_column($items, 'quantity')),
        ];
    }

    private function warehouseName($id)
    {
        return $id ? (DB::table('warehouses')->where('id', $id)->value('name') ?? '-') : 'All location';
    }
}
