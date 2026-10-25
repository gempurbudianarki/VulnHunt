<?php

use App\Services\CvssCalculator;

test('kalkulator CVSS menghitung 4 vector uji wajib spesifikasi dengan benar', function () {
    // 1. Critical (9.8)
    $res1 = CvssCalculator::calculate('CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H');
    expect($res1['score'])->toBe(9.8)
        ->and($res1['severity'])->toBe('critical');

    // 2. Medium (6.1) - Scope Changed
    $res2 = CvssCalculator::calculate('CVSS:3.1/AV:N/AC:L/PR:N/UI:R/S:C/C:L/I:L/A:N');
    expect($res2['score'])->toBe(6.1)
        ->and($res2['severity'])->toBe('medium');

    // 3. Medium (6.5)
    $res3 = CvssCalculator::calculate('CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N');
    expect($res3['score'])->toBe(6.5)
        ->and($res3['severity'])->toBe('medium');

    // 4. High (7.8) - Local AV
    $res4 = CvssCalculator::calculate('CVSS:3.1/AV:L/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:H');
    expect($res4['score'])->toBe(7.8)
        ->and($res4['severity'])->toBe('high');
});

test('kasus tepi: impact <= 0 menghasilkan skor 0.0 dan severity info', function () {
    $res = CvssCalculator::calculate('CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:N/I:N/A:N');
    expect($res['score'])->toBe(0.0)
        ->and($res['severity'])->toBe('info');
});

test('fungsi roundup cvss 3.1 bekerja presisi terhadap floating point', function () {
    expect(CvssCalculator::roundup(4.0))->toBe(4.0)
        ->and(CvssCalculator::roundup(4.01))->toBe(4.1)
        ->and(CvssCalculator::roundup(4.00001))->toBe(4.1)
        ->and(CvssCalculator::roundup(4.00000))->toBe(4.0)
        ->and(CvssCalculator::roundup(9.84))->toBe(9.9);
});

test('kalkulator CVSS melempar exception jika komponen vector tidak lengkap atau tidak valid', function () {
    expect(fn () => CvssCalculator::calculate('AV:N/AC:L/PR:N'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => CvssCalculator::calculate('CVSS:3.1/AV:X/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H'))
        ->toThrow(InvalidArgumentException::class);
});
