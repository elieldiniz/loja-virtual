<?php

use App\Models\Customer;
use App\Models\StoreSetting;
use App\Services\CustomerRegistrar;
use Illuminate\Validation\ValidationException;

it('creates a customer with only the name and defaults the city to the store city', function () {
    StoreSetting::current()->update(['city' => 'Ji-Paraná']);

    $customer = (new CustomerRegistrar)->save(['name' => 'Ana Paula']);

    expect($customer->whatsapp)->toBeNull()->and($customer->city)->toBe('Ji-Paraná')->and($customer->birth_day)->toBeNull();
});

it('normalizes the whatsapp and parses the birthday', function () {
    $customer = (new CustomerRegistrar)->save(['name' => 'Bruna', 'whatsapp' => '(69) 99934-1202', 'birthday' => '28/07', 'city' => 'Presidente Médici']);

    expect($customer->whatsapp)->toBe('69999341202')
        ->and($customer->birth_day)->toBe(28)->and($customer->birth_month)->toBe(7)
        ->and($customer->city)->toBe('Presidente Médici')
        ->and($customer->whatsappFormatted())->toBe('(69) 99934-1202')
        ->and($customer->birthdayLabel())->toBe('28/07');
});

it('rejects invalid input', function (array $input, string $field) {
    try {
        (new CustomerRegistrar)->save($input);
        $this->fail('Should have thrown');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey($field);
    }

    expect(Customer::count())->toBe(0);
})->with([
    'no name' => [['name' => ''], 'name'],
    'short phone' => [['name' => 'Ana', 'whatsapp' => '999990000'], 'whatsapp'],
    'bad birthday' => [['name' => 'Ana', 'birthday' => '31/02'], 'birthday'],
    'birthday text' => [['name' => 'Ana', 'birthday' => 'junho'], 'birthday'],
]);

it('rejects a whatsapp already used by another customer but allows the same customer', function () {
    $first = (new CustomerRegistrar)->save(['name' => 'Ana', 'whatsapp' => '69999990000']);

    expect(fn () => (new CustomerRegistrar)->save(['name' => 'Outra', 'whatsapp' => '(69) 99999-0000']))->toThrow(ValidationException::class);

    $updated = (new CustomerRegistrar)->save(['name' => 'Ana Souza', 'whatsapp' => '(69) 99999-0000'], $first);

    expect($updated->name)->toBe('Ana Souza')->and(Customer::count())->toBe(1);
});

it('accepts 29/02 as a birthday', function () {
    $customer = (new CustomerRegistrar)->save(['name' => 'Bissexta', 'birthday' => '29/02']);

    expect($customer->birth_day)->toBe(29)->and($customer->birth_month)->toBe(2);
});
