<?php

namespace App\Http\Controllers;

use App\Models\DokuVaChannel;
use App\Services\Payments\Doku\DokuException;
use App\Services\Payments\Doku\DokuSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DokuSettingsController extends Controller
{
    public function __construct(private readonly DokuSettingsService $settings) {}

    public function index()
    {
        Gate::authorize('superuser-only');
        $setting = $this->settings->current();
        $keyStatus = $this->settings->keyStatus();
        $channels = DokuVaChannel::forDoku()->orderByDesc('is_default')->orderBy('name')->get();

        return view('settings.doku', compact('setting', 'keyStatus', 'channels'));
    }

    public function update(Request $request)
    {
        Gate::authorize('superuser-only');

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'environment' => ['required', 'in:sandbox,production'],
            'base_url' => ['required', 'url', 'max:255'],
            'client_id' => ['nullable', 'string', 'max:128'],
            'secret_key' => ['nullable', 'string', 'max:1000'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'merchant_id' => ['nullable', 'string', 'max:128'],
            'terminal_id' => ['nullable', 'string', 'max:128'],
            'channel_id' => ['required', 'string', 'max:64'],
            'va_bank' => ['nullable', 'string', 'max:32'],
            'va_partner_service_id' => ['nullable', 'string', 'max:64'],
            'va_customer_prefix' => ['nullable', 'string', 'max:16'],
            'notification_path' => ['required', 'string', 'max:255'],
            'timeout' => ['required', 'integer', 'min:5', 'max:120'],
            'private_key_path' => ['required', 'string', 'max:255'],
            'private_key_passphrase' => ['nullable', 'string', 'max:1000'],
            'public_key_path' => ['required', 'string', 'max:255'],
        ]);

        $data['enabled'] = $request->boolean('enabled');
        $data['private_key_path'] = trim($data['private_key_path']);
        $data['public_key_path'] = trim($data['public_key_path']);

        $this->settings->save($data);

        return back()->with('success', 'Pengaturan DOKU berhasil disimpan.');
    }

    public function test()
    {
        Gate::authorize('superuser-only');

        try {
            $this->settings->applyToConfig();
            app(\App\Services\Payments\Doku\DokuClient::class)->accessToken();
        } catch (DokuException $e) {
            report($e);
            return back()->with('error', 'Koneksi DOKU gagal: ' . $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Koneksi DOKU gagal. Periksa konfigurasi dan log aplikasi.');
        }

        return back()->with('success', 'Koneksi DOKU berhasil. Access token berhasil diperoleh.');
    }

    public function storeVaChannel(Request $request)
    {
        Gate::authorize('superuser-only');

        $catalog = config('doku.va_banks', []);
        $data = $request->validate([
            'bank' => ['required', 'string', 'max:32'],
            'merchant_bin' => ['nullable', 'regex:/^\d{1,8}$/'],
            'partner_service_id' => ['nullable', 'regex:/^\d{1,8}$/'],
            'customer_prefix' => ['nullable', 'regex:/^\d{0,20}$/'],
            'custom_code' => ['nullable', 'alpha_dash', 'max:32'],
            'custom_name' => ['nullable', 'string', 'max:80'],
            'custom_channel' => ['nullable', 'string', 'max:64'],
            'enabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $bank = strtoupper(trim($data['bank']));
        if ($bank === 'CUSTOM') {
            $code = strtoupper(trim((string) ($data['custom_code'] ?? '')));
            $name = trim((string) ($data['custom_name'] ?? ''));
            $channelName = strtoupper(trim((string) ($data['custom_channel'] ?? '')));
            if ($code === '' || $name === '' || $channelName === '') {
                return back()->withInput()->with('error', 'Untuk bank custom, kode, nama bank, dan channel DOKU wajib diisi.');
            }
        } else {
            if (! array_key_exists($bank, $catalog)) {
                return back()->withInput()->with('error', 'Bank DOKU tidak tersedia di katalog. Pilih Bank Lain/Custom jika diperlukan.');
            }
            $code = $bank;
            $name = $catalog[$bank]['name'];
            $channelName = $catalog[$bank]['channel'];
        }

        $merchantBin = trim((string) ($data['merchant_bin'] ?? ''));
        $partnerServiceId = trim((string) ($data['partner_service_id'] ?? ''));
        $customerPrefix = trim((string) ($data['customer_prefix'] ?? ''));
        if ($partnerServiceId === '') {
            $partnerServiceId = trim((string) $this->settings->current()->va_partner_service_id);
        }
        if ($customerPrefix === '') {
            $customerPrefix = trim((string) $this->settings->current()->va_customer_prefix);
        }
        if ($merchantBin !== '' && ! preg_match('/^\d{1,8}$/', $merchantBin)) {
            return back()->withInput()->with('error', 'Merchant BIN DOKU harus 1-8 digit.');
        }
        if ($partnerServiceId !== '' && ! preg_match('/^\d{1,8}$/', $partnerServiceId)) {
            return back()->withInput()->with('error', 'Partner Service ID DOKU harus 1-8 digit.');
        }
        if ($customerPrefix !== '' && ! preg_match('/^\d{0,20}$/', $customerPrefix)) {
            return back()->withInput()->with('error', 'Prefix Customer No harus berupa angka.');
        }

        if (DokuVaChannel::forDoku()->where('code', $code)->exists()) {
            return back()->withInput()->with('error', 'Bank tersebut sudah ditambahkan. Gunakan Edit pada bank yang sudah ada.');
        }

        $isDefault = $request->boolean('is_default') || ! DokuVaChannel::forDoku()->exists();
        if ($isDefault) {
            DokuVaChannel::forDoku()->update(['is_default' => false]);
        }

        DokuVaChannel::create([
            'gateway' => 'doku',
            'code' => $code,
            'name' => $name,
            'channel' => $channelName,
            'merchant_bin' => $merchantBin !== '' ? $merchantBin : null,
            'partner_service_id' => $partnerServiceId !== '' ? $partnerServiceId : null,
            'customer_prefix' => $customerPrefix !== '' ? $customerPrefix : null,
            'enabled' => $request->boolean('enabled'),
            'is_default' => $isDefault,
            'metadata' => ['source' => $bank === 'CUSTOM' ? 'custom' : 'doku-catalog'],
        ]);

        return back()->with('success', $name . ' berhasil ditambahkan.');
    }

    public function updateVaChannel(Request $request, DokuVaChannel $channel)
    {
        Gate::authorize('superuser-only');
        abort_unless($channel->gateway === 'doku', 404);

        $data = $request->validate([
            'merchant_bin' => ['nullable', 'regex:/^\d{1,8}$/'],
            'partner_service_id' => ['nullable', 'regex:/^\d{1,8}$/'],
            'customer_prefix' => ['nullable', 'regex:/^\d{0,20}$/'],
            'enabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $merchantBin = trim((string) ($data['merchant_bin'] ?? ''));
        $partnerServiceId = trim((string) ($data['partner_service_id'] ?? ''));
        $customerPrefix = trim((string) ($data['customer_prefix'] ?? ''));
        if ($partnerServiceId === '') {
            $partnerServiceId = trim((string) $this->settings->current()->va_partner_service_id);
        }
        if ($customerPrefix === '') {
            $customerPrefix = trim((string) $this->settings->current()->va_customer_prefix);
        }
        if ($merchantBin !== '' && ! preg_match('/^\d{1,8}$/', $merchantBin)) {
            return back()->withInput()->with('error', 'Merchant BIN DOKU harus 1-8 digit.');
        }
        if ($partnerServiceId !== '' && ! preg_match('/^\d{1,8}$/', $partnerServiceId)) {
            return back()->withInput()->with('error', 'Partner Service ID DOKU harus 1-8 digit.');
        }
        if ($customerPrefix !== '' && ! preg_match('/^\d{0,20}$/', $customerPrefix)) {
            return back()->withInput()->with('error', 'Prefix Customer No harus berupa angka.');
        }

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            DokuVaChannel::forDoku()->where('id', '<>', $channel->id)->update(['is_default' => false]);
        }

        $channel->update([
            'merchant_bin' => $merchantBin !== '' ? $merchantBin : null,
            'partner_service_id' => $partnerServiceId !== '' ? $partnerServiceId : null,
            'customer_prefix' => $customerPrefix !== '' ? $customerPrefix : null,
            'enabled' => $request->boolean('enabled'),
            'is_default' => $isDefault,
        ]);

        return back()->with('success', $channel->name . ' berhasil diperbarui.');
    }

    public function destroyVaChannel(DokuVaChannel $channel)
    {
        Gate::authorize('superuser-only');
        abort_unless($channel->gateway === 'doku', 404);

        if ($channel->paymentAccounts()->exists()) {
            return back()->with('error', 'Bank tidak dapat dihapus karena sudah dipakai member. Nonaktifkan jika tidak ingin dipakai lagi.');
        }

        $channel->delete();
        return back()->with('success', 'Bank VA dihapus.');
    }

    public function publicKey()
    {
        Gate::authorize('superuser-only');

        $path = $this->settings->current()->public_key_path;
        if (! $path || ! is_readable($path)) {
            abort(404);
        }

        return response(file_get_contents($path), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
