<?php

namespace Toramanlis\ImplicitMigrations\Blueprint;

enum IndexType: string
{
    case Index = 'index';
    case Primary = 'primary';
    case Unique = 'unique';
    case FullText = 'fulltext';
    case SpatialIndex = 'spatialindex';

    case Foreign = 'foreign';
}
