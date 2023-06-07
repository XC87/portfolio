<?php

namespace Opt\HighloadBlock\Cluster;

use Bitrix\Main\ORM\Objectify\Collection;

/**
 * @mixin Collection
 */
class ClusterEoCollection extends EO_ClusterEo_Collection
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
}