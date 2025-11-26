<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 14px; }
h1 { font-size: 20px; margin-bottom: 10px; }
h2 { font-size: 16px; margin-top: 20px; }
.content { margin-top: 20px; }
</style>
</head>
<body>

<h1>{{ $doc->type === 'agenda' ? 'Повестка дня' : 'Протокол' }} №{{ $doc->number }}</h1>
<p><b>Дата:</b> {{ $doc->document_date }}</p>
<p><b>Автор:</b> {{ $doc->creator->name }}</p>

@if($doc->task)
<p><b>Задача:</b> {{ $doc->task->title }}</p>
@endif

@if($doc->subtask)
<p><b>Подзадача:</b> {{ $doc->subtask->title }}</p>
@endif

<h2>{{ $doc->title }}</h2>

<div class="content">
    {!! $doc->body !!}
</div>

</body>
</html>
