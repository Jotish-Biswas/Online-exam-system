<?php

test('the application returns a successful response', function () {
    $response = $this->get('/start-exam');

    $response->assertStatus(200);
});
