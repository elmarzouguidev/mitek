<?php

namespace App\Models\Core\Service;

use App\Enums\Intervention;
use App\Enums\ServiceRequestStatus;
use App\Enums\Urgency;
use App\Policies\ServiceRequestPolicy;
use App\Traits\UuidGenerator;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(ServiceRequestPolicy::class)]
class ServiceRequest extends Model
{
    use UuidGenerator;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => ServiceRequestStatus::class,
            'intervention' => Intervention::class,
            'urgency' => Urgency::class,
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
