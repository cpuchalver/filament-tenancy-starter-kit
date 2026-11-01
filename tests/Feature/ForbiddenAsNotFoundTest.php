<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;

it('answers 404 when a request is forbidden', function () {
    Route::get('/_test/forbidden', fn () => abort(403));

    get('/_test/forbidden')->assertNotFound();
});

it('answers 404 when an authorization check fails', function () {
    Gate::define('neverAllowed', fn () => false);
    Route::get('/_test/unauthorized', fn () => Gate::authorize('neverAllowed'));

    get('/_test/unauthorized')->assertNotFound();
});

it('keeps other http errors unchanged', function () {
    Route::get('/_test/server-error', fn () => abort(500));

    get('/_test/server-error')->assertInternalServerError();
});
