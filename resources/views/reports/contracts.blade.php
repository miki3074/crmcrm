<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Отчет по договорам</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .totals { margin-top: 20px; text-align: right; font-size: 12px; font-weight: bold; }
        .badge { padding: 2px 4px; border-radius: 3px; color: white; }
        .dealer { background-color: #7e22ce; } /* Purple */
        .agency { background-color: #c2410c; } /* Orange */
        .general { background-color: #475569; } /* Slate */
    </style>
</head>
<body>
<div class="header">
    <h1>Отчет по договорам</h1>
    <p>Период: {{ $dateRange }}</p>
    <p>Тип фильтра:
        @if($filterType == 'all') Все договоры
        @elseif($filterType == 'project') По Проекту
        @elseif($filterType == 'task') По Задаче
        @endif
    </p>
</div>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Дата</th>
        <th>Название</th>
        <th>Тип</th>
        <th>Контрагент</th>
        <th>Проект / Задача</th>
        <th>Сумма</th>
        <th>Маржа</th>
    </tr>
    </thead>
    <tbody>
    @foreach($contracts as $contract)
        <tr>
            <td>{{ $contract->id }}</td>
            <td>{{ $contract->signed_at ? $contract->signed_at->format('d.m.Y') : '-' }}</td>
            <td>{{ $contract->title }}</td>
            <td>
                @if($contract->type == 'dealer') Дилерский
                @elseif($contract->type == 'agency') Агентский
                @else Общий
                @endif
            </td>
            <td>{{ $contract->counterparty }}</td>
            <td>
                {{ $contract->task->project->name ?? '-' }} <br>
                <small>({{ $contract->task->title ?? 'Без задачи' }})</small>
            </td>
            <td>{{ number_format($contract->amount, 2, ',', ' ') }} ₽</td>
            <td>{{ number_format($contract->margin, 2, ',', ' ') }} ₽</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="totals">
    <p>Итого оборот: {{ number_format($totalAmount, 2, ',', ' ') }} ₽</p>
    <p>Итого прибыль: {{ number_format($totalMargin, 2, ',', ' ') }} ₽</p>
</div>
</body>
</html>
