@php
    $fmt = fn ($money) => $money->format("\u{00A0}");
    $isCod = $order->isCashOnDelivery();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #1e293b; }
        h1 { font-size: 16pt; color: #0c2140; margin: 0; }
        h2 { font-size: 9pt; text-transform: uppercase; color: #64748b; margin: 0 0 4px 0; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { vertical-align: top; }
        .box { border: 1px solid #cbd5e1; padding: 8px 10px; }
        .items th { background: #0c2140; color: #ffffff; text-align: left; padding: 6px 6px; font-size: 8pt; }
        .items td { border-bottom: 1px solid #e2e8f0; padding: 6px 6px; vertical-align: top; }
        .r { text-align: right; }
        .totals td { padding: 3px 6px; }
        .grand td { font-size: 11pt; font-weight: bold; color: #0c2140; border-top: 2px solid #0c2140; }
        .muted { color: #64748b; }
        .todo { color: #b45309; }
        .footer { font-size: 7.5pt; color: #64748b; text-align: center; border-top: 1px solid #cbd5e1; padding-top: 6px; }
    </style>
</head>
<body>
    <table class="meta">
        <tr>
            <td style="width: 55%;">
                <img src="{{ $logo }}" alt="{{ $seller['site'] ?? 'Mitek.ma' }}" style="height: 48px;"><br><br>
                <strong>{{ $seller['name'] }}</strong><br>
                {{ $seller['address'] }}<br>
                {{ $seller['email'] }}@if (! empty($seller['phone'])) · {{ $seller['phone'] }}@endif<br>
                <span class="muted">
                    ICE : @if (! empty($seller['ice'])){{ $seller['ice'] }}@else<span class="todo">[À COMPLÉTER]</span>@endif ·
                    IF : @if (! empty($seller['if'])){{ $seller['if'] }}@else<span class="todo">[À COMPLÉTER]</span>@endif ·
                    RC : @if (! empty($seller['rc'])){{ $seller['rc'] }}@else<span class="todo">[À COMPLÉTER]</span>@endif
                </span>
            </td>
            <td style="width: 45%;" class="r">
                <h1>FACTURE</h1>
                <p style="margin: 4px 0 0 0;"><strong>{{ $invoice->number }}</strong></p>
                <p class="muted" style="margin: 2px 0 0 0;">Date : {{ $invoice->issued_at->translatedFormat('d/m/Y') }}<br>Commande : {{ $order->reference }}</p>
            </td>
        </tr>
    </table>

    <br>
    <table>
        <tr>
            <td style="width: 49%;" class="box">
                <h2>Facturé à</h2>
                @if ($order->company_name)
                    <strong>{{ $order->company_name }}</strong><br>
                    @if ($order->ice)ICE : {{ $order->ice }}<br>@endif
                    @if ($order->if_number)IF : {{ $order->if_number }}<br>@endif
                    @if ($order->rc_number)RC : {{ $order->rc_number }}<br>@endif
                    <span class="muted">Contact : {{ $order->contact_name }}</span>
                @else
                    <strong>{{ $order->contact_name }}</strong>
                @endif
                <br>{{ $order->contact_email }}<br>{{ $order->contact_phone }}
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 49%;" class="box">
                <h2>Livraison</h2>
                {{ $order->contact_name }}<br>
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_postal_code }} {{ $order->shipping_city }}
            </td>
        </tr>
    </table>

    <br>
    <table class="items">
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="r">Qté</th>
                <th class="r">PU HT</th>
                <th class="r">TVA</th>
                <th class="r">Total HT</th>
                <th class="r">Total TTC</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td><strong>{{ $item->name }}</strong><br><span class="muted">Réf. {{ $item->reference }}</span></td>
                    <td class="r">{{ $item->quantity }}</td>
                    <td class="r">{{ $fmt($item->unit_price_ht) }}</td>
                    <td class="r">{{ $item->vat_rate }} %</td>
                    <td class="r">{{ $fmt($item->line_ht) }}</td>
                    <td class="r">{{ $fmt($item->line_ttc) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Frais de livraison</strong>@if ($order->shipping_zone_name)<br><span class="muted">{{ $order->shipping_zone_name }}</span>@endif @if ($order->carrier_mode === \App\Enums\CarrierMode::Own)<br><span class="muted">Transporteur du client</span>@endif</td>
                <td class="r">1</td>
                <td class="r">{{ $fmt($order->shipping_ht) }}</td>
                <td class="r">{{ $shippingVatRate !== null ? $shippingVatRate.' %' : '' }}</td>
                <td class="r">{{ $fmt($order->shipping_ht) }}</td>
                <td class="r">{{ $fmt($order->shipping_ht->plus($shippingVat)) }}</td>
            </tr>
        </tbody>
    </table>

    <br>
    <table>
        <tr>
            <td style="width: 52%; vertical-align: top;">
                <table class="totals">
                    <tr><td colspan="3"><h2>Détail de la TVA</h2></td></tr>
                    @foreach ($vatGroups as $group)
                        <tr><td>Taux {{ $group['rate'] }} %</td><td class="r">Base {{ $fmt($group['base']) }}</td><td class="r">TVA {{ $fmt($group['vat']) }}</td></tr>
                    @endforeach
                    @if ($shippingVat->cents > 0)
                        <tr><td>Livraison @if ($shippingVatRate !== null){{ $shippingVatRate }} %@endif</td><td class="r">Base {{ $fmt($order->shipping_ht) }}</td><td class="r">TVA {{ $fmt($shippingVat) }}</td></tr>
                    @endif
                </table>
                <p class="muted" style="margin-top: 10px;">
                    Mode de paiement : {{ $order->payment_method->label() }}.<br>
                    @if ($isCod)Paiement à la livraison.@elseif ($order->paid_at)Réglée le {{ $order->paid_at->translatedFormat('d/m/Y') }}.@endif
                </p>
            </td>
            <td style="width: 48%; vertical-align: top;">
                <table class="totals">
                    <tr><td>Total HT</td><td class="r">{{ $fmt($order->subtotal_ht->plus($order->shipping_ht)) }}</td></tr>
                    <tr><td>Total TVA</td><td class="r">{{ $fmt($order->vat_total) }}</td></tr>
                    <tr class="grand"><td>Total TTC</td><td class="r">{{ $fmt($order->total_ttc) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <br><br>
    <div class="footer">
        {{ $seller['name'] }} · {{ $seller['address'] }} · {{ $seller['email'] }}
    </div>
</body>
</html>
