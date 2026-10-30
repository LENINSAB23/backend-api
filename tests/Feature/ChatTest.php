<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.gemini.api_key', 'test-gemini-key');
        Config::set('services.gemini.model', 'gemini-1.5-flash');
    }

    public function test_unauthenticated_user_cannot_access_chat(): void
    {
        $response = $this->getJson('/api/chat/conversations');
        $response->assertStatus(401);
    }

    public function test_user_can_create_conversation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/chat/conversations', [
            'title' => 'Consulta sobre Bujías',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'title' => 'Consulta sobre Bujías',
                    'user_id' => $user->id,
                ],
            ]);

        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'title' => 'Consulta sobre Bujías',
        ]);
    }

    public function test_user_can_list_only_their_conversations(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Conversation::factory()->count(2)->create(['user_id' => $user1->id]);
        Conversation::factory()->count(3)->create(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);

        $response = $this->getJson('/api/chat/conversations');
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_cannot_view_another_users_conversation_messages(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $conversation = Conversation::factory()->create(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);

        $response = $this->getJson("/api/chat/conversations/{$conversation->id}/messages");
        $response->assertStatus(404);
    }

    public function test_user_can_send_message_and_gemini_generates_response(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'user_id' => $user->id,
            'title' => 'Nueva conversación',
        ]);

        Sanctum::actingAs($user);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Hola, en Grupo Rino tenemos bujías para tu motocicleta.'],
                            ],
                            'role' => 'model',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson("/api/chat/conversations/{$conversation->id}/messages", [
            'message' => '¿Tienen bujías para pulsar 200?',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'respuesta' => 'Hola, en Grupo Rino tenemos bujías para tu motocicleta.',
                'conversation_id' => $conversation->id,
            ]);

        // Assert user message and bot response persisted
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => '¿Tienen bujías para pulsar 200?',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'model',
            'content' => 'Hola, en Grupo Rino tenemos bujías para tu motocicleta.',
        ]);

        // Assert conversation title was auto-updated from first message
        $conversation->refresh();
        $this->assertStringContainsString('¿Tienen bujías', $conversation->title);
    }

    public function test_user_can_retrieve_message_history(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Primer mensaje',
        ]);

        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'role' => 'model',
            'content' => 'Respuesta del asistente',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/chat/conversations/{$conversation->id}/messages");
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.role', 'user')
            ->assertJsonPath('data.1.role', 'model');
    }

    public function test_handles_gemini_api_error_gracefully(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 429,
                    'message' => 'Resource exhausted',
                ],
            ], 429),
        ]);

        $response = $this->postJson("/api/chat/conversations/{$conversation->id}/messages", [
            'message' => 'Pregunta que fallará',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'status' => 'error',
            ]);

        // Confirm user message was cleaned up and not left orphaned
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'content' => 'Pregunta que fallará',
        ]);
    }

    public function test_handles_missing_gemini_api_key(): void
    {
        Config::set('services.gemini.api_key', '');

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/chat/conversations/{$conversation->id}/messages", [
            'message' => 'Hola Gemini',
        ]);

        $response->assertStatus(502)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_user_can_delete_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);
        Message::factory()->create(['conversation_id' => $conversation->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/chat/conversations/{$conversation->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('messages', ['conversation_id' => $conversation->id]);
    }
}
