<?php

namespace Toramanlis\Tests\Unit\Generator;

use Exception;
use Illuminate\Support\Fluent;
use PHPUnit\Framework\MockObject\Stub;
use ReflectionClass;
use ReflectionMethod;
use stdClass;
use Toramanlis\ImplicitMigrations\Attributes\IndexType;
use Toramanlis\ImplicitMigrations\Blueprint\BlueprintDiff;
use Toramanlis\ImplicitMigrations\Blueprint\Manager;
use Toramanlis\ImplicitMigrations\Blueprint\SimplifyingBlueprint;
use Toramanlis\ImplicitMigrations\Generator\MigrationGenerator;
use Toramanlis\Tests\Unit\BaseTestCase;

class MigrationGeneratorTest extends BaseTestCase
{
    public function testHandlesOffModel()
    {
        $this->mock(Manager::class)
            ->expects('applyRelationshipsToBlueprints')->once()->getMock()
            ->expects('ensureIndexColumns')->once()->getMock()
            ->expects('getRelationshipMap')->once()->andReturn([])->getMock()
            ->expects('getBlueprints')->once()->andReturn([])->getMock();

        $generator = $this->make(MigrationGenerator::class, ['existingMigrations' => []]);
        $generator->generate([stdClass::class]);

        $this->addToAssertionCount(1);
    }

    public function testHandlesForeignKeys()
    {
        $a = new SimplifyingBlueprint('as');
        $a->id();
        $a->integer('b_id');
        $a->foreign('b_id', 'a_b_id')->on('bs')->references(['id']);

        $_b = new SimplifyingBlueprint('bs');
        $b = new BlueprintDiff(
            new SimplifyingBlueprint('bs'),
            $_b,
            [],
            [],
            [$_b->id(), $_b->integer('c_id'), $_b->integer('d_id')],
            [],
            [],
            [
                $_b->index(['c_id'], 'b_c_i'),
                $_b->foreign('c_id', 'b_c_id')->on('cs')->references(['id']),
                $_b->foreign('d_id', 'b_d_id')->on('ds')->references(['id']),
            ]
        );

        $c = new SimplifyingBlueprint('cs');
        $c->id();
        $c->integer('a_id');
        $c->foreign('a_id', 'c_a_id')->on('as')->references(['id']);

        $d = new SimplifyingBlueprint('ds');
        $d->id();
        $d->integer('a_id');
        $d->integer('b_id');
        $d->foreign('a_id', 'd_a_id')->on('as')->references(['id']);
        $d->foreign('b_id', 'd_b_id')->on('bs')->references(['id']);


        $generator = $this->make(MigrationGenerator::class, ['existingMigrations' => []]);
        $reflector = new ReflectionMethod(MigrationGenerator::class, 'sortMigrations');
        $reflector->setAccessible(true);

        $sorted = $reflector->invoke($generator, ['as' => $a, 'bs' => $b, 'cs' => $c, 'ds' => $d]);


        $this->assertEquals(['bs', 'as', 'ds', 'cs', '_bs', '_ds'], array_keys($sorted));

        $this->assertEquals('as', $sorted['as']->getTable());
        $this->assertEquals('bs', $sorted['bs']->from->getTable());
        $this->assertEquals('bs', $sorted['bs']->to->getTable());
        $this->assertCount(1, $sorted['bs']->addedIndexes);
        $this->assertEquals('cs', $sorted['cs']->getTable());
        $this->assertEquals('ds', $sorted['ds']->getTable());
        $this->assertEquals('bs', $sorted['_bs']->from->getTable());
        $this->assertEquals('bs', $sorted['_bs']->to->getTable());
        $this->assertEquals(
            ['foreign', 'ds'],
            [$sorted['_bs']->addedIndexes[0]->name, $sorted['_bs']->addedIndexes[0]->on]
        );
        $this->assertEquals(
            ['foreign', 'as'],
            [$sorted['_ds']->addedIndexes[0]->name, $sorted['_ds']->addedIndexes[0]->on]
        );
    }

    public function testBlueprintThrowExceptionOnUnrecognizedForeignKey()
    {
        $blueprint = new SimplifyingBlueprint('bs');
        $blueprint->id();
        $blueprint->integer('c_id');
        $blueprint->foreign('c_id', 'b_c_id')->on('cs')->references(['id']);

        $this->expectException(Exception::class);
        $blueprint->extractForeignKey('c', 'a');
    }

    public function testBlueprintDiffThrowExceptionOnUnrecognizedForeignKey()
    {
        $to = new SimplifyingBlueprint('as');
        $blueprint = new BlueprintDiff(
            new SimplifyingBlueprint('as'),
            $to,
            [],
            [],
            [$to->id(), $to->integer('b_id')],
            [],
            [],
            [$to->index(['b_id'], 'a_b_i'), $to->foreign('b_id', 'a_b_id')->on('bs')->references(['id'])]
        );

        $this->expectException(Exception::class);
        $blueprint->extractForeignKey('d', 'a');
    }
}
