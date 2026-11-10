<?php

namespace App\Services;

class PixDemo
{
    public static function hasDevelopmentKey(): bool
    {
        $key = (string) config('payments.abacate_key');

        return str_starts_with($key, 'dev_') || str_starts_with($key, 'abc_dev_');
    }

    public static function available(): bool
    {
        if (! in_array(config('payments.driver'), ['local', 'abacate'], true)) {
            return false;
        }

        if (app()->environment(['local', 'testing'])) {
            return true;
        }

        return app()->environment('production')
            && config('payments.production_demo_enabled') === true
            && config('payments.driver') === 'abacate'
            && self::hasDevelopmentKey();
    }
}
