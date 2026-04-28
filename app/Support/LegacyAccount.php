<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class LegacyAccount
{
    public const TABLE_PREFIX = 'tb_warga_';

    public const IURAN_TABLE_PREFIX = 'tb_iuran_';

    /**
     * @throws InvalidArgumentException
     */
    public static function normalize(string $account): string
    {
        $account = trim($account);
        if ($account === '') {
            throw new InvalidArgumentException('Account wajib diisi.');
        }

        if (preg_match('/^[A-Za-z0-9_-]+$/', $account) !== 1) {
            throw new InvalidArgumentException('Format account tidak valid.');
        }

        return $account;
    }

    public static function wargaTableName(string $normalizedAccount): string
    {
        return self::TABLE_PREFIX.$normalizedAccount;
    }

    public static function tableExists(string $normalizedAccount): bool
    {
        $table = self::wargaTableName($normalizedAccount);

        return Schema::connection('legacy')->hasTable($table);
    }

    public static function iuranTableName(string $normalizedAccount): string
    {
        return self::IURAN_TABLE_PREFIX.$normalizedAccount;
    }

    public static function iuranTableExists(string $normalizedAccount): bool
    {
        $table = self::iuranTableName($normalizedAccount);

        return Schema::connection('legacy')->hasTable($table);
    }
}
