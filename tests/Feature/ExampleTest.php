<?php

it('serves the application home', function () {
    $this->get('/')->assertOk();
});
