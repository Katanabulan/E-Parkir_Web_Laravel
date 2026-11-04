<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class OwnerRoleTest extends TestCase
{
    public function test_owner_model_helpers(): void
    {
        $owner = new User(['role' => 'owner']);
        $this->assertTrue($owner->isOwner());
        $this->assertFalse($owner->isAdmin());
        $this->assertFalse($owner->isClient());

        $admin = new User(['role' => 'admin']);
        $this->assertFalse($admin->isOwner());
        $this->assertTrue($admin->isAdmin());

        $client = new User(['role' => 'client']);
        $this->assertFalse($client->isOwner());
        $this->assertTrue($client->isClient());
    }
}
