<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
    h1 { color: #2c3e50; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { background: #2c3e50; color: white; padding: 8px; text-align: left; }
    td { padding: 6px 8px; border-bottom: 1px solid #eee; }
    .totals td { font-weight: bold; background: #f8f9fa; }
    .header { border-bottom: 2px solid #2c3e50; padding-bottom: 12px; margin-bottom: 16px; }
</style>
</head>
<body>
<div class="header">
    <h1>Budget Report &mdash; {{ $trip->destination }}</h1>
    <p>{{ $trip->start_date }} to {{ $trip->end_date }} &bull; {{ $trip->travel_type }} &bull; {{ $trip->num_travelers }} traveler(s)</p>
    <p>Generated: {{ now()->format('F j, Y') }}</p>
</div>

<h3>Budget Summary</h3>
<table>
    <thead><tr><th>Category</th><th>Estimated</th><th>Spent</th><th>Remaining</th></tr></thead>
    <tbody>
    @foreach($summary['categories'] as $cat)
        <tr>
            <td>{{ $cat['category'] }}</td>
            <td>&#8369;{{ number_format($cat['estimated_cost'], 2) }}</td>
            <td>&#8369;{{ number_format($cat['actual_spent'], 2) }}</td>
            <td style="{{ $cat['remaining'] < 0 ? 'color:red' : '' }}">&#8369;{{ number_format($cat['remaining'], 2) }}</td>
        </tr>
    @endforeach
    <tr class="totals">
        <td>TOTAL</td>
        <td>&#8369;{{ number_format($summary['total_estimated'], 2) }}</td>
        <td>&#8369;{{ number_format($summary['total_spent'], 2) }}</td>
        <td>&#8369;{{ number_format($summary['remaining'], 2) }}</td>
    </tr>
    </tbody>
</table>

<h3 style="margin-top:24px;">Expense Log</h3>
<table>
    <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th></tr></thead>
    <tbody>
    @forelse($expenses as $exp)
        <tr>
            <td>{{ $exp->expense_date }}</td>
            <td>{{ $exp->category }}</td>
            <td>{{ $exp->description ?? '&mdash;' }}</td>
            <td>&#8369;{{ number_format($exp->amount, 2) }}</td>
        </tr>
    @empty
        <tr><td colspan="4">No expenses recorded.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
