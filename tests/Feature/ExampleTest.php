<?php

test('root web tidak melayani konten (API-only)', function () {
    $this->get('/')->assertNotFound();
});
