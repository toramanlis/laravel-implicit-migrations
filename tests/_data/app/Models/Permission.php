<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\PivotColumn;
use Toramanlis\ImplicitMigrations\Attributes\PivotTable;
use Toramanlis\ImplicitMigrations\Attributes\Table;

use function PHPSTORM_META\type;

#[Table]
class Permission extends Model
{
    #[PivotTable('incorrect_table_name', charset: 'idk')]
    #[PivotColumn('is_temporary', type: 'boolean')]
    #[PivotColumn('role_id')]
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
