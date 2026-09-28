@php($analysis = $job->payload['analise_ia'] ?? [])
@php($source = $job->payload)
@php($link = filter_var($job->url, FILTER_VALIDATE_URL) && in_array(parse_url($job->url, PHP_URL_SCHEME), ['http', 'https'], true) ? $job->url : null)
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"></head>
<body style="margin:0;background:#f4f5f7;font-family:Arial,sans-serif;color:#111827">
    <div style="max-width:660px;margin:20px auto;padding:24px;background:#fff;border:1px solid #e5e7eb">
        <p style="font-size:12px;color:#526276;text-transform:uppercase;margin:0">Nova vaga {{ $job->level }}</p>
        <h1 style="font-size:22px;margin:8px 0 4px">{{ $job->title }}</h1>
        <p style="margin:0 0 16px;color:#526276">{{ $job->company }} · {{ $job->location }} · {{ $job->work_mode }}</p>
        <p><strong>Aderência:</strong> {{ $job->score }}/100</p>
        <div style="height:6px;background:#e5e7eb;margin:0 0 20px"><div style="height:6px;width:{{ $job->score }}%;background:#111827"></div></div>
        @if (!empty($source['descricao_vaga']))
            <h2 style="font-size:15px">Descrição da vaga</h2>
            <p style="white-space:pre-line">{{ $source['descricao_vaga'] }}</p>
        @endif
        @if (!empty($source['requisitos_tecnicos']))
            <h2 style="font-size:15px">Requisitos</h2>
            <p style="white-space:pre-line">{{ $source['requisitos_tecnicos'] }}</p>
        @endif
        @if (!empty($source['tecnologias']))
            <p><strong>Tecnologias:</strong> {{ implode(', ', $source['tecnologias']) }}</p>
        @endif
        <p><strong>Resumo:</strong> {{ $analysis['resumo'] ?? '' }}</p>
        <p><strong>Por que combina:</strong> {{ $analysis['motivo'] ?? '' }}</p>
        <h2 style="font-size:15px">Pontos fortes</h2>
        <ul>@foreach (($analysis['pontos_fortes'] ?? []) as $point)<li>{{ $point }}</li>@endforeach</ul>
        <h2 style="font-size:15px">Pontos de atenção</h2>
        <ul>@foreach (($analysis['pontos_atencao'] ?? []) as $point)<li>{{ $point }}</li>@endforeach</ul>
        <p><strong>Preparação:</strong> {{ $analysis['sugestao_preparacao'] ?? '' }}</p>
        @if ($link)
            <p><a href="{{ $link }}" style="display:inline-block;background:#111827;color:#fff;padding:10px 16px;text-decoration:none">Abrir vaga</a></p>
        @endif
        <p style="font-size:12px;color:#526276">Seu currículo personalizado está disponível no VagaFlow e será gerado somente quando você abri-lo.</p>
    </div>
</body>
</html>
