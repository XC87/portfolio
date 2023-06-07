<?php

namespace Opt\HighloadBlock\Client\ApiKey;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

class ApiKeyEoTable extends \Bitrix\Main\Entity\DataManager
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
    private static $hashSalt = 'bj24w958bghw54bh';

    public static function getTableName()
    {
        return 'hl_client_api_key';
    }

    public static function getObjectClass()
    {
        return \Opt\HighloadBlock\Client\ApiKey\ApiKeyEo::class;
    }

    public static function getCollectionClass()
    {
        return \Opt\HighloadBlock\Client\ApiKey\ApiKeyEoCollection::class;
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
            'UF_ACTIVE' => array(
                'data_type' => 'integer',
            ),
            'UF_KEY' => array(
                'data_type' => 'text',
            ),
            'UF_CLIENT_ID' => array(
                'data_type' => 'integer',
            ),
            // клиент
            'CLIENT' => (new Reference(
                'CLIENT', \Opt\HighloadBlock\Client\ClientEoTable::class, Join::on('this.UF_CLIENT_ID', 'ref.ID')
            ))->configureJoinType('LEFT'),
            // пользователь
            'USER' => (new Reference(
                'USER', \Bitrix\Main\UserTable::class, Join::on('this.UF_USER_ID', 'ref.ID')
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
     * @param int $userId
     *
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getByUserId(int $userId): string
    {
        $obQuery = \Opt\HighloadBlock\Client\ClientEoTable::query();
        $obQuery->setSelect(['UF_KEY' => 'API_KEY_COLLECTION.UF_KEY']);
        $obQuery->where('UF_ACTIVE', 1);
        $obQuery->where('UF_USER_ID', $userId);
        $obQuery->where('API_KEY_COLLECTION.UF_ACTIVE', 1);
        $rsQuery = $obQuery->exec();
        if ($arQuery = $rsQuery->fetch()) {
            return $arQuery['UF_KEY'];
        }
        return '';
    }

    /**
     * @param string $key
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getUserId(string $key): int
    {
        $obQuery = self::query();
        $obQuery->setSelect(['USER_ID' => 'CLIENT.UF_USER_ID']);
        $obQuery->where('UF_ACTIVE', 1);
        $obQuery->where('UF_KEY', $key);
        $obQuery->where('CLIENT.UF_ACTIVE', 1);
        $rsQuery = $obQuery->exec();
        if ($arQuery = $rsQuery->fetch()) {
            return intval($arQuery['USER_ID']);
        }
        return 0;
    }

    /**
     * @param int $userId
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function generate(int $userId): bool
    {
        if (\Opt\Main\Role\User::hasAccessToApi($userId)) {
            $clientId = \Opt\HighloadBlock\Client\ClientEoTable::getIdByUserId($userId);
            if (!empty($clientId)) {
                // деактивация ключей
                $obQuery = \Opt\HighloadBlock\Client\ClientEoTable::query();
                $obQuery->setSelect(
                    [
                        'ID',
                        'API_KEY_ID' => 'API_KEY_COLLECTION.ID',
                    ]
                );
                $obQuery->where('UF_ACTIVE', 1);
                $obQuery->where('UF_USER_ID', $userId);
                $obQuery->where('API_KEY_COLLECTION.UF_ACTIVE', 1);
                $rsQuery = $obQuery->exec();
                while ($arQuery = $rsQuery->fetch()) {
                    self::update($arQuery['API_KEY_ID'], ['UF_ACTIVE' => 0]);
                }
                // генерация нового ключа
                $key = md5(serialize($_SERVER) . time() . self::$hashSalt);
                $rsResult = self::add(['UF_CLIENT_ID' => $clientId, 'UF_ACTIVE' => 1, 'UF_KEY' => $key]);
                if ($rsResult->isSuccess()) {
                    return true;
                }
            }

        } else {
            throw new \Bitrix\Main\SystemException('Не достаточно прав для создания ключа API');
        }
        return false;
    }
}
