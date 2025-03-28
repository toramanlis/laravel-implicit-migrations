<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Toramanlis\ImplicitMigrations\Attributes\PivotColumn;
use Toramanlis\ImplicitMigrations\Attributes\Relationship;
use Toramanlis\ImplicitMigrations\Attributes\Table;

use function PHPSTORM_META\type;

#[Table]
class Permission extends Model
{
    /**
     * @relationship
    */
    #[PivotColumn('is_temporary', type: 'boolean')]
    #[PivotColumn('role_id')]
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
