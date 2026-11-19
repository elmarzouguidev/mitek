<?php

namespace App\Services\Core\Quote;

use App\Enums\QuoteStatus;
use App\Events\QuoteReplied;
use App\Models\Core\Quote\QuoteRequest;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReplyToQuote
{
    /**
     * @throws ValidationException when the quote is closed
     */
    public function handle(QuoteRequest $quote, User $admin, string $reply): QuoteRequest
    {
        $quote = DB::transaction(function () use ($quote, $admin, $reply): QuoteRequest {
            $quote = QuoteRequest::query()->lockForUpdate()->findOrFail($quote->id);

            if ($quote->status === QuoteStatus::Closed) {
                throw ValidationException::withMessages(['admin_reply' => __('quotes.errors.closed')]);
            }

            $quote->update([
                'status' => QuoteStatus::Replied,
                'admin_reply' => $reply,
                'replied_at' => now(),
                'replied_by' => $admin->id,
            ]);

            return $quote;
        });

        QuoteReplied::dispatch($quote);

        return $quote;
    }
}
