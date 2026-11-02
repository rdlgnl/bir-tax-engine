<?php

namespace App\Books\Requests;

use App\Books\Enums\BooksType;
use App\Tax\Enums\BooksMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBooksTypeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'books_type' => ['required_without:books_method', Rule::enum(BooksType::class)],
            'books_method' => ['required_without:books_type', Rule::enum(BooksMethod::class)],
        ];
    }
}
