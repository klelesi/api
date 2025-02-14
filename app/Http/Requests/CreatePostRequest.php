<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'postType' => 'in:' . implode(',', [Post::POST_TYPE_MARKDOWN, Post::POST_TYPE_LINK]),
            'markdown' => 'required_if:postType,0|string',
            'url' => 'required_if:postType,1|string|url',
        ];
    }
}
