<?php

namespace App\Actions\Auth;

use App\Models\Legacy\Warga;
use App\Models\WargaAccount;
use App\Support\LegacyAccount;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Laravel\Sanctum\NewAccessToken;

final class AuthenticateWarga
{
    /**
     * @return array{token: NewAccessToken, warga: Warga, wargaAccount: WargaAccount}
     *
     * @throws AuthenticationException
     * @throws InvalidArgumentException
     * @throws ValidationException
     */
    public function __invoke(string $account, string $username, string $password): array
    {
        $account = LegacyAccount::normalize($account);

        if (! LegacyAccount::tableExists($account)) {
            throw ValidationException::withMessages([
                'account' => [__('Tabel akun tidak ditemukan.')],
            ]);
        }

        $warga = Warga::forAccount($account)
            ->where('username', $username)
            ->first();

        if ($warga === null) {
            throw new AuthenticationException(__('Kredensial tidak valid.'));
        }

        if (! hash_equals((string) $warga->password, $password)) {
            throw new AuthenticationException(__('Kredensial tidak valid.'));
        }

        if (Str::lower((string) $warga->status) !== 'aktif') {
            throw new AuthenticationException(__('Akun warga tidak aktif.'));
        }

        $wargaAccount = WargaAccount::query()->updateOrCreate(
            [
                'account' => $account,
                'id_warga_legacy' => (int) $warga->id_warga,
            ],
            [
                'username' => $username,
                'last_login_at' => now(),
            ],
        );

        $token = $wargaAccount->createToken('api', ['account:'.$account]);

        return [
            'token' => $token,
            'warga' => $warga,
            'wargaAccount' => $wargaAccount,
        ];
    }
}
