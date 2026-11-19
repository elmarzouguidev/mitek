<?php

namespace App\Services\Core\Service;

use App\Enums\ServiceRequestStatus;
use App\Events\ServiceRequested;
use App\Http\Requests\ServiceRequestForm;
use App\Models\Core\Service\Service;
use App\Models\Core\Service\ServiceRequest;
use App\Support\ReferenceGenerator;

class SubmitServiceRequest
{
    /**
     * @param  array<string, mixed>  $data  validated {@see ServiceRequestForm} input
     */
    public function handle(Service $service, array $data): ServiceRequest
    {
        $request = ServiceRequest::create([
            'reference' => ReferenceGenerator::make('SR', fn (string $candidate): bool => ServiceRequest::where('reference', $candidate)->exists()),
            'service_id' => $service->id,
            'service_title' => $service->title,
            'status' => ServiceRequestStatus::New,
            ...$data,
        ]);

        ServiceRequested::dispatch($request);

        return $request;
    }
}
