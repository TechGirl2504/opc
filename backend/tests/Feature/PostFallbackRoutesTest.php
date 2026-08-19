<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class PostFallbackRoutesTest extends TestCase
{
    public function test_every_write_operation_has_a_post_fallback_route(): void
    {
        $paths = [
            '/api/v1/applications/1/update',
            '/api/v1/applications/1/delete',
            '/api/v1/applications/1/vetting/police/update',
            '/api/v1/applications/1/vetting/nis/update',
            '/api/v1/documents/1/delete',
            '/api/v1/push-subscriptions/delete',
            '/api/v1/users/1/update',
            '/api/v1/users/1/delete',
            '/api/v1/admin/institutions/1/update',
            '/api/v1/admin/institutions/1/delete',
            '/api/v1/admin/application-statuses/1/update',
            '/api/v1/admin/application-statuses/1/delete',
            '/api/v1/admin/vetting-types/1/update',
            '/api/v1/admin/vetting-types/1/delete',
            '/api/v1/admin/document-types/1/update',
            '/api/v1/admin/document-types/1/delete',
            '/api/v1/admin/name-change-reasons/1/update',
            '/api/v1/admin/name-change-reasons/1/delete',
            '/api/v1/admin/reject-reasons/1/update',
            '/api/v1/admin/reject-reasons/1/delete',
            '/api/v1/admin/roles/1/update',
            '/api/v1/admin/roles/1/delete',
        ];

        foreach ($paths as $path) {
            $route = app('router')->getRoutes()->match(Request::create($path, 'POST'));

            $this->assertContains('POST', $route->methods(), "Missing POST fallback for {$path}");
        }
    }
}
