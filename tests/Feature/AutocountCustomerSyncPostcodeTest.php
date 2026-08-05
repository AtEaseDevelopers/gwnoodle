<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression test: the AutoCount customer sync (/api/customers/update) must be
 * able to INSERT a brand-new debtor even when its PostCode is empty or contains
 * non-numeric characters.
 *
 * The bug: `postcode` is an INT column, but the controller inserted the RAW
 * AutoCount PostCode string (a sanitized value was computed but never used).
 * Under MySQL strict mode a value like "81100 JB" or "" throws
 * "Incorrect integer value", and because the loop has no per-record isolation
 * (no transaction, no try/catch, BATCH_SIZE = 0) a single bad debtor 500s the
 * whole request - so no NEW customers in the batch are created, while unchanged
 * existing customers (no-op updates) appear to sync fine.
 */
class AutocountCustomerSyncPostcodeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_new_customer_with_non_numeric_postcode_is_created(): void
    {
        $accNo = 'TEST-PC-' . uniqid();

        $payload = [
            'debtors' => [
                [
                    'AccNo'       => $accNo,
                    'CompanyName' => 'TEST POSTCODE DEBTOR',
                    'PostCode'    => '81100 JB', // non-numeric -> would break INT column in strict mode
                    'IsActive'    => 'T',
                ],
            ],
        ];

        $response = $this->postJson('/api/customers/update', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('customers', [
            'code'    => $accNo,
            'company' => 'TEST POSTCODE DEBTOR',
        ]);
    }

    public function test_one_bad_debtor_does_not_abort_the_whole_batch(): void
    {
        $badAcc  = 'TEST-BAD-' . uniqid();
        $goodAcc = 'TEST-GOOD-' . uniqid();

        $payload = [
            'debtors' => [
                [
                    'AccNo'       => $badAcc,
                    'CompanyName' => 'BAD POSTCODE DEBTOR',
                    'PostCode'    => 'N/A', // non-numeric
                    'IsActive'    => 'T',
                ],
                [
                    'AccNo'       => $goodAcc,
                    'CompanyName' => 'GOOD DEBTOR',
                    'PostCode'    => '81300',
                    'IsActive'    => 'T',
                ],
            ],
        ];

        $response = $this->postJson('/api/customers/update', $payload);

        $response->assertStatus(200);

        // The valid debtor that follows a bad one must still be created.
        $this->assertDatabaseHas('customers', ['code' => $goodAcc]);
    }
}
