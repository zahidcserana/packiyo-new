<?php

namespace App\Models\Automations;

/**
 * Incoterms for automation order text fields.
 *
 * Incoterm negotiated for shipment as specified by EasyPost.
 * Setting this value to anything other than "DDP" will pass the cost and responsibility of duties
 * on to the recipient of the package(s), as specified by Incoterms rules.
 */
enum Incoterms: string
{
    case EXW = 'EXW';
    case FCA = 'FCA';
    case CPT = 'CPT';
    case CIP = 'CIP';
    case DAT = 'DAT';
    case DAP = 'DAP';
    case DDP = 'DDP';
    case FAS = 'FAS';
    case FOB = 'FOB';
    case CFR = 'CFR';
    case CIF = 'CIF';
}
