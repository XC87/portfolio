<?php

namespace Opt\HighloadBlock\Cluster\Collections\Products;

use Bitrix\Main\ORM\Objectify\Collection;

/**
 * @mixin Collection
 */
class UfProductsEoCollection extends EO_UfProductsEo_Collection
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
}