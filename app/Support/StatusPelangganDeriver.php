<?php

namespace App\Support;

final class StatusPelangganDeriver
{
    public const ACTIVE = 'ACTIVE';

    public const SUSPENDED = 'SUSPENDED';

    public const DISMANTLE = 'DISMANTLE';

    public const UNKNOWN = 'UNKNOWN';

    /**
     * Case-insensitive untuk nilai `status_langganan` (mis. On/off).
     */
    public static function derive(?string $status, ?string $statusLangganan): string
    {
        $statusNorm = $status === null ? '' : trim((string) $status);
        $subNorm = $statusLangganan === null ? '' : strtolower(trim((string) $statusLangganan));

        if ($statusNorm === '1' && $subNorm === 'on') {
            return self::ACTIVE;
        }

        if ($statusNorm === '1' && $subNorm === 'off') {
            return self::SUSPENDED;
        }

        if ($statusNorm === '0' && $subNorm === 'off') {
            return self::DISMANTLE;
        }

        return self::UNKNOWN;
    }
}
