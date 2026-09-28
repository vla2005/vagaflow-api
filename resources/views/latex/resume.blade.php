\documentclass[a4paper,10pt]{article}
\usepackage[utf8]{inputenc}
\usepackage[T1]{fontenc}
\usepackage[brazil]{babel}
\usepackage[scaled]{helvet}
\renewcommand{\familydefault}{\sfdefault}
\usepackage[
  top=0.9cm,
  bottom=0.9cm,
  left=1cm,
  right=1cm
]{geometry}
\usepackage{parskip}
\usepackage{titlesec}
\usepackage{enumitem}
\pagestyle{empty}
\setcounter{secnumdepth}{0}
\setlength{\parindent}{0pt}
\setlength{\parskip}{2pt}
\titleformat{\section}
{\large\bfseries}
{}
{0em}
{}
[\titlerule\vspace{0.2ex}]
\titlespacing*{\section}{0pt}{7pt}{3pt}
\titleformat{\subsection}
{\normalsize}
{}
{0em}
{}
\titlespacing*{\subsection}{0pt}{4pt}{1pt}
\setlist[itemize]{leftmargin=1.5em, itemsep=0.8pt, parsep=0pt, partopsep=0pt, topsep=2pt}
\begin{document}
\fontsize{8.8pt}{10.5pt}\selectfont
@php
    $identity = $resume['identity'] ?? [];
    $contact = array_values(array_filter([
        $identity['location'] ?? null,
        $identity['phone'] ?? null,
        $identity['email'] ?? null,
        ...($identity['links'] ?? []),
    ]));
@endphp
\begin{center}
    {\LARGE \textbf{{!! $identity['name'] ?? '' !!}}} \\[0.12cm]
    @if (!empty($identity['title'])){!! $identity['title'] !!} \\[0.03cm]@endif
    {!! implode(' \\textbullet\ ', $contact) !!}
\end{center}
\vspace{-0.1cm}

\section{Resumo Profissional}
{!! $resume['summary'] ?? '' !!}

@if (!empty($resume['skills']))
\section{Competências}
@foreach ($resume['skills'] as $skill)
\textbf{{!! $skill['category'] !!}:} {!! implode(', ', $skill['items']) !!}\\
@endforeach
@endif

@if (!empty($resume['experiences']))
\section{Experiência Profissional}
@foreach ($resume['experiences'] as $experience)
\subsection*{\textbf{{!! $experience['organization'] !!} -- {!! $experience['role'] !!}}@if (!empty($experience['location'])) \hfill {!! $experience['location'] !!}@endif}
\textit{{!! $experience['period'] !!}}
\begin{itemize}
@foreach ($experience['bullets'] as $bullet)
    \item {!! $bullet !!}
@endforeach
\end{itemize}
@endforeach
@endif

@if (!empty($resume['projects']))
\section{Projetos}
@foreach ($resume['projects'] as $project)
\subsection*{\textbf{{!! $project['name'] !!}}@if (!empty($project['period'])) \hfill {!! $project['period'] !!}@endif}
@if (!empty($project['link'])){!! $project['link'] !!}\\@endif
\begin{itemize}
@foreach ($project['bullets'] as $bullet)
    \item {!! $bullet !!}
@endforeach
\end{itemize}
@endforeach
@endif

@if (!empty($resume['education']))
\section{Educação}
@foreach ($resume['education'] as $education)
\subsection*{\textbf{{!! $education['institution'] !!}}@if (!empty($education['location'])) \hfill {!! $education['location'] !!}@endif}
{!! $education['course'] !!}@if (!empty($education['period'])) \hfill \textit{{!! $education['period'] !!}}@endif
@if (!empty($education['details']))
\begin{itemize}
@foreach ($education['details'] as $detail)
    \item {!! $detail !!}
@endforeach
\end{itemize}
@endif
@endforeach
@endif

@foreach (($resume['optional_sections'] ?? []) as $section)
\section{!! '{'.$section['title'].'}' !!}
@foreach ($section['items'] as $item)
\subsection*{\textbf{{!! $item['heading'] !!}}@if (!empty($item['period'])) \hfill {!! $item['period'] !!}@endif}
@if (!empty($item['subheading'])){!! $item['subheading'] !!}@endif
@if (!empty($item['details']))
\begin{itemize}
@foreach ($item['details'] as $detail)
    \item {!! $detail !!}
@endforeach
\end{itemize}
@endif
@endforeach
@endforeach

\end{document}
