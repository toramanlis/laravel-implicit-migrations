<?php

namespace Toramanlis\Tests\Unit\Blueprint\Exporters;

use ReflectionMethod;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\ColumnDiffExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\ColumnExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\Exporter;
use Toramanlis\Tests\Unit\BaseTestCase;

class ExporterTest extends BaseTestCase
{
    public function testWrapsParameters()
    {
        /** @var ColumnDiffExporter */
        $exporter = $this->make(ColumnDiffExporter::class);
        $result = $exporter->renameColumn(
            'a_very_long_name_to_be_renamed_to_something_else_so_that_it_wraps_to_the_next_line',
            'another_very_long_name_to_rename_something_else_to_which_should_wrap_to_the_next_line'
        );

        $this->assertStringContainsString("\n", $result);
    }

    public function testWrapsVariables()
    {
        $result = Exporter::varExport([
            'this is not actually a variable',
            'but it just means value',
            'or maybe it can be assigned',
            'to a variable after being exported'
        ]);

        $this->assertStringContainsString("\n", $result);
    }

    public function testWrapsModifiers()
    {
        /** @var ColumnExporter */
        $exporter = $this->make(ColumnExporter::class);

        $reflectionMethod = new ReflectionMethod(ColumnExporter::class, 'exportMethodCall');
        $reflectionMethod->setAccessible(true);
        $result = $reflectionMethod->invoke(
            $exporter,
            'someLengthyMethodName',
            [
                'some_lengthy_string',
                'another_length_string'
            ],
            [
                '->someLengthyModifierMethodName("with_some_lengthy_parameters_of_its_own")'
            ]
        );

        $this->assertStringContainsString("\n\t->", $result);
    }

    public function testSortsParameters()
    {
        /** @var ColumnExporter */
        $exporter = $this->make(ColumnExporter::class);

        $reflectionMethod = new ReflectionMethod(ColumnExporter::class, 'exportParameters');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invoke($exporter, ['a', 'b', 'c' => 'd', 'e']);

        $this->assertEquals("'a', 'b', 'e', c: 'd'", $result);
    }
}
