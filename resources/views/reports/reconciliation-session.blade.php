<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reconciliation Report — Session #{{ $session->id }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1a1a1a; padding: 20px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; color: #666; margin-top: 20px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta { color: #666; font-size: 11px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #f5f5f5; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #666; border-bottom: 2px solid #ddd; }
        td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        .num { text-align: right; font-family: monospace; }
        .positive { color: #16a34a; }
        .negative { color: #dc2626; }
        .summary-box { border: 1px solid #ddd; padding: 10px; margin: 8px 0; display: inline-block; min-width: 140px; }
        .summary-box .label { font-size: 9px; text-transform: uppercase; color: #999; }
        .summary-box .value { font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Reconciliation Report</h1>
    <div class="meta">
        Session #{{ $session->id }} | Location: {{ $session->location->name }} ({{ $session->location->code }}) |
        Status: {{ $session->status }} |
        Period: {{ optional($session->started_at)->toDateString() }} – {{ optional($session->closed_at)->toDateString() ?? 'Open' }}
    </div>

    <div>
        <div class="summary-box">
            <div class="label">Total Lines</div>
            <div class="value">{{ $summary['total_lines'] }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Total Variance</div>
            <div class="value">{{ $summary['total_variance_units'] }} u</div>
        </div>
        <div class="summary-box">
            <div class="label">Absolute Variance</div>
            <div class="value">{{ $summary['absolute_variance_units'] }} u</div>
        </div>
        <div class="summary-box">
            <div class="label">Net $ Impact</div>
            <div class="value {{ $summary['net_financial_impact'] < 0 ? 'negative' : 'positive' }}">
                ${{ number_format($summary['net_financial_impact'], 2) }}
            </div>
        </div>
        <div class="summary-box">
            <div class="label">Large Variances</div>
            <div class="value">{{ $summary['large_variance_lines'] }}</div>
        </div>
    </div>

    <h2>Count Line Detail</h2>
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th class="num">Physical</th>
                <th class="num">System</th>
                <th class="num">Variance</th>
                <th class="num">%</th>
                <th class="num">$ Impact</th>
                <th>Resolution</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['investigation_priority'] as $item)
            <tr>
                <td>{{ $item['product_sku'] }}</td>
                <td>{{ $item['product_name'] }}</td>
                <td class="num">{{ $item['physical_quantity'] }}</td>
                <td class="num">{{ $item['system_quantity'] }}</td>
                <td class="num {{ $item['variance'] > 0 ? 'positive' : ($item['variance'] < 0 ? 'negative' : '') }}">
                    {{ $item['variance'] > 0 ? '+' : '' }}{{ $item['variance'] }}
                </td>
                <td class="num">{{ number_format($item['variance_percentage'], 1) }}%</td>
                <td class="num {{ $item['dollar_variance'] < 0 ? 'negative' : '' }}">
                    ${{ number_format($item['dollar_variance'], 2) }}
                </td>
                <td>{{ $item['resolution_type'] ?? $item['status'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Variance by Direction</h2>
    <table>
        <thead>
            <tr>
                <th>Direction</th>
                <th class="num">Count</th>
                <th>Interpretation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Positive (physical &gt; system)</td>
                <td class="num">{{ $summary['positive_variances_count'] }}</td>
                <td>Unrecorded stock-in, counting error, or system undercount</td>
            </tr>
            <tr>
                <td>Negative (physical &lt; system)</td>
                <td class="num">{{ $summary['negative_variances_count'] }}</td>
                <td>Loss, theft, damage, unrecorded stock-out, or counting error</td>
            </tr>
            <tr>
                <td>Zero variance</td>
                <td class="num">{{ $summary['zero_variance_lines'] }}</td>
                <td>Count matches system — no action needed</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top: 30px; font-size: 10px; color: #999;">
        Generated by Warehouse Inventory System on {{ now()->toDateTimeString() }}.
        This report is confidential.
    </p>
</body>
</html>
