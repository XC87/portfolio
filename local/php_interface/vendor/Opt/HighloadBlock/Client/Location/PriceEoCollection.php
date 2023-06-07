<?php

namespace Opt\HighloadBlock\Client\Location;

use Bitrix\Main\ORM\Objectify\Collection;

/**
 * @mixin Collection
 */
class PriceEoCollection extends EO_PriceEo_Collection
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
}