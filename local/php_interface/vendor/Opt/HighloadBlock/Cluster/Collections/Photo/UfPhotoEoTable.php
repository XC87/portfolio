<?php

namespace Opt\HighloadBlock\Cluster\Collections\Facets;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Opt\HighloadBlock\Cluster\ClusterEoTable;
use Opt\HighloadBlock\Helpers\AbstractUfTable;

class UfPhotoEoTable extends AbstractUfTable
{
    public static function getTableName()
    {
        return 'hl_clusters_uf_photo_facet';
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