<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case Open = 'open';
    case DueSoon = 'due_soon';
    case Overdue = 'overdue';
    case Paid = 'paid';
}
