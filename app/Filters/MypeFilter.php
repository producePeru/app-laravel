<?php
namespace App\Filters;
use Illuminate\Http\Request;
use App\Filters\ApiFilter;


class CustomerFilter extends ApiFilter 
{
  protected $safeParams = [
    'ruc' => ['eq'],
    'social_reason' => ['eq'],
    'category' => ['eq'],
    'type' => ['eq'],
    'name_complete' => ['eq'],
    'dni_number' => ['eq']
  ];

  protected $columnMap = [
    'socialReason' => 'social_reason',
    'nameComplete' => 'name_complete',
  ];

  protected $operatorMap = [
    'eq' => '=',
    'lt' => '<',
    'lte' => '<=',
    'gt' => '>',
    'gte' => '>=',
  ];
}
