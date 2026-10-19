<?php

it('uses pt_BR locale and the Porto Velho timezone', function () {
    expect(config('app.locale'))->toBe('pt_BR')
        ->and(config('app.timezone'))->toBe('America/Porto_Velho')
        ->and(config('app.faker_locale'))->toBe('pt_BR');
});

it('returns validation messages in Portuguese', function () {
    $validator = validator(['name' => ''], ['name' => 'required']);

    expect($validator->errors()->first('name'))->toBe('O campo nome é obrigatório.');
});
