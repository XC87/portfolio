<?php

namespace Opt\HighloadBlock\Cluster;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main;
use Opt\HighloadBlock\Cluster\Collections\Products\UfProductsEoTable;
use Opt\HighloadBlock\Helpers\AbstractUfTable;

/**
 * @mixin \Bitrix\Main\ORM\Data\DataManager
 */
class ClusterEoTable extends DataManager
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;

    public static function getEntityName()
    {
        return 'Clusters';
    }

    public static function getTableName()
    {
        return 'hl_clusters';
    }

    public static function getObjectClass()
    {
        return ClusterEo::class;
    }

    public static function getCollectionClass()
    {
        return ClusterEoCollection::class;
    }

    public static function getHighloadBlock()
    {
        return HighloadBlockTable::resolveHighloadblock(self::getEntityName());
    }

    public static function getMap()
    {
        return [
            'ID' => new Main\ORM\Fields\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            'UF_CLUSTER_ID' => new Main\ORM\Fields\IntegerField('UF_CLUSTER_ID'),
            'UF_MAIN_FACET' => new Main\ORM\Fields\IntegerField('UF_MAIN_FACET'),
            'UF_RUBRIC_XML_ID' => new Main\ORM\Fields\IntegerField('UF_RUBRIC_XML_ID'),
            ...AbstractUfTable::generateParentMap('UF_PHOTO_FACET', UfProductsEoTable::class),
            ...AbstractUfTable::generateParentMap('UF_FACETS', UfProductsEoTable::class),
            ...AbstractUfTable::generateParentMap('UF_PRODUCTS', UfProductsEoTable::class)
        ];
    }

}
