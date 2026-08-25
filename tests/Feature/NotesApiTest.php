<?php

namespace Tests\Feature;

use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_note_lifecycle_and_summary(): void
    {
        $created = $this->postJson('/api/notes', ['title' => 'Project launch', 'content' => 'The launch is Friday. The team needs final approval.']);
        $created->assertCreated()->assertJsonPath('data.title', 'Project launch');
        $id = $created->json('data.id');
        $this->putJson("/api/notes/$id", ['title' => 'Updated launch'])->assertOk();
        $this->postJson("/api/notes/$id/summary")->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson('/api/notes/search?q=approval')->assertOk()->assertJsonCount(1, 'data');
        $this->deleteJson("/api/notes/$id")->assertOk();
    }

    public function test_validation_and_pagination(): void
    {
        $this->postJson('/api/notes', [])->assertUnprocessable();
        Note::factory()->count(2)->create();
        $this->getJson('/api/notes?limit=1')->assertOk()->assertJsonPath('meta.limit', 1);
    }
}
