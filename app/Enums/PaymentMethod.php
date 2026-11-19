<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case CashDeposit = 'cash_deposit';
    case CashOnDelivery = 'cod';
    case Cheque = 'cheque';

    public function label(): string
    {
        return __('orders.methods.'.$this->value);
    }

    /**
     * Whether the customer must upload a proof (transfer slip, deposit slip, cheque copy).
     */
    public function requiresProof(): bool
    {
        return $this !== self::CashOnDelivery;
    }
}
