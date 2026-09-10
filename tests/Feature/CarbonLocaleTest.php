<?php

use Carbon\Carbon;

test('carbon uses the Indonesian locale for relative dates', function () {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    try {
        expect(Carbon::getLocale())->toBe(config('app.locale'));
        expect($now->copy()->addHours(10)->diffForHumans())->toBe('10 jam dari sekarang');
    } finally {
        Carbon::setTestNow();
    }
});
