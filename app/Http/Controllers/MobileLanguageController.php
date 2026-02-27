<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\MobileTranslation;
use App\Models\MobileTranslationVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Flash;

class MobileLanguageController extends Controller
{
    public function index()
    {
        $allLanguages = Language::all();
        $availableSystemLanguages = Language::whereHas('mobileTranslations')->get();

        // Default to English (language_id = 1) if no language is selected in the session
        $selectedLanguage = session('mobile_current_language', 'en'); // 'en' is assumed to be the code for English
        $language = Language::where('code', $selectedLanguage)->first();

        // If the language isn't found (e.g., invalid session value), fallback to English
        if (!$language) {
            $language = Language::where('id', 1)->first(); // English language (language_id = 1)
            $selectedLanguage = $language->code;
            session(['mobile_current_language' => $selectedLanguage]); // Update session
        }

        // Load translations for the selected language
        $translations = MobileTranslation::where('language_id', $language->id)
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        $defaultTranslations = $this->getDefaultTranslations();

        return view('mobile_language.index', [
            'languages' => $allLanguages,
            'availableSystemLanguages' => $availableSystemLanguages,
            'translations' => $translations,
            'defaultTranslations' => $defaultTranslations,
            'selectedLanguage' => $selectedLanguage
        ]);
    }

    public function edit($languageId)
    {
        $language = Language::findOrFail($languageId);
        session(['mobile_current_language' => $language->code]);
        Flash::success(__('mobile_language_translation.language_selected_for_editing') . ': ' . $language->name);
        return redirect()->route('mobile_language.index');
    }

    public function saveTranslations(Request $request)
    {
        $currentLanguage = $request->input('current_language');
        $translations = $request->input('translations', []);

        $language = Language::where('code', $currentLanguage)->firstOrFail();

        // Get English translations as fallback
        $englishTranslations = MobileTranslation::where('language_id', 1)
            ->get()
            ->keyBy('key');

        foreach ($translations as $key => $value) {
            // Use English translation as fallback if value is empty
            $finalValue = empty($value) 
                ? ($englishTranslations[$key]->value ?? '')
                : $value;
                
            MobileTranslation::updateOrCreate(
                [
                    'language_id' => $language->id,
                    'key' => $key,
                ],
                ['value' => $finalValue]
            );
        }
        
        // Increment version
        $this->incrementVersion($language->id);

        Flash::success(__('mobile_language_translation.mobile_language_saved_successfully'));
        return back();
    }

    public function importLanguage(Request $request)
    {
        $request->validate([
            'language' => 'required|exists:languages,code',
        ]);

        $language = Language::where('code', $request->language)->firstOrFail();
        $englishTranslations = MobileTranslation::where('language_id', 1)->get();
    
        // Create base translations in the new language using English as default
        foreach ($englishTranslations as $translation) {
            MobileTranslation::updateOrCreate(
                [
                    'language_id' => $language->id,
                    'key' => $translation->key,
                ],
                ['value' => $translation->value]
            );
        }
        
        // Set initial version
        MobileTranslationVersion::updateOrCreate(
            ['language_id' => $language->id],
            ['version' => 1]
        );

        session(['mobile_current_language' => $language->code]);
        Flash::success(__('mobile_language_translation.language_ready_for_translation'));
        return redirect()->route('mobile_language.index');
    }

    public function deleteLanguage($id)
    {
        $language = Language::findOrFail($id);

        // Check if the language ID is 1 and the code is 'en'
        if ($language->id === 1 && $language->code === 'en') {
            Flash::error(__('mobile_language_translation.mobile_language_cannot_delete'));
        } else {
            // Delete related mobile translations
            MobileTranslation::where('language_id', $language->id)->delete();

            // Optionally, delete the related version record if it exists
            MobileTranslationVersion::where('language_id', $language->id)->delete();

            Flash::success(__('mobile_language_translation.mobile_language_delete_successfully'));
        }

        return redirect(route('mobile_language.index'));
    }

    public function exportTranslations(Request $request)
    {
        $request->validate([
            'language' => 'required|exists:languages,code',
            'format' => 'required|in:json',
        ]);

        $language = Language::where('code', $request->language)->firstOrFail();
        // Get default translations
        $defaultTranslations = $this->getDefaultTranslations();

        // Get translations from database
        $translations = MobileTranslation::where('language_id', $language->id)
            ->pluck('value', 'key')
            ->toArray();

            // Merge with default translations
        $exportData = [];
         foreach ($defaultTranslations as $key => $defaultValue) {
            $exportData[$key] = $translations[$key] ?? $defaultValue;
        }

        $fileName = "mobile_lang_{$language->code}_" . now()->format('Ymd_HHis').time();

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename={$fileName}.json",
        ];

        return response()->json($exportData, 200, $headers, JSON_PRETTY_PRINT);
    }

    public function importTranslations(Request $request)
    {

        $request->validate([
            'language' => 'required|exists:languages,code',
            'file' => 'required|file|mimes:json|max:10240',
        ]);

        $language = Language::where('code', $request->language)->firstOrFail();
        $file = $request->file('file');

        try {
            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON file: ' . json_last_error_msg());
            }

            // Get default translations
            $defaultTranslations = $this->getDefaultTranslations();
            foreach ($data as $key => $value) {
                $key = trim($key); // Normalize key
                if (array_key_exists($key, $defaultTranslations)) {
                    if (!empty($value)) {
                        MobileTranslation::updateOrCreate(
                            [
                                'language_id' => $language->id,
                                'key' => $key,
                            ],
                            ['value' => $value]
                        );
                    }
                }
            }

            Flash::success(__('mobile_language_translation.translations_imported_successfully'));
        } catch (\Exception $e) {
            \Log::error('Import translations failed', ['error' => $e->getMessage()]);
            Flash::error(__('mobile_language_translation.failed_to_import_translations') . ': ' . $e->getMessage());
        }

        return back();
    }


    protected function incrementVersion($languageId)
    {
        // Retrieve the version record or create a new one if it doesn't exist
        $versionRecord = MobileTranslationVersion::firstOrCreate(
            ['language_id' => $languageId],
            ['version' => 1] // Initial version if it doesn't exist
        );

        // Increment the version by 1
        $versionRecord->increment('version');
    }

    private function getDefaultTranslations()
    {
        return[
             "app.status.text.new" => "New",
            "app.status.text.pending" => "Pending",
            "app.status.text.completed" => "Completed",
            "app.status.text.cancelled" => "Cancelled",
            "app.selection.text.hint.search" => "Search",
            "app.selection.button.cancel" => "Cancel",
            "app.inventory.text.unit" => "{{1}} unit",
            "app.inventory.toast.exceedsquantitylimit" => "Exceeds quantity limit",
            "app.inventory.text.remainingbalance" => "Remaining {{1}}",
            "app.inventory.button.addtobasket" => "Add to Basket",
            "app.inventory.button.cancel" => "Cancel",
            "app.error.text.networkerror" => "Network Error",
            "app.error.text.somethingwentwrong" => "Something went wrong",
            "app.login.text.title" => "Sign In",
            "app.login.hint.text.username" => "Username",
            "app.login.text.description" => "Please login to use the platform",
            "app.login.hint.text.password" => "Password",
            "app.login.button.login" => "Login",
            "app.customerlist.text.title" => "Customer List",
            "app.customerlist.text.all" => "All",
            "app.customerlist.text.extra" => "Extra",
            "app.customerlist.text.credit" => "Credit",
            "app.customerlist.text.company" => "Company",
            "app.customerlist.text.total" => "Total",
            "app.customerlist.text.creditnote" => "Credit Note (RM)",
            "app.customerlist.hint.text.select" => "Select",
            "app.customerlist.hint.text.search" => "Search",
            "app.customerinfo.text.title" => "Customer Info",
            "app.customerinfo.button.paycredit" => "Pay Credit",
            "app.customerinfo.button.viewhistory" => "View History",
            "app.customerinfo.button.neworder" => "New Order",
            "app.tripstatus.text.new" => "New",
            "app.tripstatus.text.pending" => "Pending",
            "app.tripstatus.text.completed" => "Completed",
            "app.tripstatus.text.cancelled" => "Cancelled",
            "app.dashboard.tab.text.home" => "Home",
            "app.dashboard.tab.text.deliver" => "Delivery",
            "app.dashboard.tab.text.inventory" => "Inventory",
            "app.home.item.text.tasktransfer" => "Task Transfer",
            "app.home.item.text.stocktransfer" => "Stock Transfer",
            "app.home.item.text.creditnote" => "Credit Note",
            "app.home.item.text.endtripsummary" => "End Trip Summary",
            "app.home.item.text.pendingorder" => "Pending Orders",
            "app.home.text.welcome" => "Hi, {{1}}",
            "app.home.button.logout" => "Logout",
            "app.home.statistic.text.sales" => "Sales (RM)",
            "app.home.statistic.text.paymentcollected" => "Payment Collected (RM)",
            "app.home.statistic.text.productsold" => "Product Sold",
            "app.home.statistic.text.creditnote" => "Credit Note (RM)",
            "app.home.statistic.text.bankin" => "Bank In (RM)",
            "app.home.statistic.text.cashleft" => "Cash Left (RM)",
            "app.home.statistic.text.tng" => "TNG (RM)",
            "app.home.statistic.text.cheque" => "Cheque (RM)",
            "app.home.text.version" => "v{{1}}",
            "app.pendingorder.text.title" => "Pending Orders Submission",
            "app.pendingorder.button.submitpendingorder" => "Submit Pending Orders",
            "app.inventory.text.title" => "Inventory",
            "app.inventory.text.tripnotstart" => "Trip had not started",
            "app.inventory.button.stockhistory" => "Stock Transaction History",
            "app.stocktransaction.text.title" => "Stock Transaction",
            "app.stocktransaction.tab.text.in" => "IN",
            "app.stocktransaction.tab.text.out" => "OUT",
            "app.stocktransaction.text.opening" => "Opening",
            "app.stocktransaction.text.stockin" => "Stock In",
            "app.stocktransaction.text.stockout" => "Stock Out",
            "app.stocktransaction.text.invoice" => "Invoice",
            "app.stocktransaction.text.transfer" => "Transfer",
            "app.wastage.text.title" => "Select Wastage",
            "app.wastage.button.text.wastage" => "Wastage",
            "app.wastage.button.text.item" => "{{1}} items",
            "app.wastageconfirm.text.title" => "Wastage Confirmation",
            "app.wastageconfirm.text.wastagesummary" => "Wastage Summary",
            "app.wastageconfirm.button.edit" => "Edit",
            "app.wastageconfirm.text.total" => "Total:",
            "app.wastageconfirm.text.cashleft" => "Cash left (RM):",
            "app.wastageconfirm.text.advance" => "Advance:",
            "app.wastageconfirm.button.cancel" => "Cancel",
            "app.wastageconfirm.button.confirm" => "Confirm",
            "app.wastageconfirm.endtrip.dialog.title" => "End Trip Confirmation",
            "app.wastageconfirm.endtrip.dialog.description" => "Please confirm all the details before end trip.",
            "app.wastageconfirm.endtrip.dialog.positive" => "Confirm",
            "app.wastageconfirm.endtrip.dialog.negative" => "Cancel",
            "app.wastageconfirm.endtrip.dialog.ok" => "OK",
            "app.trippanel.text.description" => "Click on Start Trip to get your task today.",
            "app.trippanel.button.starttrip" => "Start Trip",
            "app.trippanel.button.endtrip" => "End Trip",
            "app.trippanel.hint.text.search" => "Search",
            "app.trippanel.driver.text.title" => "Driver Info",
            "app.trippanel.driver.button.confirm" => "Confirm",
            "app.trippanel.driver.text.lorry" => "Lorry",
            "app.trippanel.driver.hint.text.lorry" => "Select",
            "app.trippanel.driver.text.kelindan" => "Kelindan",
            "app.trippanel.driver.hint.text.kelindan" => "Select",
            "app.trippanel.driver.text.driver" => "Driver",
            "app.trippanel.driver.hint.text.driver" => "Select",
            "app.trippanel.driver.dialog.text.pleaseselectfield" => "Please select all required field.",
            "app.trippanel.driver.dialog.button.ok" => "OK",
            "app.neworder.text.title" => "Select Products",
            "app.neworder.button.basket" => "Basket {{1}} items",
            "app.neworder.emptycart.dialog.description" => "Cart is empty.",
            "app.neworder.emptycart.dialog.ok" => "OK",
            "app.neworder.text.ordersummary" => "Order Summary",
            "app.neworder.button.edit" => "Edit",
            "app.neworder.text.priceperunit" => "RM {{1}}/unit",
            "app.neworder.text.itemfreeofcharge" => "{{1}} item free of charge",
            "app.neworder.text.discount" => "Discount",
            "app.neworder.button.apply" => "Apply",
            "app.neworder.text.ordertotal" => "Order Total:",
            "app.neworder.toast.exceedsproductquantity" => "Exceeds product quantity limit",
            "app.neworder.text.maxunit" => "{{1}} Unit",
            "app.neworder.dialog.button.freeofcharge" => "Free of Charge",
            "app.neworder.dialog.button.cancel" => "Cancel",
            "app.neworder.text.paymentdetails" => "Payment Details",
            "app.neworder.text.totalcollectable" => "Total Collectable",
            "app.neworder.text.remarks" => "Remarks",
            "app.neworder.hint.text.typesomething" => "Type something here",
            "app.neworder.text.selectpaymentmethod" => "Please select payment method",
            "app.neworder.hint.text.enterchequeno" => "Enter cheque no",
            "app.neworder.button.cancel" => "Cancel",
            "app.neworder.button.confirm" => "Confirm",
            "app.neworder.confirmation.text.title" => "Order Confirmation",
            "app.neworder.confirmation.text.description" => "Please confirm all the details before submit the order.",
            "app.neworder.confirmation.button.positive" => "Confirm",
            "app.neworder.confirmation.button.cancel" => "Cancel",
            "app.paymentmethod.text.cash" => "Cash",
            "app.paymentmethod.text.credit" => "Credit",
            "app.paymentmethod.text.ewallet" => "E-wallet",
            "app.paymentmethod.text.onlinebanking" => "Online Banking",
            "app.paymentmethod.text.cheque" => "Cheque",
            "app.endtrip.text.title" => "End Trip Summary",
            "app.endtrip.button.share" => "Share",
            "app.endtrip.text.totalsales" => "Total Sales",
            "app.endtrip.text.cashcollected" => "Cash Collected",
            "app.endtrip.text.creditnote" => "Credit Note",
            "app.endtrip.text.totalproductsold" => "Total Product Sold",
            "app.endtrip.text.bankin" => "Bank In",
            "app.endtrip.text.cashleft" => "Cash Left",
            "app.endtrip.text.tng" => "TNG",
            "app.endtrip.text.cheque" => "Cheque",
            "app.endtrip.text.product" => "Products",
            "app.endtrip.text.wastage" => "Wastage",
            "app.endtrip.text.tripinfo" => "Trip Info",
            "app.endtrip.text.team" => "Team",
            "app.endtrip.text.lorryno" => "Lorry No",
            "app.endtrip.text.kelindan" => "Kelindan",
            "app.endtrip.text.driver" => "Driver",
            "app.tasktransfer.text.title" => "Task Transferred",
            "app.tasktransfer.button.transfertask" => "Transfer Task",
            "app.tasktransfer.text.selectteam" => "Select Team",
            "app.tasktransfer.text.selectdriver" => "Select Driver",
            "app.tasktransfer.text.selectcustomer" => "Select Customer",
            "app.tasktransfer.button.tasktransfer" => "Task Transfer",
            "app.tasktransfersummary.text.title" => "Task Transfer",
            "app.tasktransfersummary.text.transfersummary" => "Transfer Summary",
            "app.tasktransfersummary.button.edit" => "Edit",
            "app.tasktransfersummary.text.transferto" => "Transfer To:",
            "app.tasktransfersummary.button.cancel" => "Cancel",
            "app.tasktransfersummary.button.confirm" => "Confirm",
            "app.tasktransferdetails.text.title" => "Task Transfer Details",
            "app.tasktransferdetails.text.tasktransfer" => "Task Transferred",
            "app.tasktransferdetails.text.transferto" => "Transfer To",
            "app.tasktransferdetails.text.date" => "Date",
            "app.tasktransferack.text.title" => "Order Acknowledgement",
            "app.tasktransferack.button.done" => "Done",
            "app.stocktransfer.text.title" => "Stock Transfer History",
            "app.stocktransfer.tab.text.request" => "REQUEST",
            "app.stocktransfer.tab.text.approve" => "APPROVE",
            "app.stocktransfer.text.pending" => "Pending",
            "app.stocktransfer.text.accepted" => "Accepted",
            "app.stocktransfer.text.rejected" => "Rejected",
            "app.stocktransfer.button.stocktransfer" => "Stock Transfer",
            "app.stocktransferreview.text.title" => "Stock Transfer Confirmation",
            "app.stocktransferreview.text.transfersummary" => "Transfer Summary",
            "app.stocktransferreview.button.edit" => "Edit",
            "app.stocktransferreview.text.total" => "Total:",
            "app.stocktransferreview.text.transferto" => "Transfer To:",
            "app.stocktransferreview.button.cancel" => "Cancel",
            "app.stocktransferreview.button.confirm" => "Confirm",
            "app.stocktransferselect.text.title" => "Stock Transfer",
            "app.stocktransferselect.text.selectteam" => "Select Team",
            "app.stocktransferselect.text.selectdriver" => "Select Driver",
            "app.stocktransferselect.text.transferstock" => "Transfer Stock",
            "app.stocktransferselect.text.totalitems" => "{{1}} items",
            "app.stocktransferselect.dialog.desc.selectdriver" => "Please select driver.",
            "app.stocktransferselect.dialog.button.selectdriver" => "OK",
            "app.stocktransferselect.dialog.desc.additem" => "Please add item to the cart.",
            "app.stocktransferselect.dialog.button.additem" => "OK",
            "app.stocktransferdetails.text.title" => "Stock Transfer Details",
            "app.stocktransferdetails.text.transfersummary" => "Transfer Summary",
            "app.stocktransferdetails.text.transferto" => "Transfer To:",
            "app.stocktransferdetails.text.transferfrom" => "Transfer From:",
            "app.stocktransferdetails.text.status" => "Status",
            "app.stocktransferdetails.dialog.button.reject" => "Reject",
            "app.stocktransferdetails.dialog.button.accept" => "Accept",
            "app.stocktransferdetails.dialog.button.ok" => "OK",
            "app.stocktransferack.text.title" => "Stock Transfer Acknowledgement",
            "app.stocktransferack.button.done" => "Done",
            "app.paycredit.text.title" => "Pay Credit",
            "app.paycredit.text.total" => "Total",
            "app.paycredit.text.paymentdetails" => "Payment Details",
            "app.paycredit.text.paidamount" => "Paid Amount (RM)",
            "app.paycredit.button.cancel" => "Cancel",
            "app.paycredit.button.confirm" => "Confirm",
            "app.paycredit.text.selectpaymentmethod" => "Please select payment method",
            "app.paycredit.text.enterchequeno" => "Enter cheque no",
            "app.paycreditack.text.title" => "Pay Credit Acknowledgement",
            "app.paycreditack.button.done" => "Done",
            "app.paycreditack.button.receipt" => "Receipt",
            "app.pdfview.button.close" => "Close",
            "app.pdfview.button.print" => "Print",
            "app.ordersuccess.text.title" => "Order Acknowledgement",
            "app.ordersuccess.button.close" => "Close",
            "app.ordersuccess.button.deliveryorder" => "Delivery Order",
            "app.ordersuccess.button.receipt" => "Receipt",
            "app.pdfview.text.title.deliveryorder" => "Delivery Order",
            "app.pdfview.text.title.receipt" => "Receipt",
            "app.pdfview.text.title.payment" => "Payment",
            "app.pdfview.text.title.invoice" => "Invoice",
            "app.paymentdetails.text.title" => "Payment",
            "app.paymentdetails.text.total" => "Total",
            "app.paymentdetails.text.updatecredit" => "Update Credit",
            "app.paymentdetails.text.paidamount" => "Paid Amount",
            "app.paymentdetails.text.invoicefor" => "Invoice for",
            "app.paymentdetails.text.issueon" => "Issue On",
            "app.paymentdetails.button.preview" => "Preview",
            "app.deliveryorder.text.total" => "Total",
            "app.deliveryorder.text.updatedcredit" => "Updated Credit",
            "app.deliveryorder.text.deliveryorderfor" => "Delivery Order for",
            "app.deliveryorder.text.issueon" => "Issue On",
            "app.deliveryorder.text.products" => "Products",
            "app.deliveryorder.button.previewdeliveryorder" => "Preview Delivery Order",
            "app.invoice.text.total" => "Total",
            "app.invoice.text.updatedcredit" => "Updated Credit",
            "app.invoice.text.invoicefor" => "Invoice for",
            "app.invoice.text.paidamount" => "Paid Amount",
            "app.invoice.text.issueon" => "Issue On",
            "app.invoice.text.products" => "Products",
            "app.invoice.button.previewinvoice" => "Preview Invoice",
            "app.error.sessionexpired.dialog.text.message"=> "Session expired. Please login again.",
            "app.error.sessionexpired.dialog.button.action"=> "Re-login",
            "app.error.connectiontimeout.text"=> "Network Error",
            "app.error.noconnection.text"=> "Network Error",
            "app.endtrip.text.advance"=> "Advance",

            "api.message.login_successfully" => "Login successfully",
            "api.message.logout_successfully" => "Logout successfully",
            "api.message.previous_session_override"=> "Previous session will be override",
            "api.message.invalid_credential"=> "Invalid Credential",

            "api.message.invalid_session"=> "Invalid Session",
            "api.message.session_found"=> "Session Found",

            "api.message.trip_had_not_started"=> "Trip had not started",
            "api.message.trip_had_been_started_successfully"=> "Trip had been started successfully",
            "api.message.trip_had_been_ended_successfully"=> "Trip had been ended successfully",
            "api.message.trip_had_started"=> "Trip had started",
            "api.message.driver_location_had_been_updated_successfully"=> "Driver location had been updated successfully",
            "api.message.invalid_lorry"=> "Invalid Lorry",
            "api.message.invalid_type"=> "Invalid Type",
            "api.message.wastage_quantity_more_than_available_quantity"=> "Wastage quantity more than available quantity",
            "api.message.kelindan_found"=> "Kelindan found",
            "api.message.kelindan_not_found"=> "Kelindan not found",
            "api.message.lorry_found"=> "Lorry found",
            "api.message.lorry_not_found"=> "Lorry not found",
            "api.message.push_task_successfully"=> "Push task successfully",

            "api.message.task_found"=> "Task found",
            "api.message.task_not_found"=> "Task not found",
            "api.message.invalid_task"=> "Invalid task",
            "api.message.task_had_been_completed"=> "Task had been completed",
            "api.message.task_had_been_cancelled"=> "Task had been cancelled",
            "api.message.task_had_been_in_progress"=> "Task had been In-Progress",
            "api.message.task_had_been_started_successfully"=> "Task had been started successfully",
            "api.message.task_had_been_cancelled_successfully"=> "Task had been cancelled successfully",
            "api.message.invalid_language_code"=> "Invalid Language Code",

            "api.message.invalid_customer"=> "Invalid Customer",
            "api.message.invalid_product"=> "Invalid Product",
            "api.message.product_found"=> "Product Found",
            "api.message.customer_found"=> "Customer Found",
            "api.message.customer_not_found"=> "Customer Not Found",
            "api.message.payment_insert_successfully_found"=> "Payment insert successfully found",
            "api.message.invoice_not_found"=> "Invoice not found",
            "api.message.invoice_found"=> "Invoice found",
            "api.message.invoice_add_successfully"=> "Invoice add successfully",
            "api.message.invoice_found"=> "Invoice found",
            "api.message.invoice_payment_found"=> "Invoice Payment found",
            "api.message.invoice_payment_not_found"=> "Invoice Payment not found",
            "api.message.load_success"=> "Load Success",
            "api.message.no_stock_found"=> "No stock found",
            "api.message.stock_found"=> "Stock found",
            "api.message.no_driver_found"=> "No driver found",
            "api.message.driver_found"=> "Driver found",
            "api.message.invalid_driver"=> "Invalid Driver",
            "api.message.selected_driver_trip_had_not_started"=> "Selected driver trip had not started",
            "api.message.pending_driver_accept_transfer"=> "Pending driver accept transfer",
            "api.message.transfer_found"=> "Transfer found",
            "api.message.transfer_not_found"=> "Transfer not found",
            "api.message.transfer_rejecet_successfully"=> "Transfer reject successfully",
            "api.message.transfer_accept_successfully"=> "Transfer accept successfully",

            "api.message.transfer_already_accepted"=> "Transfer already accepted",
            "api.message.transfer_already_rejected"=> "Transfer already rejected",
            
            "api.message.from_driver_not_found"=> " From driver not found",
            "api.message.to_driver_not_found"=> "To driver not found",

            "api.message.task_transfer_not_found"=> " Task transfer not found",
            "api.message.task_transfer_found"=> "Task transfer found",

            "api.message.date_cannot_be_future_date"=> "Date cannot be future date",
                
            "api.message.transaction_found"=> "Transaction found",
            "api.message.transaction_not_found"=> "Transaction not found",

            "api.message.get_dashboard_successfully"=> "Get dashboard successfully",
            "api.message.language_update_successfully"=> "Language Update successfully",

        ];
    }

}