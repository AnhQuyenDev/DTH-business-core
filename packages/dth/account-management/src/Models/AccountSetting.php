<?php
namespace Dth\AccountManagement\Models;

use Illuminate\Database\Eloquent\Model;

class AccountSetting extends Model
{
    protected $table = 'account_settings';
    protected $fillable = ['key', 'value', 'type', 'description'];
    protected function casts(): array { return ['value' => 'array']; }
}
