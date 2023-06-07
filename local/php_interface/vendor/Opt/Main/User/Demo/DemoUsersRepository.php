<?php

namespace Opt\Main\User\Demo;

use Opt\HighloadBlock\Client\ClientEoTable;
use Opt\Main\User\UserEoTable;
use Opt\Sale\Basket\Basket;
use Samson\Logger\Controller\Bitrix\Log;

class DemoUsersRepository
{
    public static function deactivate(array $arUser): void
    {
        if ($arUser['ACTIVE'] === 'Y' && !UserEoTable::update($arUser['ID'], ['ACTIVE' => 'N'])) {
            throw new DemoUserException(
                'Не удалось деактивировать демо-пользователя: ' . $arUser['ID']
            );
        }

        ClientEoTable::deactivate($arUser['CLIENT_ID']);
        Basket::deleteBasket((int)$arUser['ID'], (string)$arUser['LID']);
    }

    public static function delete(array $arUser): void
    {
        if (!UserEoTable::delete($arUser['ID'])) {
            self::importError('Не удалось удалить демо-пользователя: ' . $arUser['ID']);
        } else {
            ClientEoTable::delete($arUser['CLIENT_ID']);
        }
    }

    public static function deleteInactive(int $limit = 300): void
    {
        // Деактивируем тех у кого истёк срок активности
        $arUsers = self::getList('deactive');
        foreach ($arUsers as $arUser) {
            self::deactivate($arUser);
        }

        $arToDelete = [];
        // Берём всех пользователе и зачищаем больше лимита
        $arUsers = self::getList('delete');
        foreach ($arUsers as $arUser) {
            if ($arUser['ACTIVE'] === 'N') {
                $arToDelete[] = $arUser;
            }
        }

        if (($countToDelete = count($arUsers) - $limit) > 0) {
            foreach ($arToDelete as $index => $arUser) {
                if ($countToDelete > $index) {
                    self::delete($arUser);
                }
            }
        }
    }

    public static function getList(string $filterType): array
    {
        $arReturn = [];
        $obQuery = UserEoTable::query()
            ->setSelect(
                [
                    'ID',
                    'LID',
                    'DATE_REGISTER',
                    'LOGIN',
                    'EMAIL',
                    'ACTIVE',
                    'CLIENT_ID' => 'CLIENT.ID',
                ]
            )
            ->whereLike('LOGIN', 'demo\_%')
            ->setOrder(['ID' => 'DESC']);

        switch ($filterType) {
            case 'deactive':
                $obQuery->where('ACTIVE', 'Y')
                    ->where('UF_DATE_DEACTIVE', '<=', new \Bitrix\Main\Type\Date());
                break;
            case 'inactive':
                $obQuery->where('ACTIVE', 'N');
                break;
            case 'delete':
            default:
                break;
        }

        $rsQuery = $obQuery->exec();
        while ($arShop = $rsQuery->fetch()) {
            $arReturn[] = $arShop;
        }

        return $arReturn;
    }

    private static function importError(string $error): void
    {
        if (defined('IMPORT_ROOT')) {
            $obLogImport = Log::getImportAdapter();
            $obLogImport->messageError($error);
        } else {
            throw new DemoUserException(
                $error
            );
        }
    }
}