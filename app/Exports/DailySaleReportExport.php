<?php 

namespace App\Exports;

use App\Models\Trip;
use App\Models\Invoice;
use App\Models\InventoryTransfer;
use App\Models\DailyInventoryBalance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DailySaleReportExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $invoiceDetails;
    protected $lorrys;
    protected $date;

    public function __construct($invoiceDetails,$lorrys,$date)
    {
        $this->invoiceDetails = $invoiceDetails;
        $this->lorrys = $lorrys;
        $this->date = $date;
    }

    public function collection()
     {
        return $this->invoiceDetails->map(function ($invoiceDetail) {
            $invoice = $invoiceDetail->invoice;
            
            $productId = $invoiceDetail->product->id;
            $driverId = $invoice->driver->id;
         
            return [
                'No' => $invoice->invoiceno,
                'Customer' => $invoice->customer->company,
                'Payment Term' => $invoice->paymentterm == 1 ? 'Cash' : 'Credit',
                'Product' => $invoiceDetail->product->name,
                'Qty' => $invoiceDetail->quantity,
                'Foc' => "0",
                'Foc Qty' => "0",
                'Unit Price (RM)' => $invoiceDetail->price,
                'Total Price' => $invoiceDetail->totalprice,
                'Date' => $invoice->date,
                'Driver' => $invoice->driver->name, // Assuming driver name is needed
                //'Lorry' => 'N/A',
            ];
        })->filter(); // Remove any null values from the collection
    }

    public function headings(): array
    {
        return [
            'No',
            'Customer',
            'Payment Term',
            'Product',
            'Qty',
            'Foc',
            'Foc Qty',
            'Unit Price (RM)',
            'Total Price',
            'Date',
            'Driver',
            //'Lorry',
        ];
    }
}
