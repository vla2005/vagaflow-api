<?php

namespace App\Support;

class OpportunityTaxonomy
{
    public const LEVELS = [
        'estagio',
        'junior',
        'pleno',
        'senior',
        'especialista',
        'staff',
        'coordenador',
        'techlead',
        'diretor',
    ];

    public const AREAS = [
        'qa',
        'backend',
        'frontend',
        'fullstack',
        'mobile',
        'devops',
        'security',
        'ia',
        'produto',
        'cx',
        'dados',
        'geral',
    ];

    public const PLATFORMS = [
        'linkedin',
        'gupy',
        'solides',
        'greenhouse',
        'inhire',
        'empregos',
        'catho',
        'quickin',
        'indeed',
        'trampos',
    ];

    public const EMPLOYMENT_TYPES = ['clt', 'pj'];
}
