<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;

class Username
{
    public static function normalize(string $value): string
    {
        return Str::lower(trim($value));
    }

    public function suggestion(string $first, string $last): string
    {
        $parts = array_filter(array_map(fn ($name) => preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($name))), [$first, $last]));
        $parts = array_values($parts);
        $base = count($parts) === 2 ? substr($parts[0], 0, 20).'.'.substr($parts[1], 0, 19) : ($parts[0] ?? 'user.account');
        $base = str_pad($base, 5, '0');
        for ($suffix = 1; ; $suffix++) {
            $ending = $suffix === 1 ? '' : (string) $suffix;
            $candidate = rtrim(substr($base, 0, 40 - strlen($ending)), '.').$ending;
            if (! User::query()->whereRaw('lower(username) = ?', [$candidate])->exists()) {
                return $candidate;
            }
        }
    }
}
