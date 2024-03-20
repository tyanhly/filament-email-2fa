<?php

namespace Solutionforest\FilamentEmail2fa\Trait;

use Illuminate\Support\Facades\Mail;
use Solutionforest\FilamentEmail2fa\Exceptions\InvalidTwoFACodeException;
use Solutionforest\FilamentEmail2fa\Mail\TwoFAEmail;

trait HasTwoFALogin
{
    public const enable2FALogin = true;

    public function send2FAEmail()
    {
        Mail::to(trim($this->email))
            ->send(new TwoFAEmail($this->name, $this->generate2FACode()));
    }

    public function twoFaCodes()
    {
        return $this->morphMany(config('filament-email-2fa.model'), 'user');
    }

    public function latest_2fa_code()
    {
        return $this->morphOne(config('filament-email-2fa.model'), 'user') > where('expiry_at', '>=', now())->ofMany('expiry_at', 'max');
    }

    public function generate2FACode()
    {
        $this->twoFaCodes()->delete();
        $code = sprintf('%06d', mt_rand(1, 999999));
        $this->twoFaCodes()->create([
            'code' => $code,
            'expiry_at' => now()->addMinutes((int) config('filament-email-2fa.expiry_time_by_mins', 10)),
        ]);

        return $code;

    }

    public function verify2FACode(string $code)
    {
        $latestCode = $this->latest_2fa_code?->code;
        if ($code !== null && $code === $latestCode) {
            return;
        }

        throw new InvalidTwoFACodeException;
    }
}
