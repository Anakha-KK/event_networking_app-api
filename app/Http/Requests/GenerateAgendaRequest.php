<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'days_count' => ['required', 'integer', 'in:5,7'],
        ];
    }

    public function startDate(): \Carbon\CarbonImmutable
    {
        /** @var \Carbon\CarbonImmutable $date */
        $date = \Carbon\CarbonImmutable::parse((string) $this->input('start_date'))->startOfDay();

        return $date;
    }

    public function daysCount(): int
    {
        return (int) $this->integer('days_count', 5);
    }
}
