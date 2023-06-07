<?php

namespace Opt\HighloadBlock\Cluster\Collections\Facets;

/**
 * @mixin \Bitrix\Main\ORM\Objectify\EntityObject
 */
class UfPhotoEo extends EO_UfPhotoEo
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
}