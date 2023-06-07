<?php

namespace Opt\Main\User;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Opt\HighloadBlock\Client\ClientEoTable;
use Opt\HighloadBlock\Client\Employee\EmployeeEoTable;
use Opt\HighloadBlock\TradeFormat\TradeFormatEoTable;

/**
 * Class UserEoTable
 * @package Opt\Main\User
 */
class UserEoTable extends \Bitrix\Main\UserTable
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;

    public static function getObjectClass(): string
    {
        return UserEo::class;
    }

    public static function getCollectionClass(): string
    {
        return UserEoCollection::class;
    }

    /**
     * @inheritdoc
     */
    public static function getMap()
    {
        $arMap = [
            'CLIENT' => [
                'data_type' => ClientEoTable::class,
                'reference' => [
                    "=this.ID" => "ref.UF_USER_ID",
                ],
                'join_type' => 'LEFT',
            ],
            'EMPLOYEE' => [
                'data_type' => EmployeeEoTable::class,
                'reference' => [
                    "=this.ID" => "ref.UF_USER_ID",
                ],
                'join_type' => 'LEFT',
            ],
            'EMPLOYEE_CLIENT' => [
                'data_type' => ClientEoTable::class,
                'reference' => [
                    "=this.EMPLOYEE.UF_CLIENT_ID" => "ref.ID",
                ],
                'join_type' => 'LEFT',
            ],
            // код клиента в 1С
            'CLIENT_CODE_1C' => new Entity\ExpressionField(
                'CLIENT_CODE_1C', '%s', 'LOGIN', [
                    'fetch_data_modification' => function () {
                        return [
                            function ($value) {
                                if (preg_match("/^[A-Z]*([0-9]*)$/usi", $value, $matches)) {
                                    return $matches[1];
                                }
                            },
                        ];
                    },
                ]
            ),
            // Формат торговли
            'TF_ACTION' => [
                'data_type' => TradeFormatEoTable::class,
                'reference' => [
                    "=this.CLIENT.UF_ACTION_TF_ID" => "ref.ID",
                ],
                'join_type' => 'LEFT',
            ],
            'TF_ACTION_EMPLOYEE_CLIENT' => [
                'data_type' => TradeFormatEoTable::class,
                'reference' => [
                    "=this.EMPLOYEE_CLIENT.UF_ACTION_TF_ID" => "ref.ID",
                ],
                'join_type' => 'LEFT',
            ],
            'TF' => [
                'data_type' => TradeFormatEoTable::class,
                'reference' => [
                    "=this.CLIENT.UF_TF_ID" => "ref.ID",
                ],
                'join_type' => 'LEFT',
            ],
            'TF_EMPLOYEE_CLIENT' => [
                'data_type' => TradeFormatEoTable::class,
                'reference' => [
                    "=this.EMPLOYEE_CLIENT.UF_TF_ID" => "ref.ID",
                ],
                'join_type' => 'LEFT',
            ],
            // партнер
            'PARTNER' => (new Reference(
                'PARTNER',
                \Opt\HighloadBlock\Erp\Partner\PartnerEoTable::class,
                Join::on('this.XML_ID', 'ref.UF_ERP_ID')
            ))->configureJoinType('left'),
            // группа
            'USER_GROUP' => [
                'data_type' => \Bitrix\Main\UserGroupTable::getEntity(),
                'reference' => [
                    "=this.ID" => "ref.USER_ID",
                ],
            ],
            // ЦФО
            'USER_GROUP_CFO' => [
                'data_type' => \Bitrix\Main\UserGroupTable::getEntity(),
                'reference' => [
                    "=this.ID" => "ref.USER_ID",
                ],
            ],
            'CFO' => (new Reference(
                'CFO',
                \Bitrix\Iblock\ElementTable::class,
                Join::on('this.USER_GROUP_CFO.GROUP.STRING_ID', 'ref.CODE')
                    ->where('ref.IBLOCK_ID', IB_IDP)
                    ->where('ref.ACTIVE', 'Y')
            ))->configureJoinType('left'),
            // группа ЕРП
            'USER_GROUP_ERP_PARTNER' => (new Reference(
                'USER_GROUP_ERP_PARTNER',
                \Bitrix\Main\UserGroupTable::class,
                Query::filter()
                    ->whereColumn('this.ID', 'ref.USER_ID')
                    ->where(
                        'ref.GROUP_ID',
                        \Opt\Main\Group::getIdByCode(GROUP['ERP_PARTNER'])
                    )
            ))->configureJoinType('left'),
            'IS_ERP_PARTNER' => new ExpressionField(
                'IS_ERP_PARTNER', '%s IS NOT NULL', ['USER_GROUP_ERP_PARTNER.GROUP_ID']
            ),
            // группа клиент-управляющий
            'USER_GROUP_CLIENT_MANAGER' => (new Reference(
                'USER_GROUP_CLIENT_MANAGER',
                \Bitrix\Main\UserGroupTable::class,
                Query::filter()
                    ->whereColumn('this.ID', 'ref.USER_ID')
                    ->where(
                        'ref.GROUP_ID',
                        \Opt\Main\Group::getIdByCode(GROUP['CLIENT_MANAGER'])
                    )
            ))->configureJoinType('left'),
            'IS_CLIENT_MANAGER' => new ExpressionField(
                'IS_CLIENT_MANAGER', '%s IS NOT NULL', ['USER_GROUP_CLIENT_MANAGER.GROUP_ID']
            ),
            // группа отгрузка с РЦ
            (new Reference(
                'USER_GROUP_STOCK_RC',
                \Bitrix\Main\UserGroupTable::class,
                Query::filter()
                    ->whereColumn('this.ID', 'ref.USER_ID')
                    ->where(
                        'ref.GROUP_ID',
                        \Opt\Main\Group::getIdByCode(GROUP['STOCK_RC'])
                    )
            ))->configureJoinType('left'),
            new ExpressionField(
                'IS_STOCK_RC', '%s IS NOT NULL', ['USER_GROUP_STOCK_RC.GROUP_ID']
            ),
            'GROUP_ID' => (new Reference(
                'GROUP_ID', \Bitrix\Main\GroupTable::class, Join::on('this.USER_GROUP.GROUP_ID', 'ref.ID')
            )),
        ];

        return array_merge(parent::getMap(), $arMap);
    }

    public static function findManagerByLogin1C(string $login): ?array
    {
        $obQuery = self::query();
        $obQuery->setFilter([
            '=ACTIVE' => 'Y',
            '=UF_LOGIN1C' => $login
        ]);
        $obQuery->setSelect([
            'NAME',
            'LAST_NAME',
            'WORK_POSITION',
            'PERSONAL_PHONE',
            'PERSONAL_MOBILE',
            'WORK_MAILBOX',
            'PERSONAL_PHOTO'
        ]);
        $rsManager = $obQuery->exec();
        if ($arManager = $rsManager->fetch()) {
            return $arManager;
        } else {
            return null;
        }
    }

    public static function add($arData): bool
    {
        $obUser = new \CUser();
        return $obUser->add($arData);
    }

    public static function update($id, $arData): bool
    {
        $obUser = new \CUser();
        $arUser = \Opt\Main\User\UserEoTable::getById($id)->fetch();

        return $arUser && $obUser->Update($id, $arData);
    }

    public static function delete($id): bool
    {
        $arUser = \Opt\Main\User\UserEoTable::getById($id)->fetch();

        return $arUser && \CUser::Delete($id);
    }
}
