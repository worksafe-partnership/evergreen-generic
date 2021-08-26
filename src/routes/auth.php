<?php

Route::group(['middleware' => ['web']], function () {
    Route::auth();
});
