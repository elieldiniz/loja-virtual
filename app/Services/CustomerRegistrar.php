<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\StoreSetting;
use App\Support\Phone;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Cadastro de cliente com as regras do painel (tela Clientes e cadastro rápido da venda). */
class CustomerRegistrar
{
    /**
     * @param  array{name?: ?string, whatsapp?: ?string, city?: ?string, birthday?: ?string, notes?: ?string}  $input
     *
     * @throws ValidationException
     */
    public function save(array $input, ?Customer $customer = null): Customer
    {
        $data = [
            'name' => trim((string) ($input['name'] ?? '')),
            'whatsapp' => Phone::normalize($input['whatsapp'] ?? ''),
            'city' => trim((string) ($input['city'] ?? '')),
            'birthday' => trim((string) ($input['birthday'] ?? '')),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ];

        Validator::make($data, [
            'name' => ['required', 'string', 'max:120'],
            'whatsapp' => ['nullable', 'string', function ($attribute, $value, $fail) use ($customer) {
                if ($value === '') {
                    return;
                }

                if (! Phone::isValid($value)) {
                    $fail('Informe o WhatsApp com DDD (mínimo de 10 dígitos).');

                    return;
                }

                $taken = Customer::query()
                    ->where('whatsapp', $value)
                    ->when($customer, fn ($q) => $q->where('id', '!=', $customer->id))
                    ->exists();

                if ($taken) {
                    $fail('Já existe uma cliente com esse WhatsApp.');
                }
            }],
            'city' => ['nullable', 'string', 'max:80'],
            'birthday' => ['nullable', function ($attribute, $value, $fail) {
                if ($value !== '' && self::parseBirthday($value) === null) {
                    $fail('Use o formato dia/mês, por exemplo 21/06.');
                }
            }],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Informe o nome da cliente.',
        ])->validate();

        [$day, $month] = self::parseBirthday($data['birthday']) ?? [null, null];

        $attributes = [
            'name' => $data['name'],
            'whatsapp' => $data['whatsapp'] !== '' ? $data['whatsapp'] : null,
            'city' => $data['city'] !== '' ? $data['city'] : (StoreSetting::current()->city ?: null),
            'birth_day' => $day,
            'birth_month' => $month,
            'notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ];

        if ($customer === null) {
            return Customer::create($attributes);
        }

        $customer->update($attributes);

        return $customer;
    }

    /** @return array{0: int, 1: int}|null dia e mês de "21/06" (29/02 é aceito) */
    public static function parseBirthday(string $text): ?array
    {
        if (! preg_match('#^(\d{1,2})\s*/\s*(\d{1,2})$#', trim($text), $m)) {
            return null;
        }

        $day = (int) $m[1];
        $month = (int) $m[2];

        return checkdate($month, $day, 2024) ? [$day, $month] : null;
    }
}
