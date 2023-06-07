<?php

namespace Opt\HighloadBlock\Cluster\Collections\Facets;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Opt\HighloadBlock\Cluster\ClusterEoTable;
use Opt\HighloadBlock\Helpers\AbstractUfTable;

class UfFacetsEoTable extends AbstractUfTable
{
    public static function getTableName()
    {
        return 'hl_clusters_uf_facets';
    }

    public static function getMap()
    {
        return [
            ...parent::getMap(),
            new Reference(
                'CLUSTER', ClusterEoTable::class, Join::on('this.ID', 'ref.UF_CLUSTER_ID')
            ),
        ];
    }
}