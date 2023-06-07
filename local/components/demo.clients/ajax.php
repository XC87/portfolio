<?php

use Bitrix\Main\Engine\ActionFilter;
use Opt\Main\User\Demo\DemoUsers;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

class ClientListController extends \Bitrix\Main\Engine\Controller
{
    public function configureActions()
    {
        return [
            'addDemoUser' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    public function addDemoUserAction()
    {
        $arData = $this->request->get('DESC_FILTER_');
        $obDemoUsers = new DemoUsers();
        $obDemoUsers->add($arData['ROLE'], $arData['RANGE'], (float)$arData['DISCOUNT']);
        $arResult = [
            'userId' => $obDemoUsers->getUserId(),
            'shopId' => $obDemoUsers->getShopId()
        ];

        $_SESSION["DEMO_USER_ID"] = $arResult['userId'];

        return $arResult;
    }
}