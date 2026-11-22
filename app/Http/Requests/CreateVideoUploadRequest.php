<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateVideoUploadRequest extends FormRequest
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
            'mime_type' => ['required', 'string', Rule::in(array_keys(config('videos.mime_types')))],
            'size' => ['required', 'integer', 'min:1', 'max:'.config('videos.max_upload_bytes')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mime_type.in' => 'Upload an MP4, WebM, MOV or MKV video.',
            'size.max' => 'The video is larger than '.(int) (config('videos.max_upload_bytes') / 1024 / 1024).' MB.',
        ];
    }
}
