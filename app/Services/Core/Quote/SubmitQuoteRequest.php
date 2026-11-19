<?php

namespace App\Services\Core\Quote;

use App\Enums\QuoteStatus;
use App\Events\QuoteRequested;
use App\Http\Requests\QuoteRequestForm;
use App\Models\Core\Quote\QuoteRequest;
use App\Models\User\User;
use App\Services\Core\Cart\CartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Random\RandomException;

/**
 * Turns the cart into a quote request. Prices are indicative: the quantity tier is applied, but the
 * administrator's reply is what counts.
 */
class SubmitQuoteRequest
{
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public function __construct(private readonly CartService $cart) {}

    /**
     * @param  array<string, mixed>  $data  validated {@see QuoteRequestForm} input
     *
     * @throws ValidationException
     */
    public function handle(array $data, ?User $user = null): QuoteRequest
    {
        $summary = $this->cart->summary();

        if ($summary->isEmpty()) {
            throw ValidationException::withMessages(['cart' => __('orders.errors.empty_cart')]);
        }

        $quote = DB::transaction(function () use ($data, $user, $summary): QuoteRequest {
            $quote = QuoteRequest::create([
                'reference' => $this->uniqueReference(),
                'user_id' => $user?->id,
                'status' => QuoteStatus::New,
                'contact_name' => $data['contact_name'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'],
                'company_name' => $data['company_name'] ?? null,
                'ice' => $data['ice'] ?? null,
                'message' => $data['message'] ?? null,
            ]);

            foreach ($summary->lines as $line) {
                $quote->items()->create([
                    'product_id' => $line->product->id,
                    'reference' => $line->product->reference,
                    'name' => $line->product->name,
                    'quantity' => $line->quantity,
                    'unit_price_ht' => $line->quote->unitHt,
                ]);
            }

            return $quote;
        });

        QuoteRequested::dispatch($quote);

        return $quote;
    }

    /**
     * @throws RandomException
     */
    private function uniqueReference(): string
    {
        do {
            $reference = 'DV-';
            for ($index = 0; $index < 6; $index++) {
                $reference .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (QuoteRequest::where('reference', $reference)->exists());

        return $reference;
    }
}
