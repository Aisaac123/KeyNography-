<?php


Broadcast::routes(['middleware' => ['web']]);

Broadcast::channel('global-chat', function () : bool {
    return true;
});

