<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('doku_settings')) {
            return;
        }

        foreach (DB::table('doku_settings')->select('id', 'secret_key', 'api_key', 'private_key_passphrase')->cursor() as $setting) {
            $updates = [];

            foreach (['secret_key', 'api_key', 'private_key_passphrase'] as $column) {
                $value = $setting->{$column};
                if ($value === null || $value === '') {
                    continue;
                }

                try {
                    // Legacy seed used encrypt(), which serializes strings before encryption.
                    // The encrypted cast uses decryptString(), so normalize the stored payload
                    // to encryptString() once and retain encryption at rest.
                    $plaintext = Crypt::decrypt($value);
                } catch (\Throwable) {
                    try {
                        $plaintext = Crypt::decryptString($value);
                    } catch (\Throwable) {
                        continue;
                    }
                }

                if (! is_string($plaintext)) {
                    continue;
                }

                $updates[$column] = Crypt::encryptString($plaintext);
            }

            if ($updates !== []) {
                $updates['updated_at'] = now();
                DB::table('doku_settings')->where('id', $setting->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Do not reintroduce legacy serialized encryption payloads.
    }
};
