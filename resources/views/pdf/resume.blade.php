<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24mm 17mm; }
        body { font-family: "DejaVu Sans", sans-serif; color: #171717; font-size: 9pt; line-height: 1.4; }
        h1 { text-align: center; font-size: 17pt; margin: 0 0 3mm; }
        .role, .contact { text-align: center; margin: 0 0 2mm; }
        .contact { font-size: 7.5pt; }
        h2 { font-size: 11pt; border-bottom: 1px solid #444; margin: 5mm 0 2mm; padding-bottom: 1mm; }
        h3 { font-size: 9pt; margin: 3mm 0 1mm; }
        p { margin: 1mm 0; }
        ul { margin: 1mm 0 2mm; padding-left: 5mm; }
        li { margin-bottom: 1mm; }
        .period { font-weight: normal; color: #555; }
    </style>
</head>
<body>
    <h1>{{ $resume['cabecalho']['nome'] ?? $user->name }}</h1>
    @if (!empty($resume['cabecalho']['titulo']))
        <p class="role">{{ $resume['cabecalho']['titulo'] }}</p>
    @endif
    <p class="contact">{{ $resume['cabecalho']['contato'] ?? $user->email }}</p>

    <h2>Resumo Profissional</h2>
    <p>{{ $resume['resumo_profissional'] ?? '' }}</p>

    <h2>Competências</h2>
    @foreach (($resume['competencias'] ?? []) as $skill)
        <p><strong>{{ $skill['categoria'] ?? '' }}:</strong> {{ $skill['itens'] ?? '' }}</p>
    @endforeach

    <h2>Experiência Profissional</h2>
    @foreach (($resume['experiencias'] ?? []) as $experience)
        <h3>{{ $experience['titulo'] ?? '' }} <span class="period">| {{ $experience['periodo'] ?? '' }}</span></h3>
        <ul>@foreach (($experience['pontos'] ?? []) as $point)<li>{{ $point }}</li>@endforeach</ul>
    @endforeach

    <h2>Projetos Pessoais / Acadêmicos</h2>
    @foreach (($resume['projetos'] ?? []) as $project)
        <h3>{{ $project['titulo'] ?? '' }} <span class="period">| {{ $project['periodo'] ?? '' }}</span></h3>
        <ul>@foreach (($project['pontos'] ?? []) as $point)<li>{{ $point }}</li>@endforeach</ul>
    @endforeach

    @if (!empty($resume['educacao']))
        <h2>Educação</h2>
        @foreach ($resume['educacao'] as $education)<p>{{ $education }}</p>@endforeach
    @endif
</body>
</html>
