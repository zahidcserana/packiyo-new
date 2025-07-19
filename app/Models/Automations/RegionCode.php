<?php

namespace App\Models\Automations;

/**
 * Region (continent) codes for automation order text fields.
 */
enum RegionCode: string
{
    case AFRICA  = '002';
    case OCEANIA = '009';
    case AMERICA = '019';
    case ASIA    = '142';
    case EUROPE  = '150';
    case OTHER   = '';
}
