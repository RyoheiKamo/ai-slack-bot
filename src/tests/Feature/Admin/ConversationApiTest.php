<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_list_can_be_fetched(): void
    {
        $response = $this->getJson('/api/admin/conversations');

        $response->assertOk();
    }
}
