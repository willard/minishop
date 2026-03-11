<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Export — {{ now()->format('Y-m-d') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 12px;
            color: #111;
            background: #fff;
            padding: 32px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 2px solid #111;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }

        header h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        header p {
            font-size: 11px;
            color: #666;
            margin-top: 2px;
        }

        .meta {
            text-align: right;
            font-size: 11px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background: #f4f4f5;
        }

        th {
            text-align: left;
            padding: 8px 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            border-bottom: 1px solid #e4e4e7;
        }

        td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 500;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-inactive {
            background: #f4f4f5;
            color: #71717a;
        }

        .text-mono {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            color: #555;
        }

        .text-danger {
            color: #dc2626;
            font-weight: 500;
        }

        footer {
            margin-top: 32px;
            padding-top: 12px;
            border-top: 1px solid #e4e4e7;
            font-size: 10px;
            color: #aaa;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            body { padding: 16px; }
            @page { margin: 1cm; size: A4 landscape; }
        }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>Products</h1>
            <p>{{ $products->count() }} product{{ $products->count() === 1 ? '' : 's' }}</p>
        </div>
        <div class="meta">
            <div>Exported {{ now()->format('F j, Y') }}</div>
        </div>
    </header>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>SKU</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Categories</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td class="text-mono">{{ $product->sku ?? '—' }}</td>
                    <td>${{ number_format($product->price / 100, 2) }}</td>
                    <td @class(['text-danger' => $product->stock_quantity === 0])>{{ $product->stock_quantity }}</td>
                    <td>
                        <span @class(['badge', 'badge-active' => $product->is_active, 'badge-inactive' => ! $product->is_active])>
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>{{ $product->categories->pluck('name')->join(', ') ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:#aaa; padding: 32px;">No products found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <footer>
        <span>Minishop Admin</span>
        <span>Print or save as PDF using your browser's print dialog (Ctrl+P / ⌘+P)</span>
    </footer>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>
</html>
