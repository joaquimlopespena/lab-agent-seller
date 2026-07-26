<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests away from administrative destinations', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with([
    'integrations' => 'admin.integrations.index',
    'synced data' => 'admin.synced-data.index',
    'conversations' => 'admin.conversations.index',
]);

it('renders each administrative destination for an authenticated administrator', function (
    string $routeName,
    string $component,
) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'integrations' => [
        'admin.integrations.index',
        'admin/integrations/index',
    ],
    'synced data' => [
        'admin.synced-data.index',
        'admin/synced-data/index',
    ],
    'conversations' => [
        'admin.conversations.index',
        'admin/conversations/index',
    ],
]);
