<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webfox\Xero\OauthCredentialManager;

class XeroController extends Controller
{
    public const CACHE_KEY_ACCESS_TOKEN = 'xero_access_token';
    public const CACHE_KEY_REFRESH_TOKEN = 'xero_refresh_token';
    public const MAX_ATTEMPT = 2;

    protected $redirect_uri;

    public function __construct($redirect_uri) {
        $this->redirect_uri = $redirect_uri;
    }

    public function auth()
    {
        if (!Cache::has(self::CACHE_KEY_ACCESS_TOKEN) || !Cache::has(self::CACHE_KEY_REFRESH_TOKEN)) {
            $url = 'https://login.xero.com/identity/connect/authorize?response_type=code&client_id='.config('xero.client_id').'&redirect_uri='.$this->redirect_uri.'&scope=offline_access openid profile email accounting.contacts accounting.settings';

            return redirect($url);
        }
        return true;
    }
    
    public function getAccount(string $code): Response
    {
        $url = 'https://api.xero.com/api.xro/2.0/Accounts';
        $attempt = 0;

        while (true) {
            $attempt++;

            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . Cache::get(self::CACHE_KEY_ACCESS_TOKEN),
                'Xero-Tenant-Id' => config('xero.tenant_id'),
                'Accept' => 'application/json'
            ])->get($url, [
                'where' => 'Code="'.$code.'"'
            ]);

            $payload = $res->object();
            if (isset($payload->Detail) && str_contains($payload->Detail, 'TokenExpired')) {
                $this->refreshToken();
            } elseif (!$res->ok()) {
                Log::warning('getAccount');
                Log::warning($res->body());
            }

            if ($res->ok() || $attempt >= self::MAX_ATTEMPT) {
                break;
            }
        }

        return $res;
    }
    
    public function createAccount(string $code, string $name)
    {
        $url = 'https://api.xero.com/api.xro/2.0/Accounts';
        $attempt = 0;

        while (true) {
            $attempt++;

            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . Cache::get(self::CACHE_KEY_ACCESS_TOKEN),
                'Xero-Tenant-Id' => config('xero.tenant_id'),
            ])->put($url, [
                'Code' => $code,
                'Name' => $name,
                'Type' => 'INVENTORY',
            ]);
            
            $payload = $res->object();
            if (isset($payload->Detail) && str_contains($payload->Detail, 'TokenExpired')) {
                $this->refreshToken();
            } elseif (!$res->ok()) {
                Log::warning('createAccount');
                Log::warning($res->body());
            }

            if ($res->ok() || $attempt >= self::MAX_ATTEMPT) {
                break;
            }
        }

        return $res;
    }

    public function createItem(string $code, string $name, float $price)
    {
        $url = 'https://api.xero.com/api.xro/2.0/Items';
        $attempt = 0;

        while (true) {
            $attempt++;

            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . Cache::get(self::CACHE_KEY_ACCESS_TOKEN),
                'Xero-Tenant-Id' => config('xero.tenant_id'),
            ])->post($url, [
                'Code' => $code,
                'Name' => $name,
                'SalesDetails' => [
                    'UnitPrice' => $price
                ]
            ]);
            
            $payload = $res->object();
            if (isset($payload->Detail) && str_contains($payload->Detail, 'TokenExpired')) {
                $this->refreshToken();
            } elseif (!$res->ok()) {
                Log::warning('createItem');
                Log::warning($res->body());
            }

            if ($res->ok() || $attempt >= self::MAX_ATTEMPT) {
                break;
            }
        }

        return $res;
    }

    public function createCreditNote(bool $is_pay, $contact, array $items, $note_id)
    {
        $url = 'https://api.xero.com/api.xro/2.0/CreditNotes';
        $attempt = 0;

        while (true) {
            $attempt++;

            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . Cache::get(self::CACHE_KEY_ACCESS_TOKEN),
                'Xero-Tenant-Id' => config('xero.tenant_id'),
            ])->post($url, [
                'Type' => $is_pay ? 'ACCPAYCREDIT' : 'ACCRECCREDIT',
                'Date' => now(),
                'CurrencyCode' => 'MYR',
                'Contact' => $contact,
                'LineItems' => [$items],
                'CreditNoteNumber' => $note_id
            ]);
            
            $payload = $res->object();
            if (isset($payload->Detail) && str_contains($payload->Detail, 'TokenExpired')) {
                $this->refreshToken();
            } elseif (!$res->ok()) {
                Log::warning('createCreditNote');
                Log::warning($res->body());
            }

            if ($res->ok() || $attempt >= self::MAX_ATTEMPT) {
                break;
            }
        }

        return $res;
    }

    public function getContact(string $contact_name): Response
    {
        $url = 'https://api.xero.com/api.xro/2.0/Contacts';
        $attempt = 0;

        while (true) {
            $attempt++;

            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . Cache::get(self::CACHE_KEY_ACCESS_TOKEN),
                'Xero-Tenant-Id' => config('xero.tenant_id'),
                'Accept' => 'application/json'
            ])->get($url, [
                'where' => 'Name="'.$contact_name.'"'
            ]);

            $payload = $res->object();
            if (isset($payload->Detail) && str_contains($payload->Detail, 'TokenExpired')) {
                $this->refreshToken();
            } elseif (!$res->ok()) {
                Log::warning('getContact');
                Log::warning($res->body());
            }

            if ($res->ok() || $attempt >= self::MAX_ATTEMPT) {
                break;
            }
        }

        return $res;
    }

    public function createContact($name)
    {
        $url = 'https://api.xero.com/api.xro/2.0/Contacts';
        $attempt = 0;

        while (true) {
            $attempt++;

            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . Cache::get(self::CACHE_KEY_ACCESS_TOKEN),
                'Xero-Tenant-Id' => config('xero.tenant_id'),
            ])->post($url, [
                'Name' => $name,
            ]);

            $payload = $res->object();
            if (isset($payload->Detail) && str_contains($payload->Detail, 'TokenExpired')) {
                $this->refreshToken();
            } elseif (!$res->ok()) {
                Log::warning('createContact');
                Log::warning($res->body());
            }

            if ($res->ok() || $attempt >= self::MAX_ATTEMPT) {
                break;
            }
        }

        return $res;
    }

    public function getTrackingCaterogies(): Response
    {
        $url = 'https://api.xero.com/api.xro/2.0/TrackingCategories';
        $attempt = 0;

        while (true) {
            $attempt++;

            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . Cache::get(self::CACHE_KEY_ACCESS_TOKEN),
                'Xero-Tenant-Id' => config('xero.tenant_id'),
                'Accept' => 'application/json'
            ])->get($url);

            $payload = $res->object();
            if (isset($payload->Detail) && str_contains($payload->Detail, 'TokenExpired')) {
                $this->refreshToken();
            } elseif (!$res->ok()) {
                Log::warning('getTrackingCaterogy');
                Log::warning($res->body());
            }

            if ($res->ok() || $attempt >= self::MAX_ATTEMPT) {
                break;
            }
        }

        return $res;
    }

    public function createTrackingOption(string $category_id, string $name): Response
    {
        $url = 'https://api.xero.com/api.xro/2.0/TrackingCategories/'.$category_id.'/Options';
        $attempt = 0;

        while (true) {
            $attempt++;

            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . Cache::get(self::CACHE_KEY_ACCESS_TOKEN),
                'Xero-Tenant-Id' => config('xero.tenant_id'),
            ])->put($url, [
                'Name' => $name,
                'Status' => 'ACTIVE',
            ]);

            $payload = $res->object();
            if (isset($payload->Detail) && str_contains($payload->Detail, 'TokenExpired')) {
                $this->refreshToken();
            } elseif (!$res->ok()) {
                Log::warning('createTrackingOption');
                Log::warning($res->body());
            }

            if ($res->ok() || $attempt >= self::MAX_ATTEMPT) {
                break;
            }
        }

        return $res;
    }

    public function createInvoice($contact, array $data)
    {
        $url = 'https://api.xero.com/api.xro/2.0/Invoices';
        $attempt = 0;

        while (true) {
            $attempt++;

            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . Cache::get(self::CACHE_KEY_ACCESS_TOKEN),
                'Xero-Tenant-Id' => config('xero.tenant_id'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'Type' => 'ACCREC',
                'Contact' => $contact,
                'LineItems' => $data['line_items'],
                'Date' => Carbon::parse($data['invoice_date'])->format('Y-m-d'),
                // 'DueDate' => Carbon::createFromFormat('d/m/Y', $data['due_date'])->format('Y-m-d'),
                // 'InvoiceNumber' => $data['invoice_number'],
                // 'Reference' => $data['reference'],
                'Status' => 'DRAFT'
            ]);

            $payload = $res->object();
            if (isset($payload->Detail) && str_contains($payload->Detail, 'TokenExpired')) {
                $this->refreshToken();
            } elseif (!$res->ok()) {
                Log::warning('createInvoice');
                Log::warning($res->body());
            }

            if ($res->ok() || $attempt >= self::MAX_ATTEMPT) {
                break;
            }
        }

        return $res;
    }

    public function getToken($code): Response
    {
        $url = 'https://identity.xero.com/connect/token';

        $res = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(config('xero.client_id') . ':' . config('xero.client_secret')),
        ])->asForm()->post($url, [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirect_uri,
            'code' => $code,
        ]);
        
        if ($res->ok()) {
            $payload = $res->object();
            $this->saveTokenIntoCache($payload->access_token, $payload->refresh_token);
        }

        return $res;
    }

    public function refreshToken()
    {
        $url = 'https://identity.xero.com/connect/token';

        $res = Http::asForm()->post($url, [
            'grant_type' => 'refresh_token',
            'refresh_token' => Cache::get(self::CACHE_KEY_REFRESH_TOKEN),
            'client_id' => config('xero.client_id'),
            'client_secret' => config('xero.client_secret'),
            'scope' => 'offline_access openid profile email accounting.contacts accounting.settings',
        ]);

        $payload = $res->object();
        if ($res->ok()) {
            $this->saveTokenIntoCache($payload->access_token, $payload->refresh_token);
            Log::info('xero token refreshed');
        }

        return $res;
    }

    private function saveTokenIntoCache(string $access_token, string $refresh_token)
    {
        Cache::put(self::CACHE_KEY_ACCESS_TOKEN, $access_token);
        Cache::put(self::CACHE_KEY_REFRESH_TOKEN, $refresh_token);
    }
}
