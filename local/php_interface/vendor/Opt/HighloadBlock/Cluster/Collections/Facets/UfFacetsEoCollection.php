<?php

namespace Opt\HighloadBlock\Cluster\Collections\Facets;

use Bitrix\Main\ORM\Objectify\Collection;

/**
 * @mixin Collection
 */
class UfFacetsEoCollection extends EO_UfFacetsEo_Collection
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
}