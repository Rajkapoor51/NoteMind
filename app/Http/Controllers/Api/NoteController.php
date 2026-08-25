<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use App\Services\SemanticSearchService;
use App\Services\SummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(private SemanticSearchService $semantic, private SummaryService $summaries) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'limit' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $notes = Note::latest()->paginate($request->integer('limit', 10));

        return response()->json(['success' => true, 'message' => 'Notes retrieved.', 'data' => $notes->items(), 'meta' => ['page' => $notes->currentPage(), 'limit' => $notes->perPage(), 'total' => $notes->total(), 'last_page' => $notes->lastPage()]]);
    }

    public function store(StoreNoteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['embedding'] = $this->semantic->embed($data['title'].' '.$data['content']);
        $note = Note::create($data);

        return response()->json(['success' => true, 'message' => 'Note created.', 'data' => $note], 201);
    }

    public function show(Note $note): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $note]);
    }

    public function update(UpdateNoteRequest $request, Note $note): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['title']) || isset($data['content'])) {
            $data['embedding'] = $this->semantic->embed(($data['title'] ?? $note->title).' '.($data['content'] ?? $note->content));
        }
        $note->update($data);

        return response()->json(['success' => true, 'message' => 'Note updated.', 'data' => $note->fresh()]);
    }

    public function destroy(Note $note): JsonResponse
    {
        $note->delete();

        return response()->json(['success' => true, 'message' => 'Note deleted.']);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:500'], 'limit' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = $this->semantic->embed($request->string('q')->toString());
        $results = Note::all()->map(fn (Note $note) => ['note' => $note, 'score' => $this->semantic->similarity($query, $note->embedding ?? [])])->filter(fn ($item) => $item['score'] > 0)->sortByDesc('score')->take($request->integer('limit', 10))->map(fn ($item) => array_merge($item['note']->toArray(), ['semantic_score' => round($item['score'], 4)]))->values();

        return response()->json(['success' => true, 'message' => 'Semantic search completed.', 'data' => $results]);
    }

    public function summary(Note $note): JsonResponse
    {
        $result = $this->summaries->summarize($note->content);
        $note->update(['summary' => $result['summary']]);

        return response()->json(['success' => true, 'message' => 'Summary generated.', 'data' => ['id' => $note->id, 'summary' => $note->summary, 'provider' => $result['provider'], 'provider_status' => $result['provider_status'], 'warning' => $result['warning']]]);
    }
}
