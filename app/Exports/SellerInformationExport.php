<?php 

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SellerInformationExport implements FromCollection, WithHeadings
{
    protected $invoices;
    protected $params;

    public function __construct($invoices, $params)
    {
        $this->invoices = $invoices;
        $this->params = $params;
    }

    /**
     * Return the collection of data.
     */
    public function collection()
    {
        return collect($this->formatData($this->invoices, $this->params));
    }

    /**
     * Define the headings for the Excel sheet.
     */
    public function headings(): array
    {
        return [
            'Invoice Number',
            'Invoice Date',
            'Code',
            'Customer Name',
            'Customer Group',
            'Description',
            'Quantity',
            'UOM',
            'Unit Price',
            'Amount',
            'Total Amount',
            'Agent',
            'Driver'
        ];
    }

    /**
     * Format the data as needed.
     */
    public function formatData($invoices, $params)
    {
        $formattedData = [];
        foreach ($invoices as $invoice) {
            foreach ($invoice['invoicedetail'] as $detail) {
                $formattedData[] = [
                'Invoice Number' => $invoice['invoiceno'],
                'Invoice Date' => $invoice['date'],
                'Code' => $invoice['customer']['code'],
                'Customer Name' => $invoice['customer']['company'],
                'Customer Group' => $params['p_customer_groups'],
                'Description' => $detail['product']['name'],
                'Quantity' => number_format($detail['quantity'],2),
                'UOM' => 'BAG',
                'Unit Price' => number_format($detail['price'],4),
                'Amount' => number_format($detail['totalprice'],2),
                'Total Amount' => number_format($invoice['invoicedetail_sum_totalprice'],2),
                'Agent' => $params['p_agents'],
                'Driver' => $params['p_drivers'],
            ];
            }
        }
        return $formattedData;
    }
}
