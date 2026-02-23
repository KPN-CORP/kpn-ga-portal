<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessMenu extends Model
{
    protected $table = 'tb_access_menu';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'username',
        'request_idcard',
        'list_idcard',
        'detail_idcard',
        'proses_idcard',
        'request_messenger',
        'status_messenger',
        'detail_messenger',
        'akses_messenger_all',
        'akses_messenger',
        'proses_messenger',
        'emp_index',
        'emp_show',
        'emp_edit',
        'mailing_list',
        'mailing_input',
        'mailing_edit',
        'mailing_proses',
        'ga_help_proses',
        'ga_help_full_akses'
    ];
    
    protected $casts = [
        'request_idcard' => 'boolean',
        'list_idcard' => 'boolean',
        'detail_idcard' => 'boolean',
        'proses_idcard' => 'boolean',
        'request_messenger' => 'boolean',
        'status_messenger' => 'boolean',
        'detail_messenger' => 'boolean',
        'akses_messenger_all' => 'boolean',
        'akses_messenger' => 'boolean',
        'proses_messenger' => 'boolean',
        'emp_index' => 'boolean',
        'emp_show' => 'boolean',
        'emp_edit' => 'boolean',
        'mailing_list' => 'boolean',
        'mailing_input' => 'boolean',
        'mailing_edit' => 'boolean',
        'mailing_proses' => 'boolean',
        'ga_help_proses' => 'boolean',
        'ga_help_full_akses'=> 'boolean',
    ];
}