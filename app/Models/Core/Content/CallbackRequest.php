<?php

namespace App\Models\Core\Content;

use App\Enums\CallbackSlot;
use App\Enums\CallbackStatus;
use App\Enums\CallbackSubject;
use App\Policies\CallbackRequestPolicy;
use Database\Factories\Core\Content\CallbackRequestFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * "Être rappelé": a visitor leaves a name, a phone number, a subject and a preferred time slot.
 */
#[UsePolicy(CallbackRequestPolicy::class)]
class CallbackRequest extends Model
{
    /** @use HasFactory<CallbackRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'subject' => CallbackSubject::class,
            'time_slot' => CallbackSlot::class,
            'status' => CallbackStatus::class,
            'contacted_at' => 'datetime',
        ];
    }
}
