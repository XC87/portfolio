<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;
use Opt\HighloadBlock\Shops\Delivery\Region\Region;
use Opt\HighloadBlock\Site\SiteTable;
use Opt\Main\Component\Helper;
use Opt\Shop\Entity;

Loc::loadMessages(__FILE__);

class DeskSiteUmbrella extends Helper
{
    protected $cacheDir;
    protected $arEnumList;
    protected $request;
    protected $arHlBlock;

    public function __construct($component = null)
    {
        $this->cache = new CPHPCache();
        parent::__construct($component);
        $this->cacheDir = $this->getSiteId() . "/desk/";
    }

    public function onPrepareComponentParams($arParams)
    {
        $arParams['FILTER_NAME'] = $arParams['FILTER_NAME'] ? $arParams['FILTER_NAME'] : 'filterTicket';
        $arParams['PREFIX'] = $arParams['PREFIX'] ? $arParams['PREFIX'] : 'site';
        $arParams['SAVE_IN_SESSION'] = $arParams['SAVE_IN_SESSION'] ? $arParams['SAVE_IN_SESSION'] : 'Y';
        $arParams['CACHE_TIME'] = $arParams['CACHE_TIME'] ? $arParams['CACHE_TIME'] : 3600;
        return parent::onPrepareComponentParams($arParams);
    }

    private function checkRights($id)
    {
        global $USER;
        $viewOnly = false;

        $groupId = \Opt\Main\Group::getIdByCode('SHOP_SITE_CONTROL');
        $arUserGroups = $USER->GetUserGroupArray();
        if (!$id) {
            $viewOnly = isUserOOT();
        }
        return ($viewOnly || isUserAdmin() || isUserSeniorINT() || in_array($groupId, $arUserGroups));
    }

    /** Возвращает список сайтов
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getShopList()
    {
        global $APPLICATION;

        $arSiteList = $this->arEnumList['ID_TO_NAME'];
        unset($arSiteList[array_flip($this->arEnumList['ID_TO_LID'])[Entity::SITE_OZ]]);

        $FILTER_NAME = $this->arParams['FILTER_NAME'];
        $PREFIX = $this->arParams['PREFIX'];
        global ${$FILTER_NAME};

        $arFilterKey = [
            'SEARCH' => [
                'NAME' => Loc::getMessage('SO_SEARCH'),
                'TYPE' => 'TEXT',
                'SHOW_FILTER_HEADER' => 'Y',
                'CSS' => 'filter filter--search',
                'CSS_FIELD' => 'filter__field Field',
                'ATTRIBUTE' => [
                    'placeholder' => Loc::getMessage("SO_SEARCH_TEXT"),
                ],
            ],
            'UF_SITE' => [
                'NAME' => Loc::getMessage('SO_SITE'),
                'TYPE' => 'LIST',
                'SHOW_FILTER_HEADER' => 'Y',
                'LIST' => $arSiteList,
                'CSS' => 'filter filter--site',
                'CSS_FIELD' => 'filter__field Field',
            ],
        ];

        $this->arResult['strFilter'] = $APPLICATION->IncludeComponent(
            'global:data.filter',
            'desk2',
            [
                'PREFIX' => $PREFIX,
                'FILTER_KEY' => $arFilterKey,
                'FILTER_NAME' => $FILTER_NAME,
                'SAVE_IN_SESSION' => $this->arParams['SAVE_IN_SESSION'],
                'RETURN' => 'Y',
                'FORM_CSS' => 'controlPanel controlPanel--filters',
                'CSS_BUTTON' => 'filter filter--alignBottom',
                'SHORT_DATE' => 'Y',
                'BUTTON_TYPE' => 'AJAX',
                'ACTION_FORM' => $APPLICATION->GetCurPage(false)
            ],
            $this
        );

        $arFilter = ${$FILTER_NAME};

        $arFilter = array_merge(
            [
                [
                    'LOGIC' => 'OR',
                    ['UF_ACTIVE' => '1'],
                ],
            ],
            $arFilter
        );
        if ($arFilter['SEARCH']) {
            $arFilter = array_merge(
                [
                    [
                        'LOGIC' => 'OR',
                        ['%=REGIONS' => '%' . $arFilter['SEARCH'] . '%'],
                        ['%=USER.LOGIN' => '%' . $arFilter['SEARCH'] . '%'],
                        ['%=UF_IDENTIFIER' => '%' . $arFilter['SEARCH'] . '%'],
                    ],
                ],
                $arFilter
            );
            unset($arFilter['SEARCH']);
        }

        $arFilter['!UF_SITE'] = Entity::getSiteId(Entity::SITE_OZ);

        $arSiteCnt = Entity::getDataClass()::getList(
            [
                'runtime' => \Opt\HighloadBlock\Site\SiteTable::addDksRuntime(),
                'filter' => $arFilter,
                'select' => [
                    new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(DISTINCT %1$s)', ['ID']),
                ],
            ]
        )
            ->fetch();

        if ($arSiteCnt['CNT'] > 0) {
            $this->arResult['NAV_RESULT'] = $this->getNavigation($arSiteCnt['CNT']);
            $this->arResult['NAV_STRING'] = $this->arResult['NAV_RESULT']->GetPageNavString(
                'Сайты',
                'feedback',
                'Y'
            );

            $rsSite = $this->getShops($arFilter);
            while ($arSite = $rsSite->fetch()) {
                $arSite = $this->prepareResult($arSite);
                $this->arResult['ITEM'][] = $arSite;
            }
        }
    }

    public function getShopDetail($id)
    {
        global $APPLICATION;
        $APPLICATION->AddChainItem("Редактирование сайта");

        $arFilter = ['ID' => $id];
        $rsSite = $this->getShops($arFilter);
        if ($arSite = $rsSite->fetch()) {
            $arSite = $this->prepareResult($arSite);
            $this->arResult = array_merge($arSite, $this->arResult);
        }
        $this->getRegionList();
    }

    /**
     * Общая функция получения данных
     *
     * @param $arFilter
     *
     * @return \Bitrix\Main\ORM\Query\Result
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getShops($arFilter)
    {
        $arFilter['!UF_SITE'] = Entity::getSiteId(Entity::SITE_OZ);

        return Entity::getDataClass()::getList(
            [
                'runtime' => \Opt\HighloadBlock\Site\SiteTable::addDksRuntime(),
                'filter' => $arFilter,
                'select' => [
                    'ID',
                    'UF_UUID',
                    'UF_ACTIVE',
                    'LOGIN' => 'USER.LOGIN',
                    'SHOP_UUID' => 'SITE.UF_UUID',
                    'UF_STATUS' => 'SITE.UF_STATUS',
                    'UF_STATE' => 'SITE.UF_STATE',
                    'UF_SHOP_TITLE' => 'UF_SHOP_TITLE',
                    'UF_REDIRECT' => 'SITE.UF_REDIRECT',
                    'UF_IDENTIFIER',
                    'UF_USER' => 'UF_USER',
                    'UF_SITE',
                    'MAIN_REGION' => 'UF_MAIN_REGION.SECTION.NAME',
                    'MAIN_REGION_UUID' => 'UF_MAIN_REGION.UF_UUID',
                    'REGIONS' => 'REGIONS',
                    'REGIONS_UUID' => 'REGIONS_UUID',
                ],
                'limit' => $this->arResult['NAV_RESULT']->NavPageSize,
                'offset' => ($this->arResult['NAV_RESULT']->NavPageNomer - 1)
                    * $this->arResult['NAV_RESULT']->NavPageSize,
            ]
        );
    }

    /**
     * Обрабатываем данные о сайте
     *
     * @param $arSite
     *
     * @return array
     */
    private function prepareResult($arSite)
    {
        $arSite['UF_SITE'] = $this->arEnumList['ID_TO_NAME'][$arSite['UF_SITE']];

        return $arSite;
    }

    /**
     * Формирование постраничной навигации
     *
     * @param int $resultCount
     *
     * @return CDBResult
     */
    protected function getNavigation(int $resultCount)
    {
        //постраничка
        $rsNavigation = new \CDBResult();
        $rsNavigation->NavStart($this->arParams["ELEMENT_PAGE_COUNT"]);
        $rsNavigation->NavRecordCount = $resultCount;
        $rsNavigation->NavPageCount = ceil($resultCount / $this->arParams["ELEMENT_PAGE_COUNT"]);
        $rsNavigation->NavPageNomer =
            !empty($_REQUEST['PAGEN_' . $rsNavigation->NavNum]) ? $_REQUEST['PAGEN_' . $rsNavigation->NavNum] : 1;

        return $rsNavigation;
    }

    protected function handlePost()
    {
        if ($id = $this->request->get('EDIT_ID')) {
            $this->arResult['ERROR_LIST'] = [];
            if (!$this->request->get('UF_IDENTIFIER')) {
                $this->arResult['ERROR_LIST']['UF_IDENTIFIER'] = Loc::getMessage("SO_FIELD_REQUIRED");
            }
            
            if (!$this->request->get('UF_SHOP_TITLE')) {
                $this->arResult['ERROR_LIST']['UF_SHOP_TITLE'] = Loc::getMessage("SO_FIELD_REQUIRED");
            }

            if (!$this->arResult['ERROR_LIST']) {
                $arFilter = ['ID' => $id];
                $arShop = $this->getShops($arFilter)
                    ->fetch();

                if ($arShop) {
                    $arOldRegion = [];
                    $arNewRegions = $this->request->get('REGIONS');
                    $newMainRegion = $this->request->get('REGION_MAIN');
                    $oldMainRegion = $this->request->get('OLD_MAIN_REGION');
                    if (!in_array($newMainRegion, $arNewRegions)) {
                        $arNewRegions[] = $newMainRegion;
                    }
                    $obOldRegion = Region::getList([
                        'select' => ['ID', 'UF_REGION_UUID'],
                        'filter' => ['=UF_SHOP_UUID' => $arShop['UF_UUID']],
                    ]);
                    while ($arRegion = $obOldRegion->fetch()) {
                        $arOldRegion[$arRegion['UF_REGION_UUID']] = $arRegion;
                    }

                    if ($arNewRegions) {
                        $arNewRegions = array_flip($arNewRegions);
                    } else {
                        $arNewRegions = [];
                    }
                    foreach ($arOldRegion as $regionUuId => $arRegion) {
                        if (!isset($arNewRegions[$regionUuId])) {
                            Region::delete($arRegion['ID']);
                        } else {
                            unset($arNewRegions[$regionUuId]);
                        }
                    }

                    foreach ($arNewRegions as $arNewRegion => $tmp) {
                        $arData = [
                            'UF_SHOP_UUID' => $arShop['UF_UUID'],
                            'UF_REGION_UUID' => $arNewRegion,
                            'UF_REGION_DEFAULT' => '0',
                            'UF_DELIVERY_CHARGES' => '200',
                            'UF_DELIVERY_FREE' => '1000',
                            'UF_DELIVERY_TIME' => '2',
                            'UF_INCLUDE_EXCEPTION' => '1',
                        ];
                        $obRes = Region::add($arData);
                        if ($obRes->isSuccess()) {
                            $arOldRegion[$arNewRegion] = ['ID' => $obRes->getId()];
                        }
                    }

                    if ($newMainRegion != $oldMainRegion) {
                        if ($oldMainRegion) {
                            $arData = [
                                'UF_REGION_DEFAULT' => '0',
                            ];
                            Region::update($arOldRegion[$oldMainRegion]['ID'], $arData);
                        }
                        if ($newMainRegion) {
                            $arData = [
                                'UF_REGION_DEFAULT' => '1',
                            ];
                            Region::update($arOldRegion[$newMainRegion]['ID'], $arData);
                        }
                    }

                    $arShopData = [
                        'UF_IDENTIFIER' => $this->request->get('UF_IDENTIFIER'),
                        'UF_SHOP_TITLE' => $this->request->get('UF_SHOP_TITLE'),
                    ];

                    Entity::update($id, $arShopData);

                    if (!$this->arResult['ERROR_LIST'] && $this->arResult['ERROR_TEXT']) {
                        LocalRedirect($GLOBALS['APPLICATION']->GetCurPage(false));
                    }
                }
            }
        }
    }


    private function getRegionUuidList() : array
    {
        $rsSection = \CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            ['IBLOCK_ID' => IB_REGION_DKS, 'ACTIVE' => 'Y'],
            false,
            ['ID', 'UF_UUID']
        );
        while ($arSection = $rsSection->Fetch()) {
         $arReturn[$arSection['ID']] = $arSection['UF_UUID'];
        }

        return $arReturn;
    }

    function getRegionList()
    {
        $arSectionUuid = $this->getRegionUuidList();

        $rsSection = \CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            ['IBLOCK_ID' => IB_REGION_DKS, 'ACTIVE' => 'Y'],
            false,
            ['ID', 'IBLOCK_SECTION_ID', 'NAME', 'UF_REGION_CENTER', 'UF_REGION_SPECIAL', 'UF_UUID']
        );
        while ($arSection = $rsSection->Fetch()) {
            if (empty($arSection['IBLOCK_SECTION_ID'])) {
                $this->arResult['REGION_LIST'][$arSection['UF_UUID']]['LABEL'] = $arSection['NAME'];
            } else {
                $this->arResult['CITY_LIST'][$arSection['NAME'] . '-' . $arSection['UF_UUID']][$arSection['UF_UUID']] =
                    $arSectionUuid[$arSection['IBLOCK_SECTION_ID']];
            }
        }

        //сортировка городов
        $locale = setlocale(LC_ALL, "0");
        setlocale(LC_ALL, 'ru_RU.cp1251');
        uksort(
            $this->arResult['CITY_LIST'],
            function ($a, $b) {
                $a = strtoupper($a);
                $b = strtoupper($b);
                return strcasecmp($a, $b);
            }
        );
        setlocale(LC_ALL, $locale);

        $this->arResult['REGIONS_UUID'] = explode(',', $this->arResult['REGIONS_UUID']);
        // очистка от пустых регионов и сортировка
        foreach ($this->arResult['REGION_LIST'] as $regionID => $regionName) {
            $use = false;
            foreach ($this->arResult['CITY_LIST'] as $cityName => $arCity) {
                // удаление ID города в конце имени "...-123456"
                $positionId = strpos($cityName, '-');
                $cityName = substr($cityName, 0, $positionId);

                if (in_array($regionID, $arCity)) {
                    $use = true;
                    $this->arResult['REGION_LIST'][$regionID]['ITEMS'][key($arCity)] = $cityName;
                    if (in_array(key($arCity),$this->arResult['REGIONS_UUID'])) {
                        $this->arResult['MAIN_REGION_LIST'][key($arCity)] = $cityName;
                    }
                }
            }
            if (!$use) {
                unset($this->arResult['REGION_LIST'][$regionID]);
            }
        }

        unset($arName, $arSort, $this->arResult['CITY_LIST']);
    }

    public function executeComponent()
    {
        global $USER;

        $this->arHlBlock = SiteTable::getHlBlockByName(SiteTable::getEntityName());
        $this->arEnumList = \Opt\Shop\Entity::getSiteType();
        $this->arResult['USER_ID'] = $USER->GetID();
        if ((int)$this->arParams['SITE_ID'] > 0) {
            if (!$this->checkRights($this->arParams['SITE_ID'])) {
                $this->arResult['ERROR_TEXT'] = Loc::getMessage('SO_YOU_HAVE_NO_RIGHTS');
                $template = 'error';
            } else {
                $template = 'detail';
                $this->handlePost();
                $this->getShopDetail($this->arParams['SITE_ID']);
            }
        } else {
            if (!$this->checkRights($this->arParams['SITE_ID'])) {
                $this->arResult['ERROR_TEXT'] = Loc::getMessage('SO_YOU_HAVE_NO_RIGHTS_SEE_THIS');
                $template = 'error';
            } else {
                $this->getShopList();
                if (count($this->arResult['ITEM']) > 0) {
                    $template = '';
                } else {
                    $this->arResult['LIST_URL'] = $this->arParams['LIST_URL'] . '?filter_clear=Y';
                    $template = 'empty';
                }
            }
        }
        $this->arResult['arEnumList'] = $this->arEnumList;
        $this->includeComponentTemplate($template);
    }

}
