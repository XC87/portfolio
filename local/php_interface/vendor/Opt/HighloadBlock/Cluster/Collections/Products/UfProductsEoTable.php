<?php

namespace Opt\HighloadBlock\Cluster\Collections\Products;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Opt\HighloadBlock\Cluster\ClusterEoTable;

class UfProductsEoTable extends \Opt\HighloadBlock\Helpers\AbstractUfTable
{
    public static function getTableName()
    {
        return 'hl_clusters_uf_products';
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