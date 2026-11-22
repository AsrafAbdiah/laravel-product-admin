<?php

namespace App\Http\Requests;

use App\Support\CurrentTeam;
use App\Support\Roles;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageMembers', app(CurrentTeam::class)->get());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $team = app(CurrentTeam::class)->get();

        return [
            'email' => [
                'required', 'string', 'email', 'max:255',
                function (string $attribute, mixed $value, Closure $fail) use ($team) {
                    $email = mb_strtolower((string) $value);

                    if ($team->users()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                        $fail('This person is already a member of your shop.');
                    } elseif ($team->invitations()->where('email', $email)->exists()) {
                        $fail('This person has already been invited.');
                    }
                },
            ],
            'role' => ['required', Rule::in(Roles::assignable())],
        ];
    }
}
