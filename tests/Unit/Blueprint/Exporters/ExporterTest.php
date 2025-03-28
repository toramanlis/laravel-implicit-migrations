<?php

namespace Toramanlis\Tests\Unit\Blueprint\Exporters;

use Toramanlis\ImplicitMigrations\Blueprint\Exporters\ColumnDiffExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\Exporter;
use Toramanlis\Tests\Unit\BaseTestCase;

class ExporterTest extends BaseTestCase
{
    public function testWrapsParameters()
    {
        /** @var ColumnDiffExporter */
        $exporter = $this->make(ColumnDiffExporter::class);
        $result = $exporter->renameColumn(
            'a_very_long_name_to_be_renamed_to_something_else',
            'another_very_long_name_to_rename_something_else_to'
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
}
