<?php

namespace Toramanlis\ImplicitMigrations\Generator;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\DirectRelationship;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\IndirectRelationship;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\MorphicDirectRelationship;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\MorphicIndirectRelationship;
use Toramanlis\ImplicitMigrations\Attributes\Exception;

class RelationshipResolver
{
    public static function resolve(Relation $relation): array
    {
        if ($relation instanceof HasOneOrMany) {
            return [static::resolveHasOneOrMany($relation)];
        } elseif ($relation instanceof HasOneOrManyThrough) {
            return static::resolveHasOneOrManyThrough($relation);
        } elseif ($relation instanceof BelongsTo) {
            return [static::resolveBelongsTo($relation)];
        } elseif ($relation instanceof BelongsToMany) {
            return [static::resolveBelongsToMany($relation)];
        } else {
            throw new Exception(Exception::CODE_RL_UNKNOWN, [$relation::class]);
        }
    }

    protected static function resolveHasOneOrMany(HasOneOrMany $relation): DirectRelationship
    {
        if ($relation instanceof MorphOneOrMany) {
            /** @var MorphicDirectRelationship */
            $migratable = App::make(MorphicDirectRelationship::class);
            $migratable->setTypeKey($relation->getMorphType());
        } else {
            /** @var DirectRelationship */
            $migratable = App::make(DirectRelationship::class);
        }

        $relatedModel = $relation->getRelated();
        $migratable->setRelatedTable((new $relatedModel())->getTable());
        $migratable->setForeignKey($relation->getForeignKeyName());

        $parentModel = $relation->getParent();
        $migratable->setParentTable((new $parentModel())->getTable());
        $migratable->setLocalKey($relation->getLocalKeyName());

        return $migratable;
    }

    protected static function resolveHasOneOrManyThrough(HasOneOrManyThrough $relation): array
    {
        $throughModel = $relation->getParent();
        $relatedModel = $relation->getRelated();
        $farParentTable = explode('.', $relation->getQualifiedLocalKeyName())[0];

        return [
            App::make(DirectRelationship::class, [
                'parentTable' => (new $throughModel())->getTable(),
                'relatedTable' => (new $relatedModel())->getTable(),
                'foreignKey' => $relation->getForeignKeyName(),
                'localKey' => $relation->getSecondLocalKeyName()

            ]),
            App::make(DirectRelationship::class, [
                'parentTable' => $farParentTable,
                'relatedTable' => (new $throughModel())->getTable(),
                'foreignKey' => $relation->getFirstKeyName(),
                'localKey' => $relation->getLocalKeyName()
            ]),
        ];
    }

    protected static function resolveBelongsTo(BelongsTo $relation): DirectRelationship
    {
        if ($relation instanceof MorphTo) {
            /** @var MorphicDirectRelationship */
            $migratable = App::make(MorphicDirectRelationship::class);
            $migratable->setTypeKey($relation->getMorphType());
        } else {
            /** @var DirectRelationship */
            $migratable = App::make(DirectRelationship::class);

            $relatedModel = $relation->getRelated();
            $migratable->setParentTable((new $relatedModel())->getTable());
        }

        $parentModel = $relation->getParent();
        $migratable->setRelatedTable((new $parentModel())->getTable());

        $migratable->setForeignKey($relation->getForeignKeyName());

        $localKey = $relation->getOwnerKeyName();
        if (null !== $localKey) {
            $migratable->setLocalKey($localKey);
        }

        return $migratable;
    }

    protected static function resolveBelongsToMany(BelongsToMany $relation): IndirectRelationship
    {
        if ($relation instanceof MorphToMany) {
            /** @var MorphicIndirectRelationship */
            $migratable = App::make(MorphicIndirectRelationship::class);
            $migratable->setTypeKey($relation->getMorphType());
        } else {
            /** @var IndirectRelationship */
            $migratable = App::make(IndirectRelationship::class);
        }

        $migratable->setPivotTable($relation->getTable());

        $parentModel = $relation->getParent();
        $parentTable = (new $parentModel())->getTable();

        $migratable->addRelatedTable($parentTable);
        $migratable->addForeignKey($parentTable, $relation->getForeignPivotKeyName());
        $migratable->addLocalKey($parentTable, $relation->getParentKeyName());

        $relatedModel = $relation->getRelated();
        $relatedTable = (new $relatedModel())->getTable();

        $migratable->addRelatedTable($relatedTable);
        $migratable->addForeignKey($relatedTable, $relation->getRelatedPivotKeyName());
        $migratable->addLocalKey($relatedTable, $relation->getRelatedKeyName());

        $migratable->setPivotColumns($relation->getPivotColumns());

        return $migratable;
    }
}
