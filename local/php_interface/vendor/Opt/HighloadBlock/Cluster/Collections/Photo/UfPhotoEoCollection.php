<?php

namespace Opt\HighloadBlock\Cluster\Collections\Facets;

use Bitrix\Main\ORM\Objectify\Collection;

/**
 * @mixin Collection
 */
class UfPhotoEoCollection extends EO_UfPhotoEo_Collection
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
}