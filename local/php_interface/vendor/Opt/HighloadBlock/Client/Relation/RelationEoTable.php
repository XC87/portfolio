<?php

namespace Opt\HighloadBlock\Client\Relation;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

class RelationEoTable extends \Bitrix\Main\Entity\DataManager
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;

    public static function getTableName()
    {
        return 'hl_client_relation';
    }

    public static function getObjectClass()
    {
        return \Opt\HighloadBlock\Client\Relation\RelationEo::class;
    }

    public static function getCollectionClass()
    {
        return \Opt\HighloadBlock\Client\Relation\RelationEoCollection::class;
    }

    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
            ),
            'UF_DATE_CREATE' => array(
                'data_type' => 'datetime',
                'default_value' => function () {
                    return new \Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s');
                },
            ),
            'UF_DATE_MODIFY' => array(
                'data_type' => 'datetime',
                'default_value' => function () {
                    return new \Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s');
                },
            ),
            'UF_CLIENT_ID' => array(
                'data_type' => 'integer',
            ),
            'UF_LOGIN' => array(
                'data_type' => 'text',
            ),
            // клиент
            'CLIENT' => (new Reference(
                'CLIENT', \Opt\HighloadBlock\Client\ClientEoTable::class, Join::on('this.UF_CLIENT_ID', 'ref.ID')
            ))->configureJoinType('LEFT'),
            // пользователь
            'USER' => (new Reference(
                'USER', \Bitrix\Main\UserTable::class, Join::on('this.UF_LOGIN', 'ref.LOGIN')
            ))->configureJoinType('LEFT'),
        );
    }

    public static function onBeforeUpdate(\Bitrix\Main\Entity\Event $obEvent)
    {
        $obResult = new \Bitrix\Main\Entity\EventResult;
        $arFieldList = $obEvent->getParameter("fields");
        $arFieldList['UF_DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
        $obResult->modifyFields($arFieldList);
        return $obResult;
    }
}
