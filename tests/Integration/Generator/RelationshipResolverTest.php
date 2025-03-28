<?php

namespace Toramanlis\Tests\Integration\Generator;

use Toramanlis\Tests\Integration\BaseTestCase;

class RelationshipResolverTest extends BaseTestCase
{
    public function testBelongsTo()
    {
        $this->carryModels(['Item.php', 'Order.php', 'User.php']);
        $this->generate();
        $this->expectMigration('create_orders_table');
    }

    public function testBelongsToMany()
    {
        $this->carryModels(['User.php', 'Role.php', 'Permission.php']);
        $this->generate();
        $this->expectMigration('create_permissions_table');
        $this->expectMigration('create_permission_role_table');
    }

    public function testHasOne()
    {
        $this->getApp()->config->set('database.auto_infer_migrations', true);

        $this->carryModels(['Coupon.php', 'Promotion.php']);

        $this->generate();

        $this->expectMigration(
            'create_coupons_table',
            '0000_00_00_000000_0_implicit_migration_create_coupons_table_with_promotions.php'
        );
    }

    public function testHasMany()
    {
        $this->carryModels(['User.php', 'Role.php']);
        $this->generate();
        $this->expectMigration(
            'create_users_table',
            '0000_00_00_000000_0_implicit_migration_create_users_table_with_roles.php'
        );
    }

    public function testHasOneOrManyThrough()
    {
        $this->carryModels(['Profile.php', 'User.php', 'Store.php', 'Coupon.php']);
        $this->generate();
        $this->expectMigration('create_profiles_table');
        $this->expectMigration(
            'create_users_table',
            '0000_00_00_000000_0_implicit_migration_create_users_table_with_profiles.php'
        );
        $this->expectMigration('create_stores_table');
        $this->expectMigration('create_coupons_table');
    }

    public function testMorphsToOne()
    {
        $this->carryModels(['Comment.php', 'Category.php', 'Variant.php', 'Description.php']);
        $this->generate();
        $this->expectMigration('create_comments_table');
        $this->expectMigration('create_descriptions_table');
    }

    public function testMorphsToMany()
    {
        $this->carryModels(['Comment.php', 'Variation.php', 'Reaction.php']);
        $this->generate();
        $this->expectMigration('create_reactables_table');
    }
}
