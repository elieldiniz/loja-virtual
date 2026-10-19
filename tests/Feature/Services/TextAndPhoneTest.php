<?php

use App\Support\Phone;
use App\Support\SearchText;

it('normalizes search text', function () {
    expect(SearchText::normalize('  Anéis  Dourados '))->toBe('aneis dourados')
        ->and(SearchText::normalize('MARIANA'))->toBe('mariana')
        ->and(SearchText::normalize(null))->toBe('');
});

it('normalizes and validates phones', function () {
    expect(Phone::normalize('(69) 99999-0000'))->toBe('69999990000')
        ->and(Phone::isValid('(69) 99999-0000'))->toBeTrue()
        ->and(Phone::isValid('999990000'))->toBeFalse()
        ->and(Phone::isValid(null))->toBeFalse()
        ->and(Phone::forWhatsApp('(69) 99999-0000'))->toBe('5569999990000')
        ->and(Phone::forWhatsApp('5569999990000'))->toBe('5569999990000');
});
