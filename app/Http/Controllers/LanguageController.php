<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\LanguageTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Flash;
use ZipArchive;

class LanguageController extends Controller
{
    public function index()
    {
        $allLanguages = Language::all();
        $availableSystemLanguages = Language::whereHas('translations')->get();
        $currentLanguage = App::getLocale();

        $translations = LanguageTranslation::where('language_id', function ($query) use ($currentLanguage) {
            $query->select('id')->from('languages')->where('code', $currentLanguage)->first();
        })->get()
            ->groupBy('page')
            ->mapWithKeys(function ($group, $page) {
                return [
                    $page => $group->pluck('translated_text', 'key')->toArray()
                ];
            })
            ->toArray();

        $pages = [
            'side_menu' => [
                'invoices' => $translations['side_menu']['invoices'] ?? '',
                'invoice_details' => $translations['side_menu']['invoice_details'] ?? '',
                'payments' => $translations['side_menu']['payments'] ?? '',
                'tasks' => $translations['side_menu']['tasks'] ?? '',
                'trips' => $translations['side_menu']['trips'] ?? '',
                'inventory' => $translations['side_menu']['inventory'] ?? '',
                'balances' => $translations['side_menu']['balances'] ?? '',
                'transactions' => $translations['side_menu']['transactions'] ?? '',
                'transfers' => $translations['side_menu']['transfers'] ?? '',
                'lorries' => $translations['side_menu']['lorries'] ?? '',
                'lorry_service' => $translations['side_menu']['lorry_service'] ?? '',
                'drivers' => $translations['side_menu']['drivers'] ?? '',
                'driver_locations' => $translations['side_menu']['driver_locations'] ?? '',
                'kelindans' => $translations['side_menu']['kelindans'] ?? '',
                'agents' => $translations['side_menu']['agents'] ?? '',
                'operations' => $translations['side_menu']['operations'] ?? '',
                'products' => $translations['side_menu']['products'] ?? '',
                'customers' => $translations['side_menu']['customers'] ?? '',
                'companies' => $translations['side_menu']['companies'] ?? '',
                'special_prices' => $translations['side_menu']['special_prices'] ?? '',
                'focs' => $translations['side_menu']['focs'] ?? '',
                'assigns' => $translations['side_menu']['assigns'] ?? '',
                'setup' => $translations['side_menu']['setup'] ?? '',
                'codes' => $translations['side_menu']['codes'] ?? '',
                'customer_group' => $translations['side_menu']['customer_group'] ?? '',
                'commission_group' => $translations['side_menu']['commission_group'] ?? '',
                'system_language_setting' => $translations['side_menu']['system_language_setting'] ?? '',
                'mobile_app_language_setting' => $translations['side_menu']['mobile_app_language_setting'] ?? '',
                'user_management' => $translations['side_menu']['user_management'] ?? '',
                'users' => $translations['side_menu']['users'] ?? '',
                'user_roles' => $translations['side_menu']['user_roles'] ?? '',
                'roles' => $translations['side_menu']['roles'] ?? '',
                'role_permissions' => $translations['side_menu']['role_permissions'] ?? '',
                'permissions' => $translations['side_menu']['permissions'] ?? '',
                'reports' => $translations['side_menu']['reports'] ?? '',
                'delivery_orders' => $translations['side_menu']['delivery_orders'] ?? '',
                'loan_management' => $translations['side_menu']['loan_management'] ?? '',
                'loans' => $translations['side_menu']['loans'] ?? '',
                'loan_payments' => $translations['side_menu']['loan_payments'] ?? '',
                'finance' => $translations['side_menu']['finance'] ?? '',
                'compounds' => $translations['side_menu']['compounds'] ?? '',
                'advances' => $translations['side_menu']['advances'] ?? '',
                'bonuses' => $translations['side_menu']['bonuses'] ?? '',
                'master_data' => $translations['side_menu']['master_data'] ?? '',
            ],
            'dashboard' => [
                'dashboard' => $translations['dashboard']['dashboard'] ?? '',
                'total_sales_rm' => $translations['dashboard']['total_sales_rm'] ?? '',
                'total_sales_quantity' => $translations['dashboard']['total_sales_quantity'] ?? '',
                'by_day' => $translations['dashboard']['by_day'] ?? '',
                'day' => $translations['dashboard']['day'] ?? '',
                'week' => $translations['dashboard']['week'] ?? '',
                'month' => $translations['dashboard']['month'] ?? '',
                'year' => $translations['dashboard']['year'] ?? '',
            ],
            'table_buttons' => [
                'create' => $translations['table_buttons']['create'] ?? '',
                'print' => $translations['table_buttons']['print'] ?? '',
                'reset' => $translations['table_buttons']['reset'] ?? '',
                'reload' => $translations['table_buttons']['reload'] ?? '',
                'excel' => $translations['table_buttons']['excel'] ?? '',
                'pdf' => $translations['table_buttons']['pdf'] ?? '',
                'column' => $translations['table_buttons']['column'] ?? '',
                'show_10_rows' => $translations['table_buttons']['show_10_rows'] ?? '',
            ],
            'invoices' => [
                'invoices' => $translations['invoices']['invoices'] ?? '',
                'invoice_no' => $translations['invoices']['invoice_no'] ?? '',
                'invoice_detail' => $translations['invoices']['invoice_detail'] ?? '',
                'create_invoice' => $translations['invoices']['create_invoice'] ?? '',
                'invoice_saved_and_assigned_success' => $translations['invoices']['invoice_saved_and_assigned_success'] ?? '',
                'invoice_saved_successfully' => $translations['invoices']['invoice_saved_successfully'] ?? '',
                'invoice_not_found' => $translations['invoices']['invoice_not_found'] ?? '',
                'invoice_updated_and_assigned_success' => $translations['invoices']['invoice_updated_and_assigned_success'] ?? '',
                'invoice_updated_successfully' => $translations['invoices']['invoice_updated_successfully'] ?? '',
                'invoice_had_been_processed_by_driver' => $translations['invoices']['invoice_had_been_processed_by_driver'] ?? '',
                'invoice_deleted_successfully' => $translations['invoices']['invoice_deleted_successfully'] ?? '',
                'invoice_detail_saved_successfully' => $translations['invoices']['invoice_detail_saved_successfully'] ?? '',
                'invoice_detail_not_found' => $translations['invoices']['invoice_detail_not_found'] ?? '',
                'invoice_detail_deleted_successfully' => $translations['invoices']['invoice_detail_deleted_successfully'] ?? '',
                'invoice_sync_xero_success' => $translations['invoices']['invoice_sync_xero_success'] ?? '',
                'something_went_wrong' => $translations['invoices']['something_went_wrong'] ?? '',
                'date' => $translations['invoices']['date'] ?? '',
                'detail' => $translations['invoices']['detail'] ?? '',
                'customer' => $translations['invoices']['customer'] ?? '',
                'driver' => $translations['invoices']['driver'] ?? '',
                'kelindan' => $translations['invoices']['kelindan'] ?? '',
                'agent' => $translations['invoices']['agent'] ?? '',
                'supervisor' => $translations['invoices']['supervisor'] ?? '',
                'total_price' => $translations['invoices']['total_price'] ?? '',
                'payment_term' => $translations['invoices']['payment_term'] ?? '',
                'status' => $translations['invoices']['status'] ?? '',
                'group' => $translations['invoices']['group'] ?? '',
                'action' => $translations['invoices']['action'] ?? '',
                'cheque_no' => $translations['invoices']['cheque_no'] ?? '',
                'remark' => $translations['invoices']['remark'] ?? '',
                'save_&_exit' => $translations['invoices']['save_&_exit'] ?? '',
                'save_&_continue' => $translations['invoices']['save_&_continue'] ?? '',
                'cancel' => $translations['invoices']['cancel'] ?? '',
                'search' => $translations['invoices']['search'] ?? '',
                'close' => $translations['invoices']['close'] ?? '',
                'select_a_date_range' => $translations['invoices']['select_a_date_range'] ?? '',
                'edit' => $translations['invoices']['edit'] ?? '',
                'edit_invoice' => $translations['invoices']['edit_invoice'] ?? '',
                'are_you_sure_to_delete_the_invoice' => $translations['invoices']['are_you_sure_to_delete_the_invoice'] ?? '',
                'product' => $translations['invoices']['product'] ?? '',
                'quantity' => $translations['invoices']['quantity'] ?? '',
                'price' => $translations['invoices']['price'] ?? '',
            ],
            'invoice_details' => [
                'invoice' => $translations['invoice_details']['invoice'] ?? '',
                'invoice_details' => $translations['invoice_details']['invoice_details'] ?? '',
                'detail' => $translations['invoice_details']['detail'] ?? '',
                'create_invoice_details' => $translations['invoice_details']['create_invoice_details'] ?? '',
                'edit_invoice_details' => $translations['invoice_details']['edit_invoice_details'] ?? '',
                'invoice_no' => $translations['invoice_details']['invoice_no'] ?? '',
                'product' => $translations['invoice_details']['product'] ?? '',
                'quantity' => $translations['invoice_details']['quantity'] ?? '',
                'price' => $translations['invoice_details']['price'] ?? '',
                'total_price' => $translations['invoice_details']['total_price'] ?? '',
                'date' => $translations['invoice_details']['date'] ?? '',
                'remark' => $translations['invoice_details']['remark'] ?? '',
                'edit' => $translations['invoice_details']['edit'] ?? '',
                'create' => $translations['invoice_details']['create'] ?? '',         
                'save' => $translations['invoice_details']['save'] ?? '',
                'cancel' => $translations['invoice_details']['cancel'] ?? '',
                'search' => $translations['invoice_details']['search'] ?? '',
                'close' => $translations['invoice_details']['close'] ?? '',
                'select_a_date_range' => $translations['invoice_details']['select_a_date_range'] ?? '',
                'are_you_sure_to_delete_the_invoice_detail' => $translations['invoice_details']['are_you_sure_to_delete_the_invoice_detail'] ?? '',
                'something_went_wrong' => $translations['invoice_details']['something_went_wrong'] ?? '',
                'invoice_detail_saved_successfully' => $translations['invoice_details']['invoice_detail_saved_successfully'] ?? '',
                'invoice_detail_updated_successfully' => $translations['invoice_details']['invoice_detail_updated_successfully'] ?? '',
                'invoice_detail_not_found' => $translations['invoice_details']['invoice_detail_not_found'] ?? '',
                'invoice_detail_deleted_successfully' => $translations['invoice_details']['invoice_detail_deleted_successfully'] ?? '',
            ],
            'invoice_payments' => [
                'invoice' => $translations['invoice_payments']['invoice'] ?? '',
                'create_invoice_payments' => $translations['invoice_payments']['create_invoice_payments'] ?? '',
                'invoice_payments' => $translations['invoice_payments']['invoice_payments'] ?? '',
                'edit_invoice_payments' => $translations['invoice_payments']['edit_invoice_payments'] ?? '',
                'payment_approval' => $translations['invoice_payments']['payment_approval'] ?? '',
                'payment_no' => $translations['invoice_payments']['payment_no'] ?? '',
                'invoice_no' => $translations['invoice_payments']['invoice_no'] ?? '',
                'detail' => $translations['invoice_payments']['detail'] ?? '',
                'amount' => $translations['invoice_payments']['amount'] ?? '',
                'customer' => $translations['invoice_payments']['customer'] ?? '',
                'status' => $translations['invoice_payments']['status'] ?? '',
                'date' => $translations['invoice_payments']['date'] ?? '',
                'type' => $translations['invoice_payments']['type'] ?? '',
                'cash' => $translations['invoice_payments']['cash'] ?? '',
                'credit' => $translations['invoice_payments']['credit'] ?? '',
                'online_bankin' => $translations['invoice_payments']['online_bankin'] ?? '',
                'ewallet' => $translations['invoice_payments']['ewallet'] ?? '',
                'cheque' => $translations['invoice_payments']['cheque'] ?? '',
                'payment_term_unknown' => $translations['invoice_payments']['payment_term_unknown'] ?? '',
                'completed' => $translations['invoice_payments']['completed'] ?? '',
                'new' => $translations['invoice_payments']['new'] ?? '',
                'approve_by' => $translations['invoice_payments']['approve_by'] ?? '',
                'approve_at' => $translations['invoice_payments']['approve_at'] ?? '',
                'group' => $translations['invoice_payments']['group'] ?? '',
                'attachment' => $translations['invoice_payments']['attachment'] ?? '',
                'remark' => $translations['invoice_payments']['remark'] ?? '',
                'create' => $translations['invoice_payments']['create'] ?? '',
                'edit' => $translations['invoice_payments']['edit'] ?? '',
                'update' => $translations['invoice_payments']['update'] ?? '',
                'payment_saved_successfully' => $translations['invoice_payments']['payment_saved_successfully'] ?? '',
                'payment_updated_successfully' => $translations['invoice_payments']['payment_updated_successfully'] ?? '',
                'payment_not_found' => $translations['invoice_payments']['payment_not_found'] ?? '',
                'cannot_delete_completed_payment' => $translations['invoice_payments']['cannot_delete_completed_payment'] ?? '',
                'payment_deleted_successfully' => $translations['invoice_payments']['payment_deleted_successfully'] ?? '',
                'payment_had_been_completed' => $translations['invoice_payments']['payment_had_been_completed'] ?? '',
                'something_went_wrong' => $translations['invoice_payments']['something_went_wrong'] ?? '',
                'search' => $translations['invoice_details']['search'] ?? '',
                'close' => $translations['invoice_details']['close'] ?? '',
                'select_a_date_range' => $translations['invoice_details']['select_a_date_range'] ?? '',
                'are_you_sure_to_delete_the_payment' => $translations['invoice_payments']['are_you_sure_to_delete_the_payment'] ?? '',
                'save' => $translations['invoice_payments']['save'] ?? '',
                'cancel' => $translations['invoice_payments']['cancel'] ?? '',
                'amount' => $translations['invoice_payments']['amount'] ?? '',
                'action' => $translations['invoice_payments']['action'] ?? '',

            ],
            'tasks' => [
                'tasks' => $translations['tasks']['tasks'] ?? '',
                'detail' => $translations['tasks']['detail'] ?? '',
                'trip_id' => $translations['tasks']['trip_id'] ?? '',
                'invoice_no' => $translations['tasks']['invoice_no'] ?? '',
                'action' => $translations['tasks']['action'] ?? '',
                'create' => $translations['tasks']['create'] ?? '',
                'edit' => $translations['tasks']['edit'] ?? '',
                'edit_task' => $translations['tasks']['edit_task'] ?? '',
                'create_task' => $translations['tasks']['create_task'] ?? '',
                'are_you_sure_to_delete_the_task' => $translations['tasks']['are_you_sure_to_delete_the_task'] ?? '',
                'date' => $translations['tasks']['date'] ?? '',
                'driver' => $translations['tasks']['driver'] ?? '',
                'customer' => $translations['tasks']['customer'] ?? '',
                'sequence' => $translations['tasks']['sequence'] ?? '',
                'invoice' => $translations['tasks']['invoice'] ?? '',
                'status' => $translations['tasks']['status'] ?? '',
                'save' => $translations['tasks']['save'] ?? '',
                'cancel' => $translations['tasks']['cancel'] ?? '',
                'update' => $translations['tasks']['update'] ?? '',
                'new' => $translations['tasks']['new'] ?? '',
                'in_progress' => $translations['tasks']['in_progress'] ?? '',
                'completed' => $translations['tasks']['completed'] ?? '',
                'cancelled' => $translations['tasks']['cancelled'] ?? '',
                'search' => $translations['tasks']['search'] ?? '',
                'close' => $translations['tasks']['close'] ?? '',
                'select_a_date_range' => $translations['tasks']['select_a_date_range'] ?? '',
                'task_saved_successfully' => $translations['tasks']['task_saved_successfully'] ?? '',
                'task_not_found' => $translations['tasks']['task_not_found'] ?? '',
                'task_updated_successfully' => $translations['tasks']['task_updated_successfully'] ?? '',
                'task_deleted_successfully' => $translations['tasks']['task_deleted_successfully'] ?? '',
            ],
            'task_transfers' => [
                'task_transfers' => $translations['task_transfers']['task_transfers'] ?? '',
                'tasks' => $translations['task_transfers']['tasks'] ?? '',
                'detail' => $translations['task_transfers']['detail'] ?? '',
                'create' => $translations['task_transfers']['create'] ?? '',
                'create_task_transfer' => $translations['task_transfers']['create_task_transfer'] ?? '',
                'from_driver_id' => $translations['task_transfers']['from_driver_id'] ?? '',
                'to_driver_id' => $translations['task_transfers']['to_driver_id'] ?? '',
                'task_id' => $translations['task_transfers']['task_id'] ?? '',
                'save' => $translations['task_transfers']['save'] ?? '',
                'cancel' => $translations['task_transfers']['cancel'] ?? '',
                'are_you_sure' => $translations['task_transfers']['are_you_sure'] ?? '',
                'date' => $translations['task_transfers']['date'] ?? '',
                'search' => $translations['task_transfers']['search'] ?? '',
                'close' => $translations['task_transfers']['close'] ?? '',
                'select_a_date_range' => $translations['task_transfers']['select_a_date_range'] ?? '',
                'task_transfer_saved_successfully' => $translations['task_transfers']['task_transfer_saved_successfully'] ?? '',
                'task_transfer_not_found' => $translations['task_transfers']['task_transfer_not_found'] ?? '',
                'task_transfer_updated_successfully' => $translations['task_transfers']['task_transfer_updated_successfully'] ?? '',
                'task_transfer_deleted_successfully' => $translations['task_transfers']['task_transfer_deleted_successfully'] ?? '',
            ],
            'trips' => [
                'trips' => $translations['trips']['trips'] ?? '',
                'action' => $translations['trips']['action'] ?? '',
                'end_trip_summary' => $translations['trips']['end_trip_summary'] ?? '',
                'sales' => $translations['trips']['sales'] ?? '',
                'cash' => $translations['trips']['cash'] ?? '',
                'cash_left' => $translations['trips']['cash_left'] ?? '',
                'credit' => $translations['trips']['credit'] ?? '',
                'online_bank' => $translations['trips']['online_bank'] ?? '',
                'tng' => $translations['trips']['tng'] ?? '',
                'cheque' => $translations['trips']['cheque'] ?? '',
                'product_sold' => $translations['trips']['product_sold'] ?? '',
                'product' => $translations['trips']['product'] ?? '',
                'quantity' => $translations['trips']['quantity'] ?? '',
                'price' => $translations['trips']['price'] ?? '',
                'no_product_sold' => $translations['trips']['no_product_sold'] ?? '',
                'product_foc' => $translations['trips']['product_foc'] ?? '',
                'no_product_foc' => $translations['trips']['no_product_foc'] ?? '',
                'wastage' => $translations['trips']['wastage'] ?? '',
                'no_wastage' => $translations['trips']['no_wastage'] ?? '',
                'trip_id' => $translations['trips']['trip_id'] ?? '',
                'driver' => $translations['trips']['driver'] ?? '',
                'kelindan' => $translations['trips']['kelindan'] ?? '', 
                'lorry' => $translations['trips']['lorry'] ?? '', 
                'closing_cash' => $translations['trips']['closing_cash'] ?? '', 
                'type' => $translations['trips']['type'] ?? '', 
                'trip_not_found' => $translations['trips']['trip_not_found'] ?? '', 

                'select_a_date_range' => $translations['trips']['select_a_date_range'] ?? '',
                'date' => $translations['trips']['date'] ?? '',
                'search' => $translations['trips']['search'] ?? '',
                'close' => $translations['trips']['close'] ?? '',
            ],
            'inventory_balances' => [
                'inventory_balances' => $translations['inventory_balances']['inventory_balances'] ?? '',
                'lorry' => $translations['inventory_balances']['lorry'] ?? '',
                'select_lorry' => $translations['inventory_balances']['select_lorry'] ?? '',
                'product' => $translations['inventory_balances']['product'] ?? '',
                'quantity' => $translations['inventory_balances']['quantity'] ?? '',
                'are_you_sure' => $translations['inventory_balances']['are_you_sure'] ?? '',
                'stock_in' => $translations['inventory_balances']['stock_in'] ?? '',
                'stock_out' => $translations['inventory_balances']['stock_out'] ?? '',
                'transfer_quantity' => $translations['inventory_balances']['transfer_quantity'] ?? '',
                'cancel' => $translations['inventory_balances']['cancel'] ?? '',
                'update' => $translations['inventory_balances']['update'] ?? '',
            ],
            'inventory_transactions' => [
                'inventory_transactions' => $translations['inventory_transactions']['inventory_transactions'] ?? '',
                'lorry' => $translations['inventory_transactions']['lorry'] ?? '',
                'select_lorry' => $translations['inventory_transactions']['select_lorry'] ?? '',
                'product' => $translations['inventory_transactions']['product'] ?? '',
                'quantity' => $translations['inventory_transactions']['quantity'] ?? '',
                'type' => $translations['inventory_transactions']['type'] ?? '',
                'remark' => $translations['inventory_transactions']['remark'] ?? '',
                'user' => $translations['inventory_transactions']['user'] ?? '',
                'are_you_sure' => $translations['inventory_transactions']['are_you_sure'] ?? '',
                'stock_in' => $translations['inventory_transactions']['stock_in'] ?? '',
                'stock_out' => $translations['inventory_transactions']['stock_out'] ?? '',
                'transfer_quantity' => $translations['inventory_transactions']['transfer_quantity'] ?? '',
                'cancel' => $translations['inventory_transactions']['cancel'] ?? '',
                'update' => $translations['inventory_transactions']['update'] ?? '',
                'select_a_date_range' => $translations['inventory_transactions']['select_a_date_range'] ?? '',
                'date' => $translations['inventory_transactions']['date'] ?? '',
                'search' => $translations['inventory_transactions']['search'] ?? '',
                'close' => $translations['inventory_transactions']['close'] ?? '',

            ],
            'inventory_transfers' => [
                'inventory_transfers' => $translations['inventory_transfers']['inventory_transfers'] ?? '',
                'from_driver' => $translations['inventory_transfers']['from_driver'] ?? '',
                'from_lorry' => $translations['inventory_transfers']['from_lorry'] ?? '',
                'to_driver' => $translations['inventory_transfers']['to_driver'] ?? '',
                'to_lorry' => $translations['inventory_transfers']['to_lorry'] ?? '',
                'product' => $translations['inventory_transfers']['product'] ?? '',
                'quantity' => $translations['inventory_transfers']['quantity'] ?? '',
                'status' => $translations['inventory_transfers']['status'] ?? '',
                'remark' => $translations['inventory_transfers']['remark'] ?? '',
                'select_a_date_range' => $translations['inventory_transfers']['select_a_date_range'] ?? '',
                'date' => $translations['inventory_transfers']['date'] ?? '',
                'search' => $translations['inventory_transfers']['search'] ?? '',
                'close' => $translations['inventory_transfers']['close'] ?? '',

            ],
            'lorries' => [
                'lorries' => $translations['lorries']['lorries'] ?? '',
                'lorry_no' => $translations['lorries']['lorry_no'] ?? '',

                'lorry' => $translations['lorries']['lorry'] ?? '',
                'create' => $translations['lorries']['create'] ?? '',
                'edit' => $translations['lorries']['edit'] ?? '',
                'detail' => $translations['lorries']['detail'] ?? '',
                'create_lorry' => $translations['lorries']['create_lorry'] ?? '',
                'edit_lorry' => $translations['lorries']['edit_lorry'] ?? '',
                'save' => $translations['lorries']['save'] ?? '',
                'cancel' => $translations['lorries']['cancel'] ?? '',

                'tyre_next_date' => $translations['lorries']['tyre_next_date'] ?? '',
                'insurance_next_date' => $translations['lorries']['insurance_next_date'] ?? '',
                'permit_next_date' => $translations['lorries']['permit_next_date'] ?? '',
                'road_tax_next_date' => $translations['lorries']['road_tax_next_date'] ?? '',
                'inspection_next_date' => $translations['lorries']['inspection_next_date'] ?? '',
                'other_next_date' => $translations['lorries']['other_next_date'] ?? '',
                'fire_extinguisher' => $translations['lorries']['fire_extinguisher'] ?? '',
                'status' => $translations['lorries']['status'] ?? '',
                'remark' => $translations['lorries']['remark'] ?? '',
                
                'are_you_sure_to_delete_the_lorry' => $translations['lorries']['are_you_sure_to_delete_the_lorry'] ?? '',

                'saved_successfully' => $translations['lorries']['saved_successfully'] ?? '',
                'updated_successfully' => $translations['lorries']['updated_successfully'] ?? '',
                'deleted_successfully' => $translations['lorries']['deleted_successfully'] ?? '',

                'lorry_not_found' => $translations['lorries']['lorry_not_found'] ?? '',

            ],
            'lorry_service' => [
                'lorry_service' => $translations['lorry_service']['lorry_service'] ?? '',

                'lorry' => $translations['lorry_service']['lorry'] ?? '',
                'create' => $translations['lorry_service']['create'] ?? '',
                'edit' => $translations['lorry_service']['edit'] ?? '',
                'detail' => $translations['lorry_service']['detail'] ?? '',
                'create_lorry_service' => $translations['lorry_service']['create_lorry_service'] ?? '',
                'edit_lorry_service' => $translations['lorry_service']['edit_lorry_service'] ?? '',
                'save' => $translations['lorry_service']['save'] ?? '',
                'cancel' => $translations['lorry_service']['cancel'] ?? '',
                'action' => $translations['lorry_service']['action'] ?? '',

                'type' => $translations['lorry_service']['type'] ?? '',

                'type_other' => $translations['lorry_service']['type_other'] ?? '',
                'type_tyre' => $translations['lorry_service']['type_tyre'] ?? '',
                'type_insurance' => $translations['lorry_service']['type_insurance'] ?? '',
                'type_permit' => $translations['lorry_service']['type_permit'] ?? '',
                'type_road_tax' => $translations['lorry_service']['type_road_tax'] ?? '',
                'type_inspection' => $translations['lorry_service']['type_inspection'] ?? '',
                'type_fire_extinguisher' => $translations['lorry_service']['type_fire_extinguisher'] ?? '',

                'date' => $translations['lorry_service']['date'] ?? '',
                'next_date' => $translations['lorry_service']['next_date'] ?? '',
                'amount' => $translations['lorry_service']['amount'] ?? '',
                'remark' => $translations['lorry_service']['remark'] ?? '',
                
                'are_you_sure_to_delete_the_lorry_service' => $translations['lorry_service']['are_you_sure_to_delete_the_lorry_service'] ?? '',
                'select_a_date_range' => $translations['lorry_service']['select_a_date_range'] ?? '',
                'search' => $translations['lorry_service']['search'] ?? '',
                'close' => $translations['lorry_service']['close'] ?? '',

                'lorry_service_saved_successfully' => $translations['lorry_service']['lorry_service_saved_successfully'] ?? '',
                'lorry_service_not_found' => $translations['lorry_service']['lorry_service_not_found'] ?? '',
                'lorry_service_updated_successfully' => $translations['lorry_service']['lorry_service_updated_successfully'] ?? '',
                'lorry_service_deleted_successfully' => $translations['lorry_service']['lorry_service_deleted_successfully'] ?? '',
            ],
            'drivers' => [
                'drivers' => $translations['drivers']['drivers'] ?? '',

                'employee_id' => $translations['drivers']['employee_id'] ?? '',

                'create' => $translations['drivers']['create'] ?? '',
                'edit' => $translations['drivers']['edit'] ?? '',
                'detail' => $translations['drivers']['detail'] ?? '',
                'create_driver' => $translations['drivers']['create_driver'] ?? '',
                'edit_driver' => $translations['drivers']['edit_driver'] ?? '',
                'save' => $translations['drivers']['save'] ?? '',
                'cancel' => $translations['drivers']['cancel'] ?? '',

                'customer' => $translations['drivers']['customer'] ?? '',
                'code' => $translations['drivers']['code'] ?? '',
                'company' => $translations['drivers']['company'] ?? '',
                'phone' => $translations['drivers']['phone'] ?? '',
                'address' => $translations['drivers']['address'] ?? '',
                'sequence' => $translations['drivers']['sequence'] ?? '',
                'action' => $translations['drivers']['action'] ?? '',
                'no_matching_records_found' => $translations['drivers']['no_matching_records_found'] ?? '',

                'employee_password' => $translations['drivers']['employee_password'] ?? '',
                'name' => $translations['drivers']['name'] ?? '',
                'ic' => $translations['drivers']['ic'] ?? '',
                'phone' => $translations['drivers']['phone'] ?? '',
                'bank_details_1' => $translations['drivers']['bank_details_1'] ?? '',
                'bank_details_2' => $translations['drivers']['bank_details_2'] ?? '',
    
                'status' => $translations['drivers']['status'] ?? '',
                'remark' => $translations['drivers']['remark'] ?? '',
                'active' => $translations['drivers']['active'] ?? '',
                'unactive' => $translations['drivers']['unactive'] ?? '',

                'are_you_sure_to_delete_the_driver' => $translations['drivers']['are_you_sure_to_delete_the_driver'] ?? '',

                'assign_deleted_successfully' => $translations['drivers']['assign_deleted_successfully'] ?? '',
                'assign_saved_successfully' => $translations['drivers']['assign_saved_successfully'] ?? '',
                'saved_successfully' => $translations['drivers']['saved_successfully'] ?? '',
                'updated_successfully' => $translations['drivers']['updated_successfully'] ?? '',
                'deleted_successfully' => $translations['drivers']['deleted_successfully'] ?? '',

                'driver_not_found' => $translations['drivers']['driver_not_found'] ?? '',
                'assign_not_found' => $translations['drivers']['assign_not_found'] ?? '',

            ],
            'driver_locations' => [
                'driver_locations' => $translations['driver_locations']['driver_locations'] ?? '',
                'summary' => $translations['driver_locations']['summary'] ?? '',
                'transactions' => $translations['driver_locations']['transactions'] ?? '',
                'find_drivers_on_map' => $translations['driver_locations']['find_drivers_on_map'] ?? '',

                'select_a_date_range' => $translations['driver_locations']['select_a_date_range'] ?? '',
                'date' => $translations['driver_locations']['date'] ?? '',
                'search' => $translations['driver_locations']['search'] ?? '',
                'close' => $translations['driver_locations']['close'] ?? '',

                'are_you_sure' => $translations['driver_locations']['are_you_sure'] ?? '',
                'driver' => $translations['driver_locations']['driver'] ?? '',
                'kelindan' => $translations['driver_locations']['kelindan'] ?? '',

                'lorry' => $translations['driver_locations']['lorry'] ?? '',
                'latitude' => $translations['driver_locations']['latitude'] ?? '',
                'longitude' => $translations['driver_locations']['longitude'] ?? '',

            ],
            'kelindans' => [
                'kelindans' => $translations['kelindans']['kelindans'] ?? '',

                'create' => $translations['kelindans']['create'] ?? '',
                'edit' => $translations['kelindans']['edit'] ?? '',
                'detail' => $translations['kelindans']['detail'] ?? '',
                'create_kelindan' => $translations['kelindans']['create_kelindan'] ?? '',
                'edit_kelindan' => $translations['kelindans']['edit_kelindan'] ?? '',
                'save' => $translations['kelindans']['save'] ?? '',
                'cancel' => $translations['kelindans']['cancel'] ?? '',

                'status' => $translations['kelindans']['status'] ?? '',
                'remark' => $translations['kelindans']['remark'] ?? '',
                
                'are_you_sure_to_delete_the_kelindan' => $translations['kelindans']['are_you_sure_to_delete_the_kelindan'] ?? '',

                'employee_id' => $translations['kelindans']['employee_id'] ?? '',
                'name' => $translations['kelindans']['name'] ?? '',
                'ic' => $translations['kelindans']['ic'] ?? '',
                'phone' => $translations['kelindans']['phone'] ?? '',
                'bank_details_1' => $translations['kelindans']['bank_details_1'] ?? '',
                'bank_details_2' => $translations['kelindans']['bank_details_2'] ?? '',
                'permit_date' => $translations['kelindans']['permit_date'] ?? '',

                'status' => $translations['kelindans']['status'] ?? '',
                'remark' => $translations['kelindans']['remark'] ?? '',
                'active' => $translations['kelindans']['active'] ?? '',
                'unactive' => $translations['kelindans']['unactive'] ?? '',

                'saved_successfully' => $translations['kelindans']['saved_successfully'] ?? '',
                'updated_successfully' => $translations['kelindans']['updated_successfully'] ?? '',
                'deleted_successfully' => $translations['kelindans']['deleted_successfully'] ?? '',

                'kelindan_not_found' => $translations['kelindans']['kelindan_not_found'] ?? '',
            ],
            'agents' => [
                'agents' => $translations['agents']['agents'] ?? '',

                'create' => $translations['agents']['create'] ?? '',
                'edit' => $translations['agents']['edit'] ?? '',
                'detail' => $translations['agents']['detail'] ?? '',
                'create_agent' => $translations['agents']['create_agent'] ?? '',
                'edit_agent' => $translations['agents']['edit_agent'] ?? '',
                'save' => $translations['agents']['save'] ?? '',
                'cancel' => $translations['agents']['cancel'] ?? '',
                'close' => $translations['agents']['close'] ?? '',
                'upload' => $translations['agents']['upload'] ?? '',
                'status' => $translations['agents']['status'] ?? '',
                'remark' => $translations['agents']['remark'] ?? '',
                'action' => $translations['agents']['action'] ?? '',

                'are_you_sure_to_delete_the_agent' => $translations['agents']['are_you_sure_to_delete_the_agent'] ?? '',

                'employee_id' => $translations['agents']['employee_id'] ?? '',
                'name' => $translations['agents']['name'] ?? '',
                'ic' => $translations['agents']['ic'] ?? '',
                'phone' => $translations['agents']['phone'] ?? '',
                'bank_details_1' => $translations['agents']['bank_details_1'] ?? '',
                'bank_details_2' => $translations['agents']['bank_details_2'] ?? '',
                'first_vaccine_date' => $translations['agents']['first_vaccine_date'] ?? '',
                'second_vaccine_date' => $translations['agents']['second_vaccine_date'] ?? '',
                'body_temperature' => $translations['agents']['body_temperature'] ?? '',

                'attachment' => $translations['agents']['attachment'] ?? '',
                'add_attachment' => $translations['agents']['add_attachment'] ?? '',


                'status' => $translations['agents']['status'] ?? '',
                'remark' => $translations['agents']['remark'] ?? '',
                'active' => $translations['agents']['active'] ?? '',
                'unactive' => $translations['agents']['unactive'] ?? '',

                'saved_successfully' => $translations['agents']['saved_successfully'] ?? '',
                'agent_updated_successfully' => $translations['agents']['agent_updated_successfully'] ?? '',
                'deleted_successfully' => $translations['agents']['deleted_successfully'] ?? '',

                'agent_not_found' => $translations['agents']['agent_not_found'] ?? '',
            ],
            'operations' => [
                'operation' => $translations['operations']['operation'] ?? '',

                'create' => $translations['operations']['create'] ?? '',
                'edit' => $translations['operations']['edit'] ?? '',
                'detail' => $translations['operations']['detail'] ?? '',
                'create_operation' => $translations['operations']['create_operation'] ?? '',
                'edit_operation' => $translations['operations']['edit_operation'] ?? '',
                'save' => $translations['operations']['save'] ?? '',
                'cancel' => $translations['operations']['cancel'] ?? '',
                'close' => $translations['operations']['close'] ?? '',
                'upload' => $translations['operations']['upload'] ?? '',
                'status' => $translations['operations']['status'] ?? '',
                'remark' => $translations['operations']['remark'] ?? '',
                'action' => $translations['operations']['action'] ?? '',

                'are_you_sure_to_delete_the_supervisor' => $translations['operations']['are_you_sure_to_delete_the_supervisor'] ?? '',

                'employee_id' => $translations['operations']['employee_id'] ?? '',
                'name' => $translations['operations']['name'] ?? '',
                'ic' => $translations['operations']['ic'] ?? '',
                'phone' => $translations['operations']['phone'] ?? '',
                'bank_details_1' => $translations['operations']['bank_details_1'] ?? '',
                'bank_details_2' => $translations['operations']['bank_details_2'] ?? '',
                'first_vaccine_date' => $translations['operations']['first_vaccine_date'] ?? '',
                'second_vaccine_date' => $translations['operations']['second_vaccine_date'] ?? '',
                'body_temperature' => $translations['operations']['body_temperature'] ?? '',

                'active' => $translations['operations']['active'] ?? '',
                'unactive' => $translations['operations']['unactive'] ?? '',

                'saved_successfully' => $translations['operations']['saved_successfully'] ?? '',
                'updated_successfully' => $translations['operations']['updated_successfully'] ?? '',
                'deleted_successfully' => $translations['operations']['deleted_successfully'] ?? '',

                'operation_not_found' => $translations['operations']['operation_not_found'] ?? '',
            ],
            'products' => [
                'products' => $translations['products']['products'] ?? '',

                'create' => $translations['products']['create'] ?? '',
                'edit' => $translations['products']['edit'] ?? '',
                'detail' => $translations['products']['detail'] ?? '',
                'create_products' => $translations['products']['create_products'] ?? '',
                'edit_products' => $translations['products']['edit_products'] ?? '',
                'save' => $translations['products']['save'] ?? '',
                'cancel' => $translations['products']['cancel'] ?? '',
                'close' => $translations['products']['close'] ?? '',
                'upload' => $translations['products']['upload'] ?? '',
                'status' => $translations['products']['status'] ?? '',
                'remark' => $translations['products']['remark'] ?? '',
                'action' => $translations['products']['action'] ?? '',

                'are_you_sure_to_delete_the_products' => $translations['products']['are_you_sure_to_delete_the_products'] ?? '',

                'code' => $translations['products']['code'] ?? '',
                'name' => $translations['products']['name'] ?? '',
                'price' => $translations['products']['price'] ?? '',
                'type' => $translations['products']['type'] ?? '',
                'type_ice' => $translations['products']['type_ice'] ?? '',
                'type_other' => $translations['products']['type_other'] ?? '',
                'type_cocoa' => $translations['products']['type_cocoa'] ?? '',
                'type_tea' => $translations['products']['type_tea'] ?? '',
                'type_coffee' => $translations['products']['type_coffee'] ?? '',

                'active' => $translations['products']['active'] ?? '',
                'unactive' => $translations['products']['unactive'] ?? '',

                'saved_successfully' => $translations['products']['saved_successfully'] ?? '',
                'updated_successfully' => $translations['products']['updated_successfully'] ?? '',
                'deleted_successfully' => $translations['products']['deleted_successfully'] ?? '',

                'product_not_found' => $translations['products']['product_not_found'] ?? '',
            ],
            'customers' => [
                'customers' => $translations['customers']['customers'] ?? '',

                'create' => $translations['customers']['create'] ?? '',
                'edit' => $translations['customers']['edit'] ?? '',
                'detail' => $translations['customers']['detail'] ?? '',
                'create_customer' => $translations['customers']['create_customer'] ?? '',
                'edit_customer' => $translations['customers']['edit_customer'] ?? '',
                'save' => $translations['customers']['save'] ?? '',
                'cancel' => $translations['customers']['cancel'] ?? '',
                'close' => $translations['customers']['close'] ?? '',
                'upload' => $translations['customers']['upload'] ?? '',
                'status' => $translations['customers']['status'] ?? '',
                'remark' => $translations['customers']['remark'] ?? '',
                'action' => $translations['customers']['action'] ?? '',

                'are_you_sure_to_delete_the_customer' => $translations['customers']['are_you_sure_to_delete_the_customer'] ?? '',
                'code' => $translations['customers']['code'] ?? '',

                'company' => $translations['customers']['company'] ?? '',
                'chinese_name' => $translations['customers']['chinese_name'] ?? '',
                'paymentterm' => $translations['customers']['paymentterm'] ?? '',
                'agent' => $translations['customers']['agent'] ?? '',
                'phone' => $translations['customers']['phone'] ?? '',
                'address' => $translations['customers']['address'] ?? '',
                'tin' => $translations['customers']['tin'] ?? '',

                'payment_term_cash' => $translations['customers']['payment_term_credit_note'] ?? '',
                'payment_term_credit_note' => $translations['customers']['payment_term_credit_note'] ?? '',

                'operation' => $translations['customers']['operation'] ?? '',
                'ssm' => $translations['customers']['ssm'] ?? '',
                'sst' => $translations['customers']['sst'] ?? '',

                'group' => $translations['customers']['group'] ?? '',

                'active' => $translations['customers']['active'] ?? '',
                'unactive' => $translations['customers']['unactive'] ?? '',

                'customer_saved_successfully' => $translations['customers']['customer_saved_successfully'] ?? '',
                'customer_updated_successfully' => $translations['customers']['customer_updated_successfully'] ?? '',
                'deleted_successfully' => $translations['customers']['deleted_successfully'] ?? '',
                'customer_not_found' => $translations['customers']['customer_not_found'] ?? '',
            ],
            'companies' => [
                'companies' => $translations['companies']['companies'] ?? '',

                'create' => $translations['companies']['create'] ?? '',
                'edit' => $translations['companies']['edit'] ?? '',
                'detail' => $translations['companies']['detail'] ?? '',
                'create_company' => $translations['companies']['create_company'] ?? '',
                'edit_company' => $translations['companies']['edit_company'] ?? '',
                'save' => $translations['companies']['save'] ?? '',
                'cancel' => $translations['companies']['cancel'] ?? '',
                'close' => $translations['companies']['close'] ?? '',
                'status' => $translations['companies']['status'] ?? '',
                'remark' => $translations['companies']['remark'] ?? '',
                'action' => $translations['companies']['action'] ?? '',

                'are_you_sure' => $translations['companies']['are_you_sure'] ?? '',
                'code' => $translations['companies']['code'] ?? '',

                'company' => $translations['companies']['company'] ?? '',
                'name' => $translations['companies']['name'] ?? '',
                'address1' => $translations['companies']['address1'] ?? '',
                'address2' => $translations['companies']['address2'] ?? '',
                'address3' => $translations['companies']['address3'] ?? '',
                'address4' => $translations['companies']['address4'] ?? '',


                'ssm' => $translations['companies']['ssm'] ?? '',

                'group' => $translations['companies']['group'] ?? '',

                'active' => $translations['companies']['active'] ?? '',
                'unactive' => $translations['companies']['unactive'] ?? '',

                'company_saved_successfully' => $translations['companies']['company_saved_successfully'] ?? '',
                'company_updated_successfully' => $translations['companies']['company_updated_successfully'] ?? '',
                'company_deleted_successfully' => $translations['companies']['company_deleted_successfully'] ?? '',
                'company_not_found' => $translations['companies']['company_not_found'] ?? '',
            ],
            'special_prices' => [
                'special_price' => $translations['special_prices']['special_price'] ?? '',

                'create' => $translations['special_prices']['create'] ?? '',
                'edit' => $translations['special_prices']['edit'] ?? '',
                'detail' => $translations['special_prices']['detail'] ?? '',
                'create_special_price' => $translations['special_prices']['create_special_price'] ?? '',
                'edit_special_price' => $translations['special_prices']['edit_special_price'] ?? '',
                'save' => $translations['special_prices']['save'] ?? '',
                'cancel' => $translations['special_prices']['cancel'] ?? '',
                'close' => $translations['special_prices']['close'] ?? '',
                'status' => $translations['special_prices']['status'] ?? '',
                'action' => $translations['special_prices']['action'] ?? '',

                'are_you_sure_to_delete_the_special_price' => $translations['special_prices']['are_you_sure_to_delete_the_special_price'] ?? '',

                'product' => $translations['special_prices']['product'] ?? '',
                'customer' => $translations['special_prices']['customer'] ?? '',
                'price' => $translations['special_prices']['price'] ?? '',

                'active' => $translations['special_prices']['active'] ?? '',
                'unactive' => $translations['special_prices']['unactive'] ?? '',

                'special_price_saved_successfully' => $translations['special_prices']['special_price_saved_successfully'] ?? '',
                'special_price_updated_successfully' => $translations['special_prices']['special_price_updated_successfully'] ?? '',
                'special_price_deleted_successfully' => $translations['special_prices']['special_price_deleted_successfully'] ?? '',
                'special_price_not_found' => $translations['special_prices']['special_price_not_found'] ?? '',
            ],
            'focs' => [
                'focs' => $translations['focs']['focs'] ?? '',

                'create' => $translations['focs']['create'] ?? '',
                'edit' => $translations['focs']['edit'] ?? '',
                'detail' => $translations['focs']['detail'] ?? '',
                'create_focs' => $translations['focs']['create_focs'] ?? '',
                'edit_focs' => $translations['focs']['edit_focs'] ?? '',
                'save' => $translations['focs']['save'] ?? '',
                'cancel' => $translations['focs']['cancel'] ?? '',
                'close' => $translations['focs']['close'] ?? '',
                'status' => $translations['focs']['status'] ?? '',
                'action' => $translations['focs']['action'] ?? '',

                'are_you_sure_to_delete_the_foc' => $translations['focs']['are_you_sure_to_delete_the_foc'] ?? '',

                'product' => $translations['focs']['product'] ?? '',
                'customer' => $translations['focs']['customer'] ?? '',
                'quantity' => $translations['focs']['quantity'] ?? '',
                'achieve_quantity' => $translations['focs']['achieve_quantity'] ?? '',
                'free_product' => $translations['focs']['free_product'] ?? '',
                'free_quantity' => $translations['focs']['free_quantity'] ?? '',
                'start_date' => $translations['focs']['start_date'] ?? '',
                'end_date' => $translations['focs']['end_date'] ?? '',

                'active' => $translations['focs']['active'] ?? '',
                'unactive' => $translations['focs']['unactive'] ?? '',

                'foc_saved_successfully' => $translations['focs']['foc_saved_successfully'] ?? '',
                'foc_updated_successfully' => $translations['focs']['foc_updated_successfully'] ?? '',
                'foc_deleted_successfully' => $translations['focs']['foc_deleted_successfully'] ?? '',
                'foc_not_found' => $translations['focs']['foc_not_found'] ?? '',
            ],
            'assign' => [
                'assigns' => $translations['assign']['assigns'] ?? '',

                'create' => $translations['assign']['create'] ?? '',
                'edit' => $translations['assign']['edit'] ?? '',
                'detail' => $translations['assign']['detail'] ?? '',
                'create_assigns' => $translations['assign']['create_assigns'] ?? '',
                'edit_assigns' => $translations['assign']['edit_assigns'] ?? '',
                'save' => $translations['assign']['save'] ?? '',
                'cancel' => $translations['assign']['cancel'] ?? '',
                'close' => $translations['assign']['close'] ?? '',
                'status' => $translations['assign']['status'] ?? '',
                'action' => $translations['assign']['action'] ?? '',
                'by_customer_group' => $translations['assign']['by_customer_group'] ?? '',

                'are_you_sure_to_delete_the_assign' => $translations['assign']['are_you_sure_to_delete_the_assign'] ?? '',

                'driver' => $translations['assign']['driver'] ?? '',
                'driver_code' => $translations['assign']['driver_code'] ?? '',
                'driver_name' => $translations['assign']['driver_name'] ?? '',
                'customer_code' => $translations['assign']['customer_code'] ?? '',
                'customer_company' => $translations['assign']['customer_company'] ?? '',

                'placeholder_pick_driver' => $translations['assign']['placeholder_pick_driver'] ?? '',
                'placeholder_pick_group' => $translations['assign']['placeholder_pick_group'] ?? '',
                'company' => $translations['assign']['company'] ?? '',


                'customer' => $translations['assign']['customer'] ?? '',
                'sequence' => $translations['assign']['sequence'] ?? '',
                'group' => $translations['assign']['group'] ?? '',

                'assign_saved_successfully' => $translations['assign']['assign_saved_successfully'] ?? '',
                'assign_updated_successfully' => $translations['assign']['assign_updated_successfully'] ?? '',
                'assign_deleted_successfully' => $translations['assign']['assign_deleted_successfully'] ?? '',
                'assign_not_found' => $translations['assign']['assign_not_found'] ?? '',
            ],

            'codes' => [
                'codes' => $translations['codes']['codes'] ?? '',
                'code' => $translations['codes']['code'] ?? '',

                'create' => $translations['codes']['create'] ?? '',
                'edit' => $translations['codes']['edit'] ?? '',
                'detail' => $translations['codes']['detail'] ?? '',
                'create_codes' => $translations['codes']['create_codes'] ?? '',
                'edit_codes' => $translations['codes']['edit_codes'] ?? '',
                'save' => $translations['codes']['save'] ?? '',
                'cancel' => $translations['codes']['cancel'] ?? '',
                'close' => $translations['codes']['close'] ?? '',
                'status' => $translations['codes']['status'] ?? '',
                'action' => $translations['codes']['action'] ?? '',

                'are_you_sure_to_delete_the_assign' => $translations['codes']['are_you_sure_to_delete_the_assign'] ?? '',

                'description' => $translations['codes']['description'] ?? '',
                'value' => $translations['codes']['value'] ?? '',
                'sequence' => $translations['codes']['sequence'] ?? '',
                'created_at' => $translations['codes']['created_at'] ?? '',
                'updated_at' => $translations['codes']['updated_at'] ?? '',

                'code_saved_successfully' => $translations['codes']['code_saved_successfully'] ?? '',
                'code_updated_successfully' => $translations['codes']['code_updated_successfully'] ?? '',
                'code_deleted_successfully' => $translations['codes']['code_deleted_successfully'] ?? '',
                'code_not_found' => $translations['codes']['code_not_found'] ?? '',
            ],

            'customer_group' => [
                'customer_group' => $translations['customer_group']['customer_group'] ?? '',

                'create' => $translations['customer_group']['create'] ?? '',
                'edit' => $translations['customer_group']['edit'] ?? '',
                'detail' => $translations['customer_group']['detail'] ?? '',
                'create_customer_group' => $translations['customer_group']['create_customer_group'] ?? '',
                'edit_customer_group' => $translations['customer_group']['edit_customer_group'] ?? '',
                'save' => $translations['customer_group']['save'] ?? '',
                'cancel' => $translations['customer_group']['cancel'] ?? '',
                'close' => $translations['customer_group']['close'] ?? '',
                'status' => $translations['customer_group']['status'] ?? '',
                'action' => $translations['customer_group']['action'] ?? '',

                'are_you_sure_to_delete_the_group' => $translations['customer_group']['are_you_sure_to_delete_the_group'] ?? '',

                'description' => $translations['customer_group']['description'] ?? '',
                'created_at' => $translations['customer_group']['created_at'] ?? '',
                'updated_at' => $translations['customer_group']['updated_at'] ?? '',

                'customer_group_saved_successfully' => $translations['customer_group']['customer_group_saved_successfully'] ?? '',
                'customer_group_updated_successfully' => $translations['customer_group']['customer_group_updated_successfully'] ?? '',
                'customer_group_deleted_successfully' => $translations['customer_group']['customer_group_deleted_successfully'] ?? '',
                'customer_group_not_found' => $translations['customer_group']['customer_group_not_found'] ?? '',
            ],

            'commission' => [
                'commission' => $translations['commission']['commission'] ?? '',

                'create' => $translations['commission']['create'] ?? '',
                'edit' => $translations['commission']['edit'] ?? '',
                'detail' => $translations['commission']['detail'] ?? '',
                'create_commission' => $translations['commission']['create_commission'] ?? '',
                'edit_commission' => $translations['commission']['edit_commission'] ?? '',
                'save' => $translations['commission']['save'] ?? '',
                'cancel' => $translations['commission']['cancel'] ?? '',
                'close' => $translations['commission']['close'] ?? '',
                'status' => $translations['commission']['status'] ?? '',
                'action' => $translations['commission']['action'] ?? '',

                'are_you_sure_to_delete_the_commission' => $translations['commission']['are_you_sure_to_delete_the_commission'] ?? '',

                'commission_type' => $translations['commission']['commission_type'] ?? '',
                'agent_commission' => $translations['commission']['agent_commission'] ?? '',
                'kelindan_commission' => $translations['commission']['kelindan_commission'] ?? '',
                'driver_commission' => $translations['commission']['driver_commission'] ?? '',
                'operation_commission' => $translations['commission']['operation_commission'] ?? '',
                'product_type' => $translations['commission']['product_type'] ?? '',
                'ice' => $translations['commission']['ice'] ?? '',
                'value' => $translations['commission']['value'] ?? '',

                'customer_group_saved_successfully' => $translations['commission']['customer_group_saved_successfully'] ?? '',
                'customer_group_updated_successfully' => $translations['commission']['customer_group_updated_successfully'] ?? '',
                'customer_group_deleted_successfully' => $translations['commission']['customer_group_deleted_successfully'] ?? '',
                'commission_group_not_found' => $translations['commission']['commission_group_not_found'] ?? '',
            ],

            'language_translation' => [
                'language_translation' => $translations['language_translation']['language_translation'] ?? '',

                'system_language' => $translations['language_translation']['system_language'] ?? '',
                'select_system_language' => $translations['language_translation']['select_system_language'] ?? '',
                'choose_language' => $translations['language_translation']['choose_language'] ?? '',


                'apply' => $translations['language_translation']['apply'] ?? '',

                'changes_will_affect_the_entire_system_interface' => $translations['language_translation']['changes_will_affect_the_entire_system_interface'] ?? '',
                'import_new_language' => $translations['language_translation']['import_new_language'] ?? '',
                
                
                'select_language_to_import' => $translations['language_translation']['select_language_to_import'] ?? '',
                'only_shows_languages_not_already_imported' => $translations['language_translation']['only_shows_languages_not_already_imported'] ?? '',

                'save_all_translations' => $translations['language_translation']['save_all_translations'] ?? '',

                'system_language_changed_successfully' => $translations['language_translation']['system_language_changed_successfully'] ?? '',
                'system_language_updated_successfully' => $translations['language_translation']['system_language_updated_successfully'] ?? '',
                'system_language_saved_successfully' => $translations['language_translation']['system_language_saved_successfully'] ?? '',
                'language_ready_for_translation' => $translations['language_translation']['language_ready_for_translation'] ?? '',

                'export_translations' =>$translations['language_translation']['export_translations'] ?? '',

                'select_language_to_export' =>$translations['language_translation']['select_language_to_export'] ?? '',
                'select_language' =>$translations['language_translation']['select_language'] ?? '',
                'export_all_translations_for_selected_language' =>$translations['language_translation']['export_all_translations_for_selected_language'] ?? '',
                'upload' =>$translations['language_translation']['upload'] ?? '',
                'export' =>$translations['language_translation']['export'] ?? '',
                'bulk_import_translations' =>$translations['language_translation']['bulk_import_translations'] ?? '',

                'no_file_chosen' =>$translations['language_translation']['no_file_chosen'] ?? '',
                'browse' =>$translations['language_translation']['browse'] ?? '',

                'accepted_formats' =>$translations['language_translation']['accepted_formats'] ?? '',
                'translations_imported_successfully' =>$translations['language_translation']['translations_imported_successfully'] ?? '',
                'failed_to_import_translations' =>$translations['language_translation']['failed_to_import_translations'] ?? '',
                'download_template' =>$translations['language_translation']['download_template'] ?? '',
                'upload_translations' =>$translations['language_translation']['upload_translations'] ?? '',

                'manage_languages' =>$translations['language_translation']['manage_languages'] ?? '',
                'language_name' =>$translations['language_translation']['language_name'] ?? '',
                'actions' =>$translations['language_translation']['actions'] ?? '',
                'delete' =>$translations['language_translation']['delete'] ?? '',
                'protected' =>$translations['language_translation']['protected'] ?? '',

                'language_deleted_successfully' =>$translations['language_translation']['language_deleted_successfully'] ?? '',
                'confirm_delete_language' =>$translations['language_translation']['confirm_delete_language'] ?? '',
                'import' =>$translations['language_translation']['import'] ?? '',
                'search_page_or_text' =>$translations['language_translation']['search_page_or_text'] ?? '',

            ],
            'mobile_language_translation' => [
                'mobile_app_language_translation' => $translations['mobile_language_translation']['mobile_app_language_translation'] ?? '',
                'mobile_app_translation' => $translations['mobile_language_translation']['mobile_app_translation'] ?? '',

                'available_languages' => $translations['mobile_language_translation']['available_languages'] ?? '',
                'choose_language' => $translations['mobile_language_translation']['choose_language'] ?? '',

                'language' => $translations['mobile_language_translation']['language'] ?? '',
                'action' => $translations['mobile_language_translation']['action'] ?? '',

                'edit' => $translations['mobile_language_translation']['edit'] ?? '',
                'delete' => $translations['mobile_language_translation']['delete'] ?? '',

                'import_new_language' => $translations['mobile_language_translation']['import_new_language'] ?? '',
                
                'default_english_text' => $translations['mobile_language_translation']['default_english_text'] ?? '',
                'translation' => $translations['mobile_language_translation']['translation'] ?? '',

                'select_language_to_import' => $translations['mobile_language_translation']['select_language_to_import'] ?? '',
                'only_shows_languages_not_already_imported' => $translations['mobile_language_translation']['only_shows_languages_not_already_imported'] ?? '',

                'save_all_translations' => $translations['mobile_language_translation']['save_all_translations'] ?? '',
                'mobile_language_saved_successfully' => $translations['mobile_language_translation']['mobile_language_saved_successfully'] ?? '',
                'language_selected_for_editing' => $translations['mobile_language_translation']['language_selected_for_editing'] ?? '',
                'language_ready_for_translation' => $translations['mobile_language_translation']['language_ready_for_translation'] ?? '',
                'mobile_language_delete_successfully' => $translations['mobile_language_translation']['mobile_language_delete_successfully'] ?? '',
                'mobile_language_cannot_delete' => $translations['mobile_language_translation']['mobile_language_cannot_delete'] ?? '',
                
                'export_translations' => $translations['mobile_language_translation']['export_translations'] ?? '',
                'select_language_to_export' => $translations['mobile_language_translation']['select_language_to_export'] ?? '',
                'export' => $translations['mobile_language_translation']['export'] ?? '',
                'export_all_translations_for_selected_language' => $translations['mobile_language_translation']['export_all_translations_for_selected_language'] ?? '',
                'bulk_import_translations' => $translations['mobile_language_translation']['bulk_import_translations'] ?? '',
                'select_language' => $translations['mobile_language_translation']['select_language'] ?? '',
                'upload' => $translations['mobile_language_translation']['upload'] ?? '',
                'accepted_formats' => $translations['mobile_language_translation']['accepted_formats'] ?? '',
                'search' => $translations['mobile_language_translation']['search'] ?? '',
                'import' => $translations['mobile_language_translation']['import'] ?? '',

                'failed_to_import_translations' => $translations['mobile_language_translation']['failed_to_import_translations'] ?? '',
                'translations_imported_successfully' => $translations['mobile_language_translation']['translations_imported_successfully'] ?? '',
            ],
            'user' => [
                'users' => $translations['user']['users'] ?? '',

                'create' => $translations['user']['create'] ?? '',
                'edit' => $translations['user']['edit'] ?? '',
                'detail' => $translations['user']['detail'] ?? '',
                'create_user' => $translations['user']['create_user'] ?? '',
                'edit_user' => $translations['user']['edit_user'] ?? '',
                'save' => $translations['user']['save'] ?? '',
                'cancel' => $translations['user']['cancel'] ?? '',
                'close' => $translations['user']['close'] ?? '',
                'status' => $translations['user']['status'] ?? '',
                'action' => $translations['user']['action'] ?? '',

                'are_you_sure_to_delete_the_user' => $translations['user']['are_you_sure_to_delete_the_user'] ?? '',

                'name' => $translations['user']['name'] ?? '',
                'email' => $translations['user']['email'] ?? '',
                'password' => $translations['user']['password'] ?? '',
                'password_confirmation' => $translations['user']['password_confirmation'] ?? '',
                'role' => $translations['user']['role'] ?? '',
                'created_at' => $translations['user']['created_at'] ?? '',
                'updated_at' => $translations['user']['updated_at'] ?? '',

                'user_saved_successfully' => $translations['user']['user_saved_successfully'] ?? '',
                'user_updated_successfully' => $translations['user']['user_updated_successfully'] ?? '',
                'user_deleted_successfully' => $translations['user']['user_deleted_successfully'] ?? '',
                'user_not_found' => $translations['user']['user_not_found'] ?? '',
                'you_have_exceeded_your_user_limit_please_contact_your_vendor' => $translations['user']['you_have_exceeded_your_user_limit_please_contact_your_vendor'] ?? '',

            ],
            'role' => [
                'roles' => $translations['role']['roles'] ?? '',

                'create' => $translations['role']['create'] ?? '',
                'edit' => $translations['role']['edit'] ?? '',
                'detail' => $translations['role']['detail'] ?? '',
                'create_role' => $translations['role']['create_role'] ?? '',
                'edit_role' => $translations['role']['edit_role'] ?? '',
                'save' => $translations['role']['save'] ?? '',
                'cancel' => $translations['role']['cancel'] ?? '',
                'close' => $translations['role']['close'] ?? '',
                'status' => $translations['role']['status'] ?? '',
                'action' => $translations['role']['action'] ?? '',

                'are_you_sure_to_delete_the_role' => $translations['role']['are_you_sure_to_delete_the_role'] ?? '',

                'name' => $translations['role']['name'] ?? '',
                'permissions' => $translations['role']['permissions'] ?? '',

                'role_saved_successfully' => $translations['role']['role_saved_successfully'] ?? '',
                'role_updated_successfully' => $translations['role']['role_updated_successfully'] ?? '',
                'role_deleted_successfully' => $translations['role']['role_deleted_successfully'] ?? '',
                'role_not_found' => $translations['role']['role_not_found'] ?? '',
            ],
            'report' => [
                'reports' => $translations['report']['reports'] ?? '',

                'create' => $translations['report']['create'] ?? '',
                'edit' => $translations['report']['edit'] ?? '',
                'detail' => $translations['report']['detail'] ?? '',
                'create_report' => $translations['report']['create_report'] ?? '',
                'edit_report' => $translations['report']['edit_report'] ?? '',
                'save' => $translations['report']['save'] ?? '',
                'cancel' => $translations['report']['cancel'] ?? '',
                'close' => $translations['report']['close'] ?? '',
                'status' => $translations['report']['status'] ?? '',
                'action' => $translations['report']['action'] ?? '',

                'are_you_sure' => $translations['report']['are_you_sure'] ?? '',

                'name' => $translations['report']['name'] ?? '',
                'sql' => $translations['report']['sql'] ?? '',
                'status' => $translations['report']['status'] ?? '',
                'active' => $translations['report']['active'] ?? '',
                'unactive' => $translations['report']['unactive'] ?? '',
                'run' => $translations['report']['run'] ?? '',

                'report_saved_successfully' => $translations['report']['report_saved_successfully'] ?? '',
                'report_updated_successfully' => $translations['report']['report_updated_successfully'] ?? '',
                'report_deleted_successfully' => $translations['report']['report_deleted_successfully'] ?? '',
                'report_not_found' => $translations['report']['report_not_found'] ?? '',
            ],
        ];


        return view('language.index', [
            'languages' => $allLanguages,
            'availableSystemLanguages' => $availableSystemLanguages,
            'currentLanguage' => $currentLanguage,
            'translations' => $translations,
            'pages' => $pages,
        ]);
    }

    public function changeLanguage(Request $request)
    {
        $request->validate([
            'language' => 'required|exists:languages,code',
        ]);

        $languageCode = $request->language;
        App::setLocale($languageCode);
        session(['current_language' => $languageCode]);
        session()->forget('is_imported');

        $language = Language::where('code', $languageCode)->firstOrFail();

        Flash::success(__('language_translation.system_language_updated_successfully').':'.$language->name);

        return back();
    }

    public function saveTranslations(Request $request)
    {
        $currentLanguage = $request->input('current_language');
        $translations = $request->input('translations', []);

        $language = Language::where('code', $currentLanguage)->firstOrFail();

        $englishTranslations = LanguageTranslation::where('language_id', 1)
            ->get()
            ->keyBy(function($item) {
                return $item->page . '.' . $item->key;
            });

        foreach ($translations as $page => $items) {
            foreach ($items as $key => $value) {
                $finalValue = empty($value) 
                    ? ($englishTranslations[$page.'.'.$key]->translated_text ?? '')
                    : $value;
                    
                LanguageTranslation::updateOrCreate(
                    [
                        'language_id' => $language->id,
                        'page' => $page,
                        'key' => $key,
                    ],
                    ['translated_text' => $finalValue]
                );
            }
        }
        $this->syncLanguageFiles($language->id);

        Flash::success(__('language_translation.system_language_saved_successfully'));
        session()->forget('is_imported');

        return back();
    }

    public function importLanguage(Request $request)
    {
        $request->validate([
            'language' => 'required|exists:languages,code',
        ]);

        $language = Language::where('code', $request->language)->firstOrFail();
        $languageCode = $language->code;

        $englishTranslations = LanguageTranslation::where('language_id', 1)->get();
    
        foreach ($englishTranslations as $englishTranslation) {
            LanguageTranslation::updateOrCreate(
                [
                    'language_id' => $language->id,
                    'page' => $englishTranslation->page,
                    'key' => $englishTranslation->key,
                ],
                ['translated_text' => $englishTranslation->translated_text]
            );
        }
        
        session(['current_language' => $languageCode]);
        session(['is_imported' => true]);

        Flash::success(__('language_translation.language_ready_for_translation'));
        return redirect()->route('language.index');
    }

    public function exportTranslations(Request $request)
    {
        $request->validate([
            'language' => 'required|exists:languages,code',
            'format' => 'required|in:json,csv,zip',
        ]);

        $language = Language::where('code', $request->language)->firstOrFail();
        $translations = LanguageTranslation::where('language_id', $language->id)
            ->get()
            ->groupBy('page');

        $fileName = "translations_{$language->code}_" . now()->format('Ymd_His');
        
        if ($request->format === 'json') {
            $jsonData = [];
            foreach ($translations as $page => $items) {
                $jsonData[$page] = $items->pluck('translated_text', 'key')->toArray();
            }
            
            $headers = [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename={$fileName}.json",
            ];
            
            return response()->json($jsonData, 200, $headers, JSON_PRETTY_PRINT);
        }
        
        Flash::error('Invalid export format');
        return back();
    }

    public function importTranslations(Request $request)
    {

         $request->validate([
            'language' => 'required|exists:languages,code',
            'file' => 'required|file|mimes:json|max:10240', // Allow text/plain
        ]);

        $language = Language::where('code', $request->language)->firstOrFail();
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        
        try {
            if ($extension === 'json') {
                $this->importJsonFile($file, $language);
            }
            
            Flash::success(__('language_translation.translations_imported_successfully'));
        } catch (\Exception $e) {
            Flash::error(__('language_translation.failed_to_import_translations') . ': ' . $e->getMessage());
        }
        
        return back();
    }

    protected function importJsonFile($file, $language)
    {
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON file');
        }
        
        $this->processImportData($data, $language);
    }

    protected function processImportData($data, $language)
    {
        foreach ($data as $page => $translations) {
            foreach ($translations as $key => $value) {
                if (!empty($value)) {
                    LanguageTranslation::updateOrCreate(
                        [
                            'language_id' => $language->id,
                            'page' => $page,
                            'key' => $key,
                        ],
                        ['translated_text' => $value]
                    );
                }
            }
        }
        
        $this->syncLanguageFiles($language->id);
    }

    protected function syncLanguageFiles($languageId)
    {
        $language = Language::find($languageId);
        if (!$language) return;
        
        $languageCode = $language->code;
        $langPath = resource_path('lang/' . $languageCode);

        if (!file_exists($langPath)) {
            mkdir($langPath, 0755, true);
        }

        $translations = LanguageTranslation::where('language_id', $languageId)
            ->get()
            ->groupBy('page');

        foreach ($translations as $page => $pageTranslations) {
            $content = "<?php\n\nreturn [\n";
            foreach ($pageTranslations as $translation) {
                $key = $translation->key;
                $translatedText = $translation->translated_text ?: '';
                $translatedText = str_replace("'", "\\'", $translatedText);
                $content .= "    '$key' => '$translatedText',\n";
            }
            $content .= "];\n";

            $filePath = $langPath . '/' . $page . '.php';
            file_put_contents($filePath, $content);
        }
    }

    public function deleteLanguage($id) 
    {
        $currentLanguage = session('current_language', config('app.locale', 'en'));

        try {
            $language = Language::findOrFail($id);
            
            // Prevent deletion of default English language
            if ($language->code === 'en') {
                Flash::error(__('language_translation.cannot_delete_default_language'));
                return back();
            }
            
            // Prevent deletion of currently active language
            if ($language->code === app()->getLocale()) {
                Flash::error(__('language_translation.cannot_delete_active_language'));
                return back();
            }
            // Delete all translations for this language
            LanguageTranslation::where('language_id', $language->id)->delete();
                        
            // Remove language files if they exist
            $this->deleteLanguageFiles($language->code);
            Flash::success(__('language_translation.language_deleted_successfully'));
        } catch (\Exception $e) {
            Flash::error('Error deleting language: ' . $e->getMessage());
        }
        
        return back();
    }

    protected function deleteLanguageFiles($languageCode)
    {
        $langPath = resource_path('lang/' . $languageCode);
        
        if (file_exists($langPath)) {
            // Delete all files in the language directory
            $files = glob($langPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            
            // Remove the directory itself
            rmdir($langPath);
        }
    }

}