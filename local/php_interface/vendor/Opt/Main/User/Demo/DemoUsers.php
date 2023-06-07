<?php

namespace Opt\Main\User\Demo;

use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;
use CUser;
use Opt\HighloadBlock\Client\ClientEo;
use Opt\HighloadBlock\TradeFormat\TradeFormat;
use Opt\Main\Cfo;
use Opt\Main\Config\Option\TestEmail;
use Opt\Main\Group;
use Opt\Main\User\UserEo;
use Opt\Main\User\UserEoTable;
use Opt\Shop\Entity;

class DemoUsers
{
    public const PREFIX = 'demo_';

    public const GROUP_LIST = [
        GROUP['CHANNEL_DEALER_OP'] => [
            'TYPE' => 'dealerop',
            'SALE_FORMAT' => 'Подканал ДКС ОП',
            'ROLE' => 'Дилеры Офисной планеты',
            'LAST_NAME' => 'Дилер ОП',
            'COUNTRY_GROUP' => [GROUP['CHANNEL_DEALER_OP'], GROUP['RUS']],
            'NEED_SITE' => true,
            'PERMISSION_VRN' => false,
        ],
        GROUP['CHANNEL_DEALER_OZ'] => [
            'TYPE' => 'dealer',
            'SALE_FORMAT' => 'Подканал ДКС',
            'ROLE' => 'Дилер',
            'LAST_NAME' => 'Дилер',
            'COUNTRY_GROUP' => [GROUP['CHANNEL_DEALER_OZ'], GROUP['RUS']],
            'NEED_SITE' => true,
            'PERMISSION_VRN' => false,
        ],
        GROUP['CHANNEL_OPT'] => [
            'TYPE' => 'opt',
            'SALE_FORMAT' => 'Подканал НКС',
            'ROLE' => 'Оптовик',
            'LAST_NAME' => 'Клиент',
            'COUNTRY_GROUP' => [GROUP['CHANNEL_OPT'], GROUP['RUS']],
            'NEED_SITE' => false,
            'PERMISSION_VRN' => false,
        ],
        GROUP['BLR'] => [
            'TYPE' => 'blr',
            'SALE_FORMAT' => 'Подканал ДКС',
            'ROLE' => 'Клиент ТС (Беларусь)',
            'LAST_NAME' => 'Клиент',
            'COUNTRY_GROUP' => [GROUP['BLR']],
            'NEED_SITE' => true,
            'PERMISSION_VRN' => true,
        ],
        GROUP['ARM'] => [
            'TYPE' => 'arm',
            'SALE_FORMAT' => 'Подканал ДКС',
            'ROLE' => 'Клиент ТС (Армения)',
            'LAST_NAME' => 'Клиент',
            'COUNTRY_GROUP' => [GROUP['ARM']],
            'NEED_SITE' => true,
            'PERMISSION_VRN' => true,
        ],
        GROUP['KAZ'] => [
            'TYPE' => 'kaz',
            'SALE_FORMAT' => 'Подканал ДКС',
            'ROLE' => 'Клиент ТС (Казахстан)',
            'LAST_NAME' => 'Клиент',
            'COUNTRY_GROUP' => [GROUP['KAZ']],
            'NEED_SITE' => true,
            'PERMISSION_VRN' => true,
        ],
        GROUP['KGZ'] => [
            'TYPE' => 'kgz',
            'SALE_FORMAT' => 'Подканал ДКС',
            'ROLE' => 'Клиент ТС (Киргизия)',
            'LAST_NAME' => 'Клиент',
            'COUNTRY_GROUP' => [GROUP['KGZ']],
            'NEED_SITE' => true,
            'PERMISSION_VRN' => true,
        ],
    ];

    public const COMMON_INFO = [
        'NAME' => 'Демо',
        'WORK_COMPANY' => 'ООО «Самсон-опт»',
        'JUR_ADDRESS' => 'ул. Комиссаржевской, д. 1, г. Воронеж, 394000',
        'LEGAL_ENTITY_ID' => '1043600029670',
    ];

    private string $groupCode;
    private string $cfo;
    private array $arDemoGroup;
    private array $arNewDemoUser = [];
    private int $shopId = 0;

    /**
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Opt\Main\User\Demo\DemoUserException
     */
    public function add(string $groupCode, string $period, float $discount = 0): void
    {
        $this->groupCode = $groupCode;
        $this->setCfo();
        $this->setDemoGroup();
        if (empty($this->arDemoGroup)) {
            throw new DemoUserException(
                'Роль ' . $groupCode . ' для демо-пользователя не существует'
            );
        }

        $obDateTime = new DateTime();
        $currentDate = $obDateTime->format('d.m.Y');

        $login = $this->getLogin();
        $password = $this->getPassword($login, $currentDate);

        $manager1cLogin = UserEo::getCurrent()
            ->fillUfLogin1c();
        $deactivateDate = DateTime::createFromTimestamp(strtotime($period))
            ->setTime(23, 59);

        $arFields = [
            'ACTIVE' => 'Y',
            'LOGIN' => $login,
            'NAME' => self::COMMON_INFO['NAME'],
            'LAST_NAME' => $this->arDemoGroup['LAST_NAME'],
            'EMAIL' => TestEmail::getDummy(),
            'PASSWORD' => $password,
            'CONFIRM_PASSWORD' => $password,
            'LID' => 'so',
            'WORK_COMPANY' => self::COMMON_INFO['WORK_COMPANY'],
            'ADMIN_NOTES' => $this->arDemoGroup['ROLE'],
            'UF_COD' => Cfo::getPhoneCode($this->cfo),
            'UF_DISCOUNT' => (-1) * abs($discount),
            'UF_DATE_DEACTIVE' => $deactivateDate,
            'DATE_REGISTER' => $obDateTime,
            'UF_MANAGER' => $manager1cLogin,
            'EXTERNAL_AUTH_ID' => 'so',
            'GROUP_ID' => $this->getUserGroups(),
        ];
        $this->setNewDemoUser($arFields);

        $this->setTradeFormatsForClient();
        $this->createDemoShop();
    }

    public function getUserId(): int
    {
        return (int)($this->arNewDemoUser['ID'] ?? 0);
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    private function setShopId(int $shopId): void
    {
        $this->shopId = $shopId;
    }

    private function setCfo(): void
    {
        $this->cfo = Cfo::getCfoCode();
    }

    private function setDemoGroup(): void
    {
        $this->arDemoGroup = self::GROUP_LIST[$this->groupCode];
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    private function getLogin(): string
    {
        return self::PREFIX . $this->arDemoGroup['TYPE'] . '_' . $this->getCurrentIncrement();
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    private function getCurrentIncrement(): int
    {
        $loginLike = self::PREFIX . $this->arDemoGroup['TYPE'];
        $incrementPos = strlen($loginLike) + 2;
        $obQuery = UserEoTable::query();
        $obQuery->setSelect(['INCREMENT']);
        $obQuery->whereLike('LOGIN', $loginLike . '%');
        $obQuery->registerRuntimeField(
            new ExpressionField('INCREMENT', 'CAST(SUBSTR(%s FROM ' . $incrementPos . ') AS UNSIGNED)', ['LOGIN'])
        );
        $obQuery->setOrder(['INCREMENT' => 'DESC']);
        if ($arRow = $obQuery->fetch()) {
            return $arRow['INCREMENT'] + 1;
        }
        return 1;
    }

    private function getPassword(string $login, string $date): string
    {
        return strtoupper(substr(md5($login . $date), 0, 6));;
    }

    private function getUserGroups(): array
    {
        $groupId = Group::getIdByCode($this->groupCode);
        $arUserGroups = [
            $groupId,
            Group::getIdByCode(GROUP['CLIENT_MANAGER']),
            Group::getIdByCode(GROUP['DEMO']),
        ];
        if ($this->arDemoGroup['COUNTRY_GROUP']) {
            foreach ($this->arDemoGroup['COUNTRY_GROUP'] as $group) {
                $arUserGroups[] = Group::getIdByCode($group);
            }
        }
        if (!empty($this->arDemoGroup['NEED_SITE'])) {
            $arUserGroups[] = Group::getIdByCode(GROUP['CLIENT_WITH_SHOP']);
        }
        $arCfoCode = Cfo::getList();
        $arUserGroups[] = $arCfoCode[$this->cfo]['GROUP_ID'];
        $arUserGroups[] = Group::getIdByCode(GROUP['PRICE_OPT']);
        return $arUserGroups;
    }

    /**
     * @throws \Opt\Main\User\Demo\DemoUserException
     */
    private function setNewDemoUser(array $arFields): void
    {
        $obUser = new CUser();
        // если есть не активные демо пользователи то обновляем их
        $arInactiveList = DemoUsersRepository::getList('inactive');
        if (count($arInactiveList) > 0) {
            $userId = $arInactiveList[0]['ID'];
            $obUser->Update($userId, $arFields);
            $rsUser = $obUser->GetByID($userId);
            $this->arNewDemoUser = $rsUser->Fetch();
            $this->arNewDemoUser['PREVIOUS_LOGIN'] = $arInactiveList[0]['LOGIN'];
        } elseif ($userId = $obUser->Add($arFields)) {
            $rsUser = $obUser->GetByID($userId);
            $this->arNewDemoUser = $rsUser->Fetch();
        } else {
            throw new DemoUserException(
                'Ошибка добавления демо-пользователя ' . $arFields['LOGIN'] . ": \r\n" . strip_tags($obUser->LAST_ERROR)
            );
        }
    }

    /**
     * Установка форматов торговли клиенту
     */
    private function setTradeFormatsForClient(): void
    {
        $obTradeFormat = TradeFormat::getByName($this->arDemoGroup['SALE_FORMAT']);
        $obClient = ClientEo::getByUserId($this->arNewDemoUser['ID']);
        if (empty($obClient)) {
            $obClient = new ClientEo();
        }
        $obClient->setUfUserId($this->arNewDemoUser['ID'])
            ->setUfActive(true)
            ->setUfActionTfId($obTradeFormat->getId())
            ->setUfTfId($obTradeFormat->getId());
        if ($obClient->isUfActiveChanged() || $obClient->isUfActionTfIdChanged() || $obClient->isUfTfIdChanged()) {
            $obClient->save();
        }
    }

    /**
     * Создаём или обновляем магазин
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\SystemException
     * @throws \Opt\Main\User\Demo\DemoUserException
     */
    private function createDemoShop(): void
    {
        if (!empty($this->arDemoGroup['NEED_SITE'])) {
            $lid = $this->arDemoGroup['TYPE'] === 'dealer' ? Entity::SITE_OZ : Entity::SITE_OP;
            $arSiteType = Entity::getSiteType();
            $arFieldList = [
                'UF_IDENTIFIER' => $this->arNewDemoUser['LOGIN'],
                'UF_USER' => $this->arNewDemoUser['ID'],
                'UF_PRICE_DEFAULT' => DEFAULT_PRICE_DEALER . '_' . $this->cfo,
                'UF_JUR_ADDRESS' => self::COMMON_INFO['JUR_ADDRESS'],
                'UF_JUR_NAME' => self::COMMON_INFO['WORK_COMPANY'],
                'UF_LEGAL_ENTITY_ID' => self::COMMON_INFO['LEGAL_ENTITY_ID'],
                'UF_MAIL' => TestEmail::getDummy(),
                'UF_SITE' => array_search($lid, $arSiteType['ID_TO_LID'], false),
                'UF_MATRIX' => Entity::MATRIX_DEFAULT,
                'UF_MIN_PART_TYPE' => Entity::MIN_PART_TYPE_DEFAULT,
                'UF_NAME' => self::COMMON_INFO['WORK_COMPANY'],
                'UF_ACTIVE' => 1,
            ];
            $this->updateShopPreviousUsers($arFieldList);
            if (empty($this->shopId)) {
                $this->addShop($arFieldList);
            }
        } else {
            $this->updateShopPreviousUsers(['UF_ACTIVE' => 0]);
        }
    }

    /**
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\SystemException
     * @throws \Opt\Main\User\Demo\DemoUserException
     */
    private function updateShopPreviousUsers(array $arFieldList): void
    {
        if (!empty($this->arNewDemoUser['PREVIOUS_LOGIN'])) {
            $rsResult = Entity::getDataClass()::query()
                ->setSelect(['ID'])
                ->setFilter([
                    '=UF_USER' => $this->arNewDemoUser['ID'],
                ])
                ->exec();
            if ($arResult = $rsResult->fetch()) {
                $this->setShopId($arResult['ID']);
                $this->updateShop($arFieldList);
            }
        }
    }

    /**
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\SystemException
     * @throws \Opt\Main\User\Demo\DemoUserException
     */
    private function updateShop(array $arFieldList): void
    {
        if (!empty($this->shopId)) {
            $arFieldList['UF_XML_ID'] = md5($this->shopId);
            $rsShops = Entity::update($this->shopId, $arFieldList);
            if (!$rsShops->isSuccess()) {
                throw new DemoUserException(
                    'Не удалось обновить магазин (' . $this->shopId . ') для пользователя: '
                    . $this->arNewDemoUser['LOGIN']
                );
            }
        }
    }

    /**
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\SystemException
     * @throws \Opt\Main\User\Demo\DemoUserException
     */
    private function addShop(array $arFieldList): void
    {
        $rsShops = Entity::add($arFieldList);
        if ($rsShops->isSuccess()) {
            $this->setShopId($rsShops->getId());
            $this->updateShop($arFieldList);
        } else {
            throw new DemoUserException(
                'Не удалось добавить магазин для пользователя: ' . $this->arNewDemoUser['LOGIN']
            );
        }
    }

    public function getDemoPassword(array $arUser): string
    {
        return strtoupper(substr(md5($arUser['LOGIN'] . $arUser['DATE_REGISTER']->format('d.m.Y')), 0, 6));
    }

    public function getDemoGroup(array $arUser): string
    {
        foreach ($arUser['GROUPS'] as $obGroup) {
            if ($arGroup = self::GROUP_LIST[$obGroup->fillGroup()
                ->getStringId()]
            ) {
                return $arGroup['ROLE'];
            }
        }
        return '';
    }
}