<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiEmpHcis extends Model
{
    use HasFactory;

    protected $table = 'api_emp_hcis';

    protected $fillable = [
        'employee_id',
        'fullname',
        'email',
        'group_company',
        'office_area',
        'manager_l1_id',
        'manager_l2_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}