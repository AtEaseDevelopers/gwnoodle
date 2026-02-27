<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\Kelindan;



use App\Models\Customer;
use App\Models\Product;
use App\Models\Supervisor;
use App\Models\Agent;


use App\Models\User;
use App\Models\Role;
use App\Models\Code;
use App\Models\DeliveryOrder;
use App\Models\Permission;
use App\Models\Item;
use App\Models\Vendor;
use App\Models\Location;
use App\Models\Lorry;
use App\Models\Driver;
use App\Models\Loan;
use App\Models\Price;
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use View;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer(['task_transfers.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });
        View::composer(['task_transfers.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });
        View::composer(['inventory_transfers.fields'], function ($view) {
            $productItems = Product::pluck('name','id')->toArray();
            $view->with('productItems', $productItems);
        });
        View::composer(['inventory_transfers.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });
        View::composer(['inventory_transfers.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });
        View::composer(['inventory_transfers.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });
        View::composer(['inventory_transfers.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });
        View::composer(['inventory_transactions.fields'], function ($view) {
            $productItems = Product::pluck('name','id')->toArray();
            $view->with('productItems', $productItems);
        });
        View::composer(['inventory_transactions.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });
        View::composer(['inventory_balances.index'], function ($view) {
            $productItems = Product::pluck('name','id')->toArray();
            $view->with('productItems', $productItems);
        });
        View::composer(['inventory_balances.index'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });

        View::composer(['inventory_balances.fields'], function ($view) {
            $productItems = Product::pluck('name','id')->toArray();
            $view->with('productItems', $productItems);
        });
        View::composer(['inventory_balances.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });
        View::composer(['trips.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });
        View::composer(['trips.fields'], function ($view) {
            $kelindanItems = Kelindan::orderBy("name")->pluck('name','id')->toArray();
            $view->with('kelindanItems', $kelindanItems);
        });
        View::composer(['trips.fields'], function ($view) {
            $driverItems = Driver::orderBy("name")->pluck('name','id')->toArray();
            $view->with('driverItems', $driverItems);
        });
        View::composer(['tasks.fields'], function ($view) {
            $invoiceItems = Invoice::where('status','0')->pluck('invoiceno','id')->toArray();
            $view->with('invoiceItems', $invoiceItems);
        });
        View::composer(['tasks.fields'], function ($view) {
            $customerItems = Customer::orderBy("company")->pluck('company','id')->toArray();
            $view->with('customerItems', $customerItems);
        });
        View::composer(['tasks.fields'], function ($view) {
            $driverItems = Driver::orderBy("name")->pluck('name','id')->toArray();
            $view->with('driverItems', $driverItems);
        });
        View::composer(['invoice_payments.fields'], function ($view) {
            $customerItems = Customer::orderBy("company")->pluck('company','id')->toArray();
            $view->with('customerItems', $customerItems);
        });
        View::composer(['invoice_payments.fields'], function ($view) {
            $invoiceItems = Invoice::pluck('invoiceno','id')->toArray();
            $view->with('invoiceItems', $invoiceItems);
        });
        View::composer(['invoice_details.fields','invoices.detail'], function ($view) {
            $productItems = Product::pluck('name','id')->toArray();
            $view->with('productItems', $productItems);
        });
        View::composer(['invoice_details.fields','invoices.detail'], function ($view) {
            $invoiceItems = Invoice::pluck('invoiceno','id')->toArray();
            $view->with('invoiceItems', $invoiceItems);
        });
        View::composer(['invoices.fields'], function ($view) {
            $supervisorItems = Supervisor::pluck('name','id')->toArray();
            $view->with('supervisorItems', $supervisorItems);
        });
        View::composer(['invoices.fields'], function ($view) {
            $agentItems = Agent::pluck('name','id')->toArray();
            $view->with('agentItems', $agentItems);
        });
        View::composer(['invoices.fields'], function ($view) {
            $kelindanItems = Kelindan::pluck('name','id')->toArray();
            $view->with('kelindanItems', $kelindanItems);
        });
        View::composer(['invoices.fields'], function ($view) {
            $driverItems = Driver::orderBy("name")->pluck('name','id')->toArray();
            $view->with('driverItems', $driverItems);
        });
        View::composer(['invoices.fields'], function ($view) {
            $customerItems = Customer::orderBy("company")->pluck('company','id')->toArray();
            $view->with('customerItems', $customerItems);
        });
        View::composer(['assigns.fields','drivers.assign'], function ($view) {
            $customerItems = Customer::where('status',1)->orderBy("company")->pluck('company','id')->toArray();
            $view->with('customerItems', $customerItems);
        });
        View::composer(['assigns.fields','drivers.assign','assigns.massfields'], function ($view) {
            $driverItems = Driver::orderBy("name")->pluck('name','id')->toArray();
            $view->with('driverItems', $driverItems);
        });
        View::composer(['assigns.massfields'], function ($view) {
            $groups = Code::where('code','customer_group')->pluck('description','value')->toArray();
            $view->with('groups', $groups);
        });
        View::composer(['focs.fields'], function ($view) {
            $productItems = Product::pluck('name','id')->toArray();
            $view->with('productItems', $productItems);
        });
        View::composer(['focs.fields'], function ($view) {
            $customerItems = Customer::orderBy("company")->pluck('company','id')->toArray();
            $view->with('customerItems', $customerItems);
        });
        View::composer(['focs.fields'], function ($view) {
            $productItems = Product::pluck('name','id')->toArray();
            $view->with('productItems', $productItems);
        });
        View::composer(['special_prices.fields'], function ($view) {
            $customerItems = Customer::orderBy("company")->pluck('company','id')->toArray();
            $view->with('customerItems', $customerItems);
        });
        View::composer(['special_prices.fields'], function ($view) {
            $productItems = Product::pluck('name','id')->toArray();
            $view->with('productItems', $productItems);
        });
        View::composer(['customers.fields'], function ($view) {
            $supervisorItems = Supervisor::pluck('name','id')->toArray();
            $view->with('supervisorItems', $supervisorItems);
        });
        View::composer(['customers.fields'], function ($view) {
            $agentItems = Agent::orderBy("name")->pluck('name','id')->toArray();
            $view->with('agentItems', $agentItems);
        });
        View::composer(['customers.fields'], function ($view) {
            $groups = Code::where('code','customer_group')->pluck('description','value')->toArray();
            $view->with('groups', $groups);
        });
        View::composer(['customers.fields'], function ($view) {
            $countryOptions = \App\Models\CountryCode::orderBy('country')->get()->mapWithKeys(function ($country) {
                return [$country->code => $country->code . ' - ' . $country->country];
            })->toArray();
            $view->with('countryOptions', $countryOptions);
            
            $stateOptions = \App\Models\StateCode::orderBy('state')->get()->mapWithKeys(function ($state) {
                return [$state->code => $state->code . ' - ' . $state->state];
            })->toArray();
            $view->with('stateOptions', $stateOptions);
        });
        View::composer(['companies.fields'], function ($view) {
            $groups = Code::where('code','customer_group')->pluck('description','value')->toArray();
            $view->with('groups', $groups);
        });
        View::composer(['drivers.fields'], function ($view) {
            $lorryItems = Lorry::orderBy("lorryno")->pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });

        View::composer(['servicedetails.fields'], function ($view) {
            $lorryItems = Lorry::orderBy("lorryno")->pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });

        // View::composer(['home'], function ($view) {
        //     $groupingItems = Driver::where('grouping','<>',NULL)->pluck('grouping','grouping')->toArray();
        //     $view->with('groupingItems', $groupingItems);
        // });
        // View::composer(['home'], function ($view) {
        //     $groupingIDItems = Driver::pluck('grouping')->toArray();
        //     $view->with('groupingIDItems', $groupingIDItems);
        // });
        // View::composer(['home'], function ($view) {
        //     $captionItems = Driver::where('caption','<>',NULL)->pluck('caption','caption')->toArray();
        //     $view->with('captionItems', $captionItems);
        // });
        // View::composer(['home'], function ($view) {
        //     $captionIDItems = Driver::pluck('caption')->toArray();
        //     $view->with('captionIDItems', $captionIDItems);
        // });
        // View::composer(['home'], function ($view) {
        //     $itemItems = Item::pluck('name','id')->toArray();
        //     $view->with('itemItems', $itemItems);
        // });
        // View::composer(['home'], function ($view) {
        //     $itemIDItems = Item::pluck('id')->toArray();
        //     $view->with('itemIDItems', $itemIDItems);
        // });
        // View::composer(['home'], function ($view) {
        //     $vendorItems = Vendor::pluck('name','id')->toArray();
        //     $view->with('vendorItems', $vendorItems);
        // });
        // View::composer(['home'], function ($view) {
        //     $vendorIDItems = Vendor::pluck('id')->toArray();
        //     $view->with('vendorIDItems', $vendorIDItems);
        // });
        View::composer(['home'], function ($view) {
            $driverItems = Driver::orderBy("name")->pluck('name','id')->toArray();
            $view->with('driverItems', $driverItems);
        });
        View::composer(['home'], function ($view) {
            $driverIDItems = Driver::pluck('id')->toArray();
            $view->with('driverIDItems', $driverIDItems);
        });
        View::composer(['home'], function ($view) {
            $customerItems = Customer::orderBy("company")->pluck('company','id')->toArray();
            $view->with('customerItems', $customerItems);
        });
        View::composer(['home'], function ($view) {
            $customerIDItems = Customer::pluck('id')->toArray();
            $view->with('customerIDItems', $customerIDItems);
        });

        View::composer(['paymentdetails.index'], function ($view) {
            $driverItems = Driver::where('status',1)->orderBy("name")->pluck('name','id')->toArray();
            $view->with('driverItems', $driverItems);
        });

        View::composer(['commission_by_vendors.fields'], function ($view) {
            $vendorItems = Vendor::where('status',1)->pluck('code','id')->toArray();
            $view->with('vendorItems', $vendorItems);
        });
        View::composer(['commission_by_vendors.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });

        View::composer(['reportdetails.fields'], function ($view) {
            $reportItems = Report::where('status',1)->pluck('name','id')->toArray();
            $view->with('reportItems', $reportItems);
        });

        View::composer(['delivery_orders.fields'], function ($view) {
            $priceItems = Price::where('status',1)->select('vendor_id','item_id','source_id','destinate_id','minrange','maxrange','billingrate')->get();
            $view->with('priceItems', $priceItems);
        });
        View::composer(['delivery_orders.fields'], function ($view) {
            $itemItems = Item::where('status',1)->pluck('code','id')->toArray();
            $view->with('itemItems', $itemItems);
        });
        View::composer(['delivery_orders.fields'], function ($view) {
            $sourceItems = Location::where('source',1)->where('status',1)->pluck('code','id')->toArray();
            $view->with('sourceItems', $sourceItems);
        });
        View::composer(['delivery_orders.fields'], function ($view) {
            $destinateItems = Location::where('destination',1)->where('status',1)->pluck('code','id')->toArray();
            $view->with('destinateItems', $destinateItems);
        });
        View::composer(['delivery_orders.fields'], function ($view) {
            $vendorItems = Vendor::where('status',1)->pluck('code','id')->toArray();
            $view->with('vendorItems', $vendorItems);
        });
        View::composer(['delivery_orders.fields'], function ($view) {
            $driverItems = Driver::where('status',1)->pluck('name','id')->toArray();
            $view->with('driverItems', $driverItems);
        });
        View::composer(['delivery_orders.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });

        View::composer(['loanpayments.fields'], function ($view) {
            $loanItems = Loan::leftjoin('drivers','loans.driver_id','drivers.id')->select(DB::raw("CONCAT(drivers.name,' (',loans.description,')') AS name"),'loans.id')->pluck('name','id')->toArray();
            // $loanItems = Loan::leftjoin('drivers','loans.driver_id','drivers.id')->where('loans.status','<>','9')->select(DB::raw("CONCAT(drivers.name,' (',loans.description,')') AS name"),'loans.id')->pluck('name','id')->toArray();
            $view->with('loanItems', $loanItems);
        });

        View::composer(['loans.fields'], function ($view) {
            $driverItems = Driver::where('status',1)->pluck('name','id')->toArray();
            $view->with('driverItems', $driverItems);
        });

        View::composer(['compounds.fields'], function ($view) {
            $driverItems = Driver::where('status',1)->pluck('name','id')->toArray();
            $view->with('driverItems', $driverItems);
        });
        View::composer(['compounds.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });

        View::composer(['advances.fields'], function ($view) {
            $driverItems = Driver::where('status',1)->pluck('name','id')->toArray();
            $view->with('driverItems', $driverItems);
        });
        View::composer(['advances.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });

        View::composer(['claims.fields'], function ($view) {
            $DoItems = DeliveryOrder::where('status',1)->pluck('dono','id')->toArray();
            $view->with('DoItems', $DoItems);
        });
        View::composer(['claims.fields'], function ($view) {
            $driverItems = Driver::where('status',1)->pluck('name','id')->toArray();
            $view->with('driverItems', $driverItems);
        });
        View::composer(['claims.fields'], function ($view) {
            $lorryItems = Lorry::pluck('lorryno','id')->toArray();
            $view->with('lorryItems', $lorryItems);
        });

        View::composer(['bonuses.fields'], function ($view) {
            $sourceItems = Location::where('source',1)->where('status',1)->pluck('code','id')->toArray();
            $view->with('sourceItems', $sourceItems);
        });
        View::composer(['bonuses.fields'], function ($view) {
            $destinateItems = Location::where('destination',1)->where('status',1)->pluck('code','id')->toArray();
            $view->with('destinateItems', $destinateItems);
        });
        View::composer(['bonuses.fields'], function ($view) {
            $vendorItems = Vendor::where('status',1)->pluck('code','id')->toArray();
            $view->with('vendorItems', $vendorItems);
        });

        View::composer(['prices.fields'], function ($view) {
            $sourceItems = Location::where('source',1)->where('status',1)->pluck('code','id')->toArray();
            $view->with('sourceItems', $sourceItems);
        });
        View::composer(['prices.fields'], function ($view) {
            $destinateItems = Location::where('destination',1)->where('status',1)->pluck('code','id')->toArray();
            $view->with('destinateItems', $destinateItems);
        });
        View::composer(['prices.fields'], function ($view) {
            $vendorItems = Vendor::where('status',1)->pluck('code','id')->toArray();
            $view->with('vendorItems', $vendorItems);
        });
        View::composer(['prices.fields'], function ($view) {
            $itemItems = Item::where('status',1)->pluck('code','id')->toArray();
            $view->with('itemItems', $itemItems);
        });

        View::composer(['lorries.fields'], function ($view) {
            $lorry_commissionpercentage = Code::where('code','lorry_commissionpercentage')->orderBy('sequence','ASC')->select('value')->get()->first()['value'] ?? [];
            $view->with('lorry_commissionpercentage', $lorry_commissionpercentage);
        });

        View::composer(['user_has_roles.fields'], function ($view) {
            $userItems = User::pluck('name','id')->toArray();
            $view->with('userItems', $userItems);
        });
        View::composer(['users.fields'], function ($view) {
            $roleItems = Role::pluck('name','id')->toArray();
            $view->with('roleItems', $roleItems);
        });

        View::composer(['role_has_permissions.fields'], function ($view) {
            $roleItems = Role::pluck('name','id')->toArray();
            $view->with('roleItems', $roleItems);
        });
        View::composer(['roles.fields'], function ($view) {
            $permissionItems = Permission::pluck('name','id')->toArray();
            $view->with('permissionItems', $permissionItems);
        });
        //
    }
}
