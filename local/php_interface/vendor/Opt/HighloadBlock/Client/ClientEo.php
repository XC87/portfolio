<?php

namespace Opt\HighloadBlock\Client;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Opt\Main\Cache;
use Opt\Main\Orm;

/**
 * @method getUfActionTfId
 * @method setUfUserId(int $id)
 * @method getPartner()
 * @method isUfActiveChanged
 * @method isUfActionTfIdChanged
 * @method isUfTfIdChanged
 * @mixin EntityObject
 */
class ClientEo extends EO_ClientEo
{
    use Cache;
    use Orm;

    public static function getByUserId(int $userId): ?self
    {
        $obQuery = ClientEoTable::query();
        $obQuery->where('UF_USER_ID', $userId);
        $obQuery->setSelect(['*']);
        $rsQuery = $obQuery->exec();
        $obClient = $rsQuery->fetchObject();
        return $obClient;
    }
}
