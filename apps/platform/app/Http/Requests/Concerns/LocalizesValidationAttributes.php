<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

/**
 * Maps validation field names to translated labels for the active locale.
 */
trait LocalizesValidationAttributes
{
    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Name'),
            'email' => __('Email address'),
            'password' => __('Password'),
            'password_confirmation' => __('Confirm password'),
            'current_password' => __('Current password'),
            'locale' => __('Language'),
            'title' => __('Title'),
            'description' => __('Description'),
            'category' => __('Category'),
            'type' => __('Type'),
            'instructions' => __('Instructions'),
            'client_id' => __('Client'),
            'reference' => __('Reference (optional)'),
            'document' => __('Document'),
            'decision' => __('Decision'),
            'rejection_reason' => __('Feedback for the client'),
            'answer_text' => __('Answer'),
            'answer_boolean' => __('Yes / no'),
            'document_request_ids' => __('Document requests'),
            'item_ids' => __('Questionnaire items'),
            'questionnaire_template_id' => __('Template'),
            'expires_in_days' => __('Link validity (days)'),
            'send_invite' => __('Send email invite'),
        ];
    }
}
