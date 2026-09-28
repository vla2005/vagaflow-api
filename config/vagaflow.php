<?php

return [
    'source_url' => env('VAGAFLOW_SOURCE_URL', 'https://meupadrinho.com.br/api'),
    'max_source_jobs_per_level' => (int) env('VAGAFLOW_MAX_SOURCE_JOBS_PER_LEVEL', 20),
    'gemini_key' => env('GEMINI_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    'gemini_fallback_model' => env('GEMINI_FALLBACK_MODEL'),
    'latex_binary' => env('LATEX_BINARY', 'pdflatex'),
];
