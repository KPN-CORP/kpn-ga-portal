<?php
// app/Traits/LogActivity.php
namespace App\Traits;

use App\Models\help_m\HelpLogSistem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogActivity
{
    public static function bootLogActivity()
    {
        static::created(function ($model) {
            self::logActivity('CREATED', $model);
        });
        
        static::updated(function ($model) {
            self::logActivity('UPDATED', $model);
        });
        
        static::deleted(function ($model) {
            self::logActivity('DELETED', $model);
        });
    }
    
    private static function logActivity($action, $model)
    {
        $log = new HelpLogSistem();
        $log->pengguna_id = Auth::id();
        $log->aksi = $action;
        $log->model = get_class($model);
        $log->model_id = $model->id;
        
        if ($action === 'UPDATED') {
            $log->data_lama = json_encode($model->getOriginal());
            $log->data_baru = json_encode($model->getAttributes());
        } else {
            $log->data_baru = json_encode($model->getAttributes());
        }
        
        $log->ip_address = Request::ip();
        $log->user_agent = Request::userAgent();
        $log->save();
    }
    
    public static function logCustomActivity($action, $data = [])
    {
        $log = new HelpLogSistem();
        $log->pengguna_id = Auth::id();
        $log->aksi = $action;
        $log->model = static::class;
        $log->data_baru = json_encode($data);
        $log->ip_address = Request::ip();
        $log->user_agent = Request::userAgent();
        $log->save();
    }
}