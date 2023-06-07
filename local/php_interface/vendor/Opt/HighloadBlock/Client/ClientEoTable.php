<?php

namespace Opt\HighloadBlock\Client;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Opt\HighloadBlock\Bonus\Client\BonusClientEo;
use Opt\HighloadBlock\Catalog\Client\AssortmentEoTable;
use Opt\HighloadBlock\Helpers\Activity;
use Opt\HighloadBlock\Shops\Settings\SettingsEoTable;

class ClientEoTable extends DataManager
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
    use Activity;

    public static function getTableName()
    {
        return 'hl_client';
    }

    /**
     * Получение названия сущности
     * @return string
     */
    static function getEntityName(): string
    {
        return 'Client';
    }

    public static function getObjectClass()
    {
        return \Opt\HighloadBlock\Client\ClientEo::class;
    }

    public static function getCollectionClass()
    {
        return \Opt\HighloadBlock\Client\ClientEoCollection::class;
    }

    public static function getHighloadBlock()
    {
        return HighloadBlockTable::resolveHighloadblock(self::getEntityName());
    }

    public static function getMap()
    {
        $arMap = [
            'ID' => [
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
            ],
            'UF_UUID' => [
                'data_type' => 'string',
            ],
            'UF_DATE_CREATE' => [
                'data_type' => 'datetime',
                'default_value' => function () {
                    return new \Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s');
                },
            ],
            'UF_DATE_MODIFY' => [
                'data_type' => 'datetime',
                'default_value' => function () {
                    return new \Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s');
                },
            ],
            'UF_ACTIVE' => [
                'data_type' => 'integer',
            ],
            'UF_USER_ID' => [
                'data_type' => 'integer',
            ],
            'UF_ERP_ID' => [
                'data_type' => 'string',
            ],
            'UF_NO_MIN_PART' => [
                'data_type' => 'integer',
            ],
            'UF_NO_MIN_PART_FREE' => [
                'data_type' => 'integer',
            ],
            'UF_ACTION_TF_ID' => [
                'data_type' => 'integer',
            ],
            'UF_TF_ID' => [
                'data_type' => 'integer',
            ],
            'UF_NO_MIN_PART_DATE' => [
                'data_type' => 'datetime',
            ],
            'UF_READY_ORDER' => [
                'data_type' => 'boolean',
            ],
            'UF_READY_ORDER_DATE' => [
                'data_type' => 'datetime',
            ],
            // пользователь
            'USER' => (new Reference(
                'USER', \Opt\Main\User\UserEoTable::class, Join::on('this.UF_USER_ID', 'ref.ID')
            ))->configureJoinType('left'),
            // Формат торговли для акций
            'TF_ACTION' => (new Reference(
                'TF_ACTION', \Opt\HighloadBlock\TradeFormat\TradeFormatEoTable::class, Join::on('this.UF_ACTION_TF_ID', 'ref.ID')
            ))->configureJoinType('left'),
            // Формат торговли для заказов
            'TF' => (new Reference(
                'TF', \Opt\HighloadBlock\TradeFormat\TradeFormatEoTable::class, Join::on('this.UF_TF_ID', 'ref.ID')
            ))->configureJoinType('left'),
            // пользователь
            'USER_GROUP' => (new Reference(
                'USER_GROUP', \Bitrix\Main\UserGroupTable::class, Join::on('this.UF_USER_ID', 'ref.USER_ID')
            ))->configureJoinType('left'),
            // партнер
            'PARTNER' => (new Reference(
                'PARTNER', \Opt\HighloadBlock\Erp\Partner\PartnerEoTable::class, Join::on('this.UF_ERP_ID', 'ref.UF_ERP_ID')
            ))->configureJoinType('left'),
            // подчиненные
            'EMPLOYEE_COLLECTION' => (new OneToMany(
                'EMPLOYEE_COLLECTION', \Opt\HighloadBlock\Client\Employee\EmployeeEoTable::class, 'CLIENT'
            )),
            // бонусная программа
            'BONUS_CLIENT' => (new Reference(
                'BONUS_CLIENT', \Opt\HighloadBlock\Bonus\Client\BonusClientEoTable::class, Join::on('this.USER.ID', 'ref.UF_USER_ID')
            ))->configureJoinType('left'),
            // подчиненные
            'API_KEY_COLLECTION' => (new OneToMany(
                'API_KEY_COLLECTION', \Opt\HighloadBlock\Client\ApiKey\ApiKeyEoTable::class, 'CLIENT'
            )),
            // подчиненные
            'RELATION_COLLECTION' => (new OneToMany(
                'RELATION_COLLECTION', \Opt\HighloadBlock\Client\Relation\RelationEoTable::class, 'CLIENT'
            )),
            // магазины
            'SHOP_COLLECTION' => (new OneToMany(
                'SHOP_COLLECTION', \Opt\HighloadBlock\Shops\ShopsEoTable::class, 'CLIENT'
            )),
            // ассортиментные ограничекния клиента
            (new OneToMany(
                'CATALOG_CLIENT_ASSORTMENT_COLLECTION', AssortmentEoTable::class, 'CLIENT'
            ))->configureCascadeDeletePolicy(CascadePolicy::FOLLOW),
        ];
        return $arMap;
    }

    /**
     * @param int $userId
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getIdByUserId(int $userId = 0): int
    {
        global $USER;
        static $arClientIdList = [];

        if (empty($userId) && $USER->IsAuthorized()) {
            $userId = $USER->GetID();
        }
        if (!array_key_exists($userId, $arClientIdList) && !empty($userId)) {
            $obQuery = self::query();
            $obQuery->setSelect(['ID']);
            $obQuery->where('UF_USER_ID', $userId);
            $obQuery->setCacheTtl(1 * 24 * 60 * 60);
            $arQuery = $obQuery->exec()
                ->fetch();
            $arClientIdList[$userId] = intval($arQuery['ID']);
        }

        return $arClientIdList[$userId];
    }

    /**
     * @param \Bitrix\Main\Entity\Event $obEvent
     *
     * @return \Bitrix\Main\Entity\EventResult|void
     * @throws \Bitrix\Main\ObjectException
     */
    public static function onBeforeUpdate(\Bitrix\Main\Entity\Event $obEvent)
    {
        $obResult = new \Bitrix\Main\Entity\EventResult();
        BonusClientEo::clearBonusClientCache($obEvent, 'CLIENT_UPDATE');
        $arFieldList['UF_DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
        $obResult->modifyFields($arFieldList);
        return $obResult;
    }

    /**
     * @param \Bitrix\Main\Event $obEvent
     *
     * @throws \Bitrix\Main\SystemException
     */
    public static function onAfterUpdate(\Bitrix\Main\Entity\Event $obEvent)
    {
        if (array_key_exists('COMPANY', $_SESSION['SESS_AUTH'])) {
            unset($_SESSION['SESS_AUTH']['COMPANY']);
        }
    }

    public static function onBeforeDelete(\Bitrix\Main\ORM\Event $event)
    {
        $primary = $event->getParameter('id')['ID'];
        $obClient = self::getById($primary)
            ->fetchObject();
        $rsSettings = SettingsEoTable::query()
            ->setSelect(['ID'])
            ->where('UF_CLIENT_ID', $obClient->getId())
            ->exec();
        while ($arSettings = $rsSettings->fetch()) {
            SettingsEoTable::delete($arSettings['ID']);
        }
    }
}
