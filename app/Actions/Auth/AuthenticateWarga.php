<?php

namespace App\Actions\Auth;

use App\Models\Legacy\Warga;
use App\Models\WargaAccount;
use App\Support\LegacyAccount;
use Illuminate\Auth\AuthenticationException;
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
            ->where('status', '1')
            ->first();

        if ($warga === null) {
            throw new AuthenticationException(__('Kredensial tidak valid.'));
        }

        if (! hash_equals((string) $warga->password, $password)) {
            throw new AuthenticationException(__('Kredensial tidak valid.'));
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

        $minutes = (int) config('sanctum.token_inactivity_ttl_minutes', 1440);
        $expiresAt = $minutes >= 1 ? now()->addMinutes($minutes) : null;
        $token = $wargaAccount->createToken('api', ['account:'.$account], $expiresAt);

        return [
            'token' => $token,
            'warga' => $warga,
            'wargaAccount' => $wargaAccount,
        ];
    }
}
