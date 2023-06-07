<?php

namespace Opt\HighloadBlock\Cluster\Collections\Products;

/**
 * @mixin \Bitrix\Main\ORM\Objectify\EntityObject
 */
class UfProductsEo extends EO_UfProductsEo
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
}