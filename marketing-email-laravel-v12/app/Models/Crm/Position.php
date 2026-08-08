<?php

namespace App\Models\Crm;

use App\Enums\Crm\PositionAuthority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    protected $table = 'positions';

    protected $fillable = [
        'title',
        'authority_level',
        'department_id',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'authority_level' => PositionAuthority::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function grantsDepartmentManagerAuthority(): bool
    {
        return $this->authority_level?->isDepartmentManager() ?? false;
    }

    /**
     * Gợi ý chức danh chuẩn. Người quản trị vẫn có thể nhập chức danh khác.
     * Tên chức danh không chứa tên phòng ban.
     *
     * @return array<int, string>
     */
    public static function titleSuggestions(): array
    {
        return [
            'Giám đốc',
            'Phó Giám đốc',
            'Trưởng phòng',
            'Phó phòng',
            'Trưởng nhóm',
            'Chuyên viên cao cấp',
            'Chuyên viên',
            'Nhân viên',
            'Thực tập sinh',
            'Cộng tác viên',
        ];
    }
}
