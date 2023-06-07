<?php

namespace Opt\HighloadBlock\Cluster\Collections\Facets;

/**
 * @mixin \Bitrix\Main\ORM\Objectify\EntityObject
 */
class UfFacetsEo extends EO_UfFacetsEo
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
}