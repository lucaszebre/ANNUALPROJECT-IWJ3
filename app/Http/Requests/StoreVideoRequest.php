<?php

namespace App\Http\Requests;

use App\Enums\VideoLanguage;
use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Lesson $lesson */
        $lesson = $this->route('lesson');

        return $this->user()->can('update', $lesson->course);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'language' => ['required', Rule::enum(VideoLanguage::class)],
        ];
    }
}
