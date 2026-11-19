@php
    $fmt = fn ($money) => $money->format("\u{00A0}");
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $creditNote->number }}</title>
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #1e293b; }
        h1 { font-size: 16pt; color: #7f1d1d; margin: 0; }
        h2 { font-size: 9pt; text-transform: uppercase; color: #64748b; margin: 0 0 4px 0; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { vertical-align: top; }
        .box { border: 1px solid #cbd5e1; padding: 8px 10px; }
        .items th { background: #0c2140; color: #ffffff; text-align: left; padding: 6px 6px; font-size: 8pt; }
        .items td { border-bottom: 1px solid #e2e8f0; padding: 6px 6px; vertical-align: top; }
        .r { text-align: right; }
        .totals td { padding: 3px 6px; }
        .grand td { font-size: 11pt; font-weight: bold; color: #7f1d1d; border-top: 2px solid #7f1d1d; }
        .muted { color: #64748b; }
        .todo { color: #b45309; }
        .footer { font-size: 7.5pt; color: #64748b; text-align: center; border-top: 1px solid #cbd5e1; padding-top: 6px; }
    </style>
</head>
<body>
    <table class="meta">
        <tr>
            <td style="width: 55%;">
                @include('invoices.partials.seller')
            </td>
            <td style="width: 45%;" class="r">
                <h1>AVOIR</h1>
                <p style="margin: 4px 0 0 0;"><strong>{{ $creditNote->number }}</strong></p>
                <p class="muted" style="margin: 2px 0 0 0;">
                    Date : {{ $creditNote->issued_at->translatedFormat('d/m/Y') }}<br>
                    Sur facture : {{ $invoice->number }} du {{ $invoice->issued_at->translatedFormat('d/m/Y') }}<br>
                    Commande : {{ $order->reference }}
                </p>
            </td>
        </tr>
    </table>

    <br>
    <table>
        <tr>
            <td style="width: 49%;" class="box">
                <h2>Client</h2>
                @if ($order->company_name)
                    <strong>{{ $order->company_name }}</strong><br>
                    @if ($order->ice)ICE : {{ $order->ice }}<br>@endif
                    @if ($order->if_number)IF : {{ $order->if_number }}<br>@endif
                    @if ($order->rc_number)RC : {{ $order->rc_number }}<br>@endif
                    <span class="muted">Contact : {{ $order->contact_name }}</span>
                @else
                    <strong>{{ $order->contact_name }}</strong>
                @endif
                <br>{{ $order->contact_email }}
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 49%;" class="box">
                <h2>Motif</h2>
                Annulation de la commande {{ $order->reference }}.<br>
                <span class="muted">Cet avoir annule la facture {{ $invoice->number }} dans son intégralité.</span>
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
                    <td class="r">-{{ $item->quantity }}</td>
                    <td class="r">{{ $fmt($item->unit_price_ht) }}</td>
                    <td class="r">{{ $item->vat_rate }} %</td>
                    <td class="r">{{ $fmt($negative($item->line_ht)) }}</td>
                    <td class="r">{{ $fmt($negative($item->line_ttc)) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Frais de livraison</strong></td>
                <td class="r">-1</td>
                <td class="r">{{ $fmt($order->shipping_ht) }}</td>
                <td class="r">{{ $shippingVatRate !== null ? $shippingVatRate.' %' : '' }}</td>
                <td class="r">{{ $fmt($negative($order->shipping_ht)) }}</td>
                <td class="r">{{ $fmt($negative($order->shipping_ht)->plus($shippingVat)) }}</td>
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
                    @if ($shippingVat->cents !== 0)
                        <tr><td>Livraison @if ($shippingVatRate !== null){{ $shippingVatRate }} %@endif</td><td class="r">Base {{ $fmt($negative($order->shipping_ht)) }}</td><td class="r">TVA {{ $fmt($shippingVat) }}</td></tr>
                    @endif
                </table>
            </td>
            <td style="width: 48%; vertical-align: top;">
                <table class="totals">
                    <tr><td>Total HT</td><td class="r">{{ $fmt($creditNote->subtotal_ht) }}</td></tr>
                    <tr><td>Total TVA</td><td class="r">{{ $fmt($creditNote->vat_total) }}</td></tr>
                    <tr class="grand"><td>Total TTC à rembourser</td><td class="r">{{ $fmt($creditNote->total_ttc) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <br><br>
    @include('invoices.partials.legal-footer')
</body>
</html>
