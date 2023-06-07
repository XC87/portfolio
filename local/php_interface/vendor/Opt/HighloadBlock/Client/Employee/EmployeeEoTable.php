<?php

namespace Opt\HighloadBlock\Client\Employee;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;

class EmployeeEoTable extends \Bitrix\Main\Entity\DataManager
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;

    public static function getTableName()
    {
        return 'hl_client_employee';
    }

    public static function getObjectClass()
    {
        return \Opt\HighloadBlock\Client\Employee\EmployeeEo::class;
    }

    public static function getCollectionClass()
    {
        return \Opt\HighloadBlock\Client\Employee\EmployeeEoCollection::class;
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
            'UF_DATE_REMOVE' => array(
                'data_type' => 'datetime',
            ),
            'UF_CLIENT_ID' => array(
                'data_type' => 'integer',
            ),
            'UF_USER_ID' => array(
                'data_type' => 'integer',
            ),
            // клиент
            'CLIENT' => (new Reference(
                'CLIENT', \Opt\HighloadBlock\Client\ClientEoTable::class, Join::on('this.UF_CLIENT_ID', 'ref.ID')
            ))->configureJoinType('LEFT'),
            // пользователь клиента
            'CLIENT_USER' => (new Reference(
                'CLIENT_USER', \Opt\Main\User\UserEoTable::class, Join::on('this.CLIENT.UF_USER_ID', 'ref.ID')
            ))->configureJoinType('LEFT'),
            // пользователь
            'USER' => (new Reference(
                'USER', \Opt\Main\User\UserEoTable::class, Join::on('this.UF_USER_ID', 'ref.ID')
            ))->configureJoinType('LEFT'),
        );
    }

    /**
     * @param \Bitrix\Main\Entity\Event $obEvent
     *
     * @return \Bitrix\Main\Entity\EventResult|void
     * @throws \Bitrix\Main\ObjectException
     */
    public static function onBeforeUpdate(\Bitrix\Main\Entity\Event $obEvent)
    {
        $obResult = new \Bitrix\Main\Entity\EventResult;
        $arFieldList = $obEvent->getParameter("fields");
        $arFieldList['UF_DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
        $obResult->modifyFields($arFieldList);
        return $obResult;
    }

    /**
     * @param \Bitrix\Main\Entity\Event $obEvent
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onAfterAdd(\Bitrix\Main\Entity\Event $obEvent)
    {
        self::cleanCacheEmployeeList($obEvent);
    }

    /**
     * @param \Bitrix\Main\Entity\Event $obEvent
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function onAfterUpdate(\Bitrix\Main\Entity\Event $obEvent)
    {
        self::cleanCacheEmployeeList($obEvent);
    }

    /**
     * @param $clientId
     *
     * @return string
     */
    public static function getEmployeeCachePath($clientId): string
    {
        return self::getCachePath() . '/client/' . $clientId . '/employee';
    }

    /**
     * @param int $clientId
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function cleanCacheEmployeeList(\Bitrix\Main\Entity\Event $obEvent)
    {
        $arFieldList = $obEvent->getParameter('fields');
        if (empty($arFieldList['UF_CLIENT_ID'])) {
            $arFieldList['UF_CLIENT_ID'] = self::getClientIdByEmployeeId($obEvent->getParameter('id')['ID']);
        }
        if (!empty($arFieldList['UF_CLIENT_ID'])) {
            BXClearCache(true, self::getEmployeeCachePath($arFieldList['UF_CLIENT_ID']));
        }
    }

    /**
     * @param int $employeeId
     *
     * @return int
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private static function getClientIdByEmployeeId(int $employeeId = 0): int
    {
        if (!empty($employeeId)) {
            $rsEmployee = self::getById($employeeId);
            if ($arEmployee = $rsEmployee->fetch()) {
                return $arEmployee['UF_CLIENT_ID'];
            }
        }
        return 0;
    }

    /**
     * @param int $userId
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function removeByUserId(int $userId): bool
    {
        $cUser = new \CUser;
        if ($cUser->Update($userId, ['ACTIVE' => 'N'])) {
            $obQuery = self::query();
            $obQuery->where('UF_USER_ID', $userId);
            $obQuery->setSelect(['ID']);
            $rsQuery = $obQuery->exec();
            if ($arQuery = $rsQuery->fetch()) {
                $rsResult = self::update(
                    $arQuery['ID'],
                    [
                        'UF_DATE_REMOVE' => new \Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s'), 'Y-m-d H:i:s'),
                    ]
                );
                return boolval($rsResult->isSuccess());
            }
        }
        return false;
    }

    /**
     * @param $userId
     * @param array $arSelectAdditional
     * @param bool $cache
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getByClientUserId($userId, array $arSelectAdditional = [], bool $cache = true): array
    {
        // выборка данных
        $clientId = \Opt\HighloadBlock\Client\ClientEoTable::getIdByUserId($userId);
        $cachePath = self::getEmployeeCachePath($clientId);
        $obCache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheString = $userId . serialize($arSelectAdditional);
        if ($obCache->initCache(self::$cacheTime, $cacheString, $cachePath)) {
            $arEmployeeList = $obCache->GetVars();
        } else {
            $arEmployeeList = [];

            $obQuery = \Opt\HighloadBlock\Client\ClientEoTable::query();
            $arSelect = [
                'EMPLOYEE_ID' => 'EMPLOYEE_COLLECTION.ID',
                'EMPLOYEE_USER_ID' => 'EMPLOYEE_COLLECTION.USER.ID',
                'EMPLOYEE_ACTIVE' => 'EMPLOYEE_COLLECTION.USER.ACTIVE',
                'EMPLOYEE_LAST_NAME' => 'EMPLOYEE_COLLECTION.USER.LAST_NAME',
                'EMPLOYEE_NAME' => 'EMPLOYEE_COLLECTION.USER.NAME',
                'EMPLOYEE_SECOND_NAME' => 'EMPLOYEE_COLLECTION.USER.SECOND_NAME',
                'EMPLOYEE_DATE_REMOVE' => 'EMPLOYEE_COLLECTION.UF_DATE_REMOVE',
                'EMPLOYEE_CLIENT_ID' => 'EMPLOYEE_COLLECTION.UF_CLIENT_ID',
            ];
            if (!empty($arSelectAdditional)) {
                foreach ($arSelectAdditional as $select) {
                    switch ($select) {
                        case 'WORK_COMPANY':
                            $arSelect['EMPLOYEE_WORK_COMPANY'] = 'EMPLOYEE_COLLECTION.USER.WORK_COMPANY';
                            break;
                        case 'UF_WORK_NAME_COMPANY':
                            $arSelect['EMPLOYEE_WORK_NAME_COMPANY'] = 'EMPLOYEE_COLLECTION.USER.UF_WORK_NAME_COMPANY';
                            break;
                        case 'UF_MANAGER':
                            $arSelect['EMPLOYEE_MANAGER'] = 'EMPLOYEE_COLLECTION.USER.UTS_OBJECT.UF_MANAGER';
                            break;
                        default:
                            throw new \Exception('Ќе корректный ключ сотрудника');
                    }
                }
            }
            $obQuery->setSelect($arSelect);
            $obQuery->where('UF_USER_ID', $userId);
            $obQuery->whereNotNull('EMPLOYEE_COLLECTION.USER.ID');
            $rsQuery = $obQuery->exec();
            while ($arQuery = $rsQuery->fetch()) {
                $arEmployee = [
                    'USER_ID' => $arQuery['EMPLOYEE_USER_ID'],
                    'ID' => $arQuery['EMPLOYEE_ID'],
                    'ACTIVE' => $arQuery['EMPLOYEE_ACTIVE'],
                    'DATE_REMOVE' => $arQuery['EMPLOYEE_DATE_REMOVE'],
                    'CLIENT_ID' => $arQuery['EMPLOYEE_CLIENT_ID'],
                    'FULL_NAME' => trim(
                        $arQuery['EMPLOYEE_LAST_NAME'] . ' ' . $arQuery['EMPLOYEE_NAME'] . ' '
                        . $arQuery['EMPLOYEE_SECOND_NAME']
                    ),
                ];
                if (!empty($arSelectAdditional)) {
                    foreach ($arSelectAdditional as $select) {
                        switch ($select) {
                            case 'WORK_COMPANY':
                                $arEmployee['WORK_COMPANY'] = $arQuery['EMPLOYEE_WORK_COMPANY'];
                                break;
                            case 'UF_WORK_NAME_COMPANY':
                                $arEmployee['UF_WORK_NAME_COMPANY'] = $arQuery['EMPLOYEE_WORK_NAME_COMPANY'];
                                break;
                            case 'UF_MANAGER':
                                $arEmployee['UF_MANAGER'] = $arQuery['EMPLOYEE_MANAGER'];
                                break;
                        }
                    }
                }
                $arEmployeeList[$arQuery['EMPLOYEE_USER_ID']] = $arEmployee;
            }
            // запись в кеш
            if ($cache && $obCache->startDataCache()) {
                $obCache->endDataCache($arEmployeeList);
            }
        }
        return $arEmployeeList;
    }

    /**
     * @param $userId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getActiveEmployee($userId): array
    {
        $arEmployee = self::getByClientUserId($userId);
        if (!empty($arEmployee)) {
            foreach ($arEmployee as $userIdInLoop => $arUser) {
                if ($arUser['ACTIVE'] === 'N') {
                    unset($arEmployee[$userIdInLoop]);
                }
            }
        }
        return $arEmployee;
    }
}
