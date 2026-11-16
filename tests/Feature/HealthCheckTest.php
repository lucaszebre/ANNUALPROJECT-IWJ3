<?php

it('exposes the built-in health route', function () {
    $this->get('/up')->assertOk();
});
