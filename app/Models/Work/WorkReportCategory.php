<?php

namespace App\Models\Work;

use Illuminate\Database\Eloquent\Model;

class WorkReportCategory extends Model
{
    protected $table = 'work_report_categories';

    protected $fillable = ['name', 'description'];

    public function reports()
    {
        return $this->hasMany(WorkReport::class, 'category_id');
    }
}