<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_permissions_policy_allows_the_microphone_on_our_own_origin(): void
    {
        $policy = $this->get('/')->headers->get('Permissions-Policy');

        // Voice search in the header search bar needs this. `microphone=()`
        // stops Chrome from ever prompting the customer.
        $this->assertStringContainsString('microphone=(self)', $policy);
    }

    public function test_permissions_policy_still_withholds_the_camera_and_location(): void
    {
        $policy = $this->get('/')->headers->get('Permissions-Policy');

        $this->assertStringContainsString('camera=()', $policy);
        $this->assertStringContainsString('geolocation=()', $policy);
    }
}
