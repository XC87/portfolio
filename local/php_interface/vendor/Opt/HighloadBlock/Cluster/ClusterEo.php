<?php

namespace Opt\HighloadBlock\Cluster;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;

/**
 * @method getUfClusterId
 * @method getUfProducts
 * @method getUfFacets
 * @method getUfMainFacet
 * @method getUfPhotoFacet
 * @method setUfClusterId(int $clusterId)
 * @method setUfProducts(array $arProducts)
 * @method setUfFacets(array $arFacets)
 * @method setUfMainFacet(int $mainFacetId)
 * @method setUfRubricXmlId(int $rubricXmlId)
 * @method setUfPhotoFacet(array $photoFacetList)
 * @mixin EntityObject
 */
class ClusterEo extends EO_ClusterEo
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;

    /**
     * Сравнивает текущий объект с переданным объектом по значениям полей
     *
     * @param ClusterEo $obCluster
     *
     * @return bool
     * @throws ArgumentException|SystemException
     */
    public function isEqual(ClusterEo $obCluster): bool
    {
        $arFieldList = ClusterEoTable::getMap();
        foreach ($arFieldList as $arValues) {
            if ($arValues instanceof Field
                && !$arValues instanceof \Bitrix\Main\ORM\Fields\Relations\Relation
                && !$arValues instanceof \Bitrix\Main\ORM\Fields\ExpressionField
            ) {
                $fieldXmlId = $arValues->getName();
                if ($this->get($fieldXmlId) !== $obCluster->get($fieldXmlId)) {
                    return false;
                }
            }
        }

        return true;
    }
}