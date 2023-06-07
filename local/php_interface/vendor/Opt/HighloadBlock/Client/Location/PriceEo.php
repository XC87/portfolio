<?php

namespace Opt\HighloadBlock\Client\Location;

use Bitrix\Main\ORM\Objectify\EntityObject;

/**
 * @mixin EntityObject
 */
class PriceEo extends EO_PriceEo
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
}