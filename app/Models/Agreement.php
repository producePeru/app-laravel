<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'office',
        'nameInstitution',
        'component',
        'responsible',
        'representative',
        'representativeEmail',
        'addendum',
        'proponent',
        'nameAgreement',
        'focalPoint',
        'phoneContact',
        'pdfDocument',
        'dateIssue',
        'effectiveDate',
        'dueDate',
        'created_by'
    ];
}
