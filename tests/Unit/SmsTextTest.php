<?php

namespace Tests\Unit;

use App\Services\Sms\SmsText;
use PHPUnit\Framework\TestCase;

class SmsTextTest extends TestCase
{
    public function test_gsm_and_unicode_multipart_limits(): void
    {
        $this->assertSame(['characters' => 160, 'encoding' => 'GSM-7', 'segments' => 1], SmsText::details(str_repeat('A', 160)));
        $this->assertSame(2, SmsText::details(str_repeat('A', 161))['segments']);
        $this->assertSame(2, SmsText::details(str_repeat('^', 81))['segments']);
        $this->assertSame(2, SmsText::details(str_repeat('你好', 36))['segments']);
    }

    public function test_mobile_normalization_rejects_invalid_numbers(): void
    {
        $this->assertSame('+639171234567', SmsText::mobile('0917 123 4567'));
        $this->assertSame('+639171234567', SmsText::mobile('639171234567'));
        $this->assertNull(SmsText::mobile('12345'));
    }
}
