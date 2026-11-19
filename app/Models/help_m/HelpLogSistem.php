<?php
// app/Models/HelpLogSistem.php - SIMPLE VERSION
namespace App\Models\help_m;

use Illuminate\Database\Eloquent\Model;

class HelpLogSistem extends Model
{
    protected $table = 'db_help_log_sistem';
    
    // Jika tabel tidak ada, sebaiknya nonaktifkan dulu
    // Tapi untuk amannya, kita buat class kosong dulu
    
    public static function logCustomActivity($action, $data = [], $model = null, $modelId = null)
    {
        // Do nothing for now
        return null;
    }
    
    public static function logCrudActivity($action, $model, $oldData = null)
    {
        // Do nothing for now
        return null;
    }
}