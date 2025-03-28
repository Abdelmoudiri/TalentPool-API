<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        $jobOfferId = $this->route('id');
        
        // Only recruiters who own the job offer can update it
        if ($user && $user->isRecruiter() && $jobOfferId) {
            $jobOffer = \App\Models\JobOffer::find($jobOfferId);
            return $jobOffer && $jobOffer->user_id === $user->id;
        }
        
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'location' => 'nullable|string|max:255',
            'company_name' => 'sometimes|required|string|max:255',
            'contract_type' => 'sometimes|required|string|max:100',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date|after:today',
        ];
    }
}
