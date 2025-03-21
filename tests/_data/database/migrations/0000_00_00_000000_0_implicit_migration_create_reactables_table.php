<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'reactables';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Reaction::variations';

    public function tableUp(Blueprint $table): void
    {
        $table->unsignedBigInteger('reactable_id');
        $table->unsignedBigInteger('reaction_id');
        $table->string('reactable_type');

        $table->foreign('reactable_id', 'reactables_comment_id_foreign')->on('comments')->references('id');
        $table->foreign('reaction_id', 'reactables_reaction_id_foreign')->on('reactions')->references('id');
        $table->foreign('reactable_id', 'reactables_variation_id_foreign')->on('variations')->references('id');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
