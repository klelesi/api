<?php

namespace App\Http\Requests;

use App\Models\UserInteraction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\In;

class StoreUserInteractionRequest extends FormRequest
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
            'type' => ['required', 'in:' . implode(',', [UserInteraction::TYPE_VIEW, UserInteraction::TYPE_UPVOTE, UserInteraction::TYPE_DOWNVOTE])],
            'postId' => ['required_without:commentId', 'exists:posts,id'],
            'commentId' => ['required_without:postId', 'exists:comments,id'],
        ];
    }
}
