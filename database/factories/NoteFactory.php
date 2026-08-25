<?php

namespace Database\Factories;

use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return ['title' => fake()->sentence(3), 'content' => fake()->paragraph(), 'embedding' => array_fill(0, 128, 0.0)];
    }
}
