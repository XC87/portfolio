<?php

use Opt\Main\User\Demo\DemoUsers;
use Opt\Main\User\UserEoCollection;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

class DeskDemoClients extends \Opt\Main\Component\Helper
{

    public function executeComponent()
    {
        if (!$this->arParams['FILTER_NAME']) {
            $this->arParams['FILTER_NAME'] = 'arrFilter';
        }

        $filterName = $this->arParams['FILTER_NAME'];
        global ${$filterName};
        if (!is_array(${$filterName})) {
            ${$filterName} = [];
        }
        $obCurrentUser = \Opt\Main\User\UserEo::getCurrent();

        // формирование фильтра
        // берем только роли которые подходят по условию
        $arRole = array_filter(
            array_map(
                function ($arValue) {
                    if ($arValue['PERMISSION_VRN'] && !\Opt\Main\Role\User::isUserSamsonVrn()) {
                        return false;
                    }
                    return $arValue['ROLE'];
                },
                DemoUsers::GROUP_LIST
            )
        );

        $arFilterKey = [
            'ROLE' => [
                'NAME' => 'Роль',
                'HIDE_ALL' => 'Y',
                'TYPE' => 'LIST',
                'CSS' => 'filter',
                'CSS_FIELD' => 'filter__field Field',
                'DEFAULT' => 'wholesaler',
                'LIST' => array_combine(
                    array_keys($arRole),
                    $arRole
                ),
            ],
            'DISCOUNT' => [
                'NAME' => 'Скидка',
                'TYPE' => 'TEXT',
                'SHOW_FILTER_HEADER' => 'Y',
                'CSS' => 'filter filter--discount',
                'CSS_FIELD' => 'filter__field Field js-filter--discount',
                'DEFAULT' => 0,
                'POSTFIX_TEXT' => '%',
            ],
            'RANGE' => [
                'NAME' => 'Срок действия',
                'HIDE_ALL' => 'Y',
                'TYPE' => 'LIST',
                'CSS' => 'filter',
                'CSS_FIELD' => 'filter__field Field',
                'DEFAULT' => '',
                'LIST' => ['+1 day' => '1 день', '+3 day' => '3 дня', '+7 day' => '7 дней'],
            ],
        ];

        // подключаение шаблона фильтра и переопределение параметров
        $this->arResult['FILTER'] = $this->getApp()->IncludeComponent(
            'global:data.filter',
            'desk2',
            [
                'FILTER_KEY' => $arFilterKey,
                'FILTER_NAME' => $filterName,
                'SAVE_IN_SESSION' => 'Y',
                'RETURN' => 'Y',
                'ADD_UL' => true,
                'UL_CSS' => 'contentWrapper filters filters--demoAccess',
                'BUTTON_TITLE' => 'Создать',
                'BUTTON_TYPE' => 'AJAX',
                'BUTTON_GA' => [
                    'Демо-доступ',
                    'Клик',
                    'Кнопка «Создать»',
                ],
                'CSS_BUTTON' => 'filter filter--alignBottom',
                'CSS' => 'btn btnMain Spinner--potential js-createDemoAccess',
            ],
            false
        );

        $lastId = $_SESSION["DEMO_USER_ID"];
        $obDemoUserCollection = UserEoCollection::getForManager($obCurrentUser->fillUfLogin1c(), true);
        $obDemoUsers = new DemoUsers();
        foreach ($obDemoUserCollection as $obUser) {
            $arUser = $obUser->collectValues();
            $arUser['GROUP_TEXT'] = $obDemoUsers->getDemoGroup($arUser);
            if (!$arUser['GROUP_TEXT']) {
                continue;
            }
            $arUser['PASSWORD'] = $obDemoUsers->getDemoPassword($arUser);
            $this->arResult['USER'][] = $arUser;

            if ($lastId && $lastId == $obUser->getId()) {
                $this->arResult['LAST_USER'] = $arUser;
                unset($_SESSION["DEMO_USER_ID"]);
            }
        }

        $this->IncludeComponentTemplate();
    }
}