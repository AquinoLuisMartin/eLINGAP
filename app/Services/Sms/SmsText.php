<?php

namespace App\Services\Sms;

class SmsText
{
    public static function details(string $message): array
    {
        $basic = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
        $extended = '^{}\\[~]|€';
        $encoding = 'GSM-7';
        $length = 0;

        foreach (mb_str_split($message) as $character) {
            if (mb_strpos($basic, $character) !== false) {
                $length++;
            } elseif (mb_strpos($extended, $character) !== false) {
                $length += 2;
            } else {
                $encoding = 'Unicode';
                break;
            }
        }

        if ($encoding === 'Unicode') {
            $length = strlen(mb_convert_encoding($message, 'UTF-16LE', 'UTF-8')) / 2;
        }

        $single = $encoding === 'GSM-7' ? 160 : 70;
        $multipart = $encoding === 'GSM-7' ? 153 : 67;

        return ['characters' => mb_strlen($message), 'encoding' => $encoding, 'segments' => $length === 0 ? 0 : ($length <= $single ? 1 : (int) ceil($length / $multipart))];
    }

    public static function mobile(?string $number): ?string
    {
        $digits = preg_replace('/[\s()-]/', '', $number ?? '');
        if (preg_match('/^09\d{9}$/', $digits)) {
            return '+63'.substr($digits, 1);
        }
        if (preg_match('/^63[9]\d{9}$/', $digits)) {
            return '+'.$digits;
        }

        return preg_match('/^\+639\d{9}$/', $digits) ? $digits : null;
    }
}
