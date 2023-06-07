<?php

namespace Opt\Event\Sale;

use Bitrix\Main;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\EventResult;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Internals\OrderPropsValueTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\ResultError;
use Catalog\Bonus;
use Opt\Catalog\Action\Info;
use Opt\HighloadBlock\Country;
use Opt\HighloadBlock\OrderProps\OrderProps;
use Opt\HighloadBlock\OrderProps\OrderPropsEoTable;
use Opt\HighloadBlock\OrderProps\Ready\OrderPropsReady;
use Opt\HighloadBlock\OrderPropsDks;
use Opt\Main\Cache\CachePathHelper;
use Opt\Main\Cfo;
use Opt\Main\Role\User;
use Opt\Main\User\UserEoTable;
use Opt\Sale\Basket\Provider;
use Opt\Sale\BasketCache;
use Opt\Sale\Order\Access;
use Opt\User\Client\Company;
use Opt\User\Notification;
use Samson\Logger\Controller\Bitrix\Log;

/**
 * Class Handler
 * @package Opt\Event\Sale
 */
class Handler
{
    private static string $codeBookmark = 'BOOKMARK';
    private static string $codeReadyOrder = 'READY_ORDER';

    private static $arOrderList = [];
    /**
     * @param $ID
     * @param $arFields
     */
    public static function onBasketAddHandler($ID, $arFields)
    {
        \Opt\Sale\Basket\Provider::SetFlag('BASKET_UPDATED', true);
    }

    /**
     * @param Main\Event $obEvent
     */
    public static function onSaleBasketItemRefreshDataHandler(Main\Event $obEvent)
    {
        BasketCache::clearByUser();
    }

    /**
     * @param $ID
     * @param $eventName
     * @param $arFields
     * @param $arOrderStatusId
     *
     * @throws Main\ArgumentException
     * @throws Main\ArgumentNullException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     * @throws \Exception
     */
    public static function onOrderStatusSendEmailHandler($ID, $eventName, &$arFields, $arOrderStatusId)
    {
        if (in_array($arOrderStatusId, \Opt\Sale\Status::SKIP_INFORMING)) {
            return false;
        }

        if ($arOrderStatusId === 'CE' || $arOrderStatusId === 'CR') { //ожидает согласования или отклонен
            $orderDate = new \DateTime($arFields['ORDER_DATE']);
            $arFields['ORDER_DATE_SHORT'] = $orderDate->format('d.m.Y');
            // получаем заказ
            $obOrder = Order::load($arFields['ORDER_ID']);
            // получаем подчиненного
            $employerId = $obOrder->getUserId();
            $obUser = Main\UserTable::getList(
                [
                    'filter' => ['ID' => $employerId],
                    'select' => ['ID', 'LOGIN', 'NAME', 'LAST_NAME'],
                    'limit' => 1,
                ]
            );
            if ($arEmployer = $obUser->fetch()) {
                $arFields['ORDER_AUTHOR'] = $arEmployer['LAST_NAME'] . ' ' . $arEmployer['NAME'];
                $employerLogin = $arEmployer['LOGIN'];

                // получаем дилера-управляющего
                $dealerId = Company::getManagerId($employerId);
                if ($dealerId) {
                    $obUser = Main\UserTable::getList(
                        [
                            'filter' => ['ID' => $dealerId],
                            'select' => ['ID', 'LOGIN', 'EMAIL'],
                            'limit' => 1,
                        ]
                    );
                    if ($arDealer = $obUser->fetch()) {
                        $arFields['DEALER_EMAIL'] = $arDealer['EMAIL'];
                        $dealerLogin = $arDealer['LOGIN'];

                        //создаем уведомления для подчиненного или для управляющего
                        $noticeUserId = ($arOrderStatusId === 'CE') ? $dealerId : $employerId;
                        $noticeUserLogin = ($arOrderStatusId === 'CE') ? $dealerLogin : $employerLogin;
                        $arParams = [
                            'IBLOCK_ID' => IB_NOTIFICATION,
                            'CLIENT' => [$noticeUserLogin => $noticeUserId],
                            'XML_ID' => ($arOrderStatusId === 'CE') ? 'unapproved_orders' : 'order_rejected',
                            'DATA' => [
                                $noticeUserLogin => [
                                    'ORDER_ID' => $arFields['ORDER_ID'],
                                    'ORDER_DATE' => $arFields['ORDER_DATE_SHORT'],
                                    'ORDER_USER_NAME' => $arFields['ORDER_AUTHOR'],
                                ],
                            ],
                        ];
                        Notification::add($arParams, false);
                    }
                }
            }
        }
    }

    /**
     * @param Main\Event $obEvent
     */
    public static function onSaleStatusOrderChangeHandler(Main\Event $obEvent)
    {
        $obOrder = $obEvent->getParameter("ENTITY");
        if (!empty($obOrder) && !$obOrder->isNew()) {
            try {
                if ($obOrder->getField('LID') === 'so') {
                    self::updateOrderStatusOpt($obEvent);
                } else {
                    self::updateOrderStatusDks($obEvent);
                }
			} catch (\Exception $exception) {
                $obLog = Log::getLogAdapter();
                $obLog->errorPhp($exception->getMessage());
			}
        }
    }

    private static function updateOrderStatusOpt(Main\Event $obEvent): void
    {
        /** @var Order $obOrder */
        $obOrder = $obEvent->getParameter("ENTITY");

        OrderProps::updateByOrderId(
            $obOrder->getId(),
            [
                'UF_DATE_STATUS' => $obOrder->getField('DATE_STATUS'),
                'UF_EMP_STATUS_ID' => $obOrder->getField('EMP_STATUS_ID'),
                'UF_STATUS_ID' => $obOrder->getField('STATUS_ID'),
            ]
        );
    }

    private static function updateOrderStatusDks(Main\Event $obEvent): void
    {
        /** @var Order $obOrder */
        $obOrder = $obEvent->getParameter("ENTITY");
        $arParams = $obEvent->getParameters();

        $isOldStatusProcessedOrReceived = in_array($arParams['OLD_VALUE'], ['N', 'P']);
        $isCurrentStatusProcessedOrReceived = in_array($arParams['VALUE'], ['N', 'P']);

        if ($isOldStatusProcessedOrReceived || $isCurrentStatusProcessedOrReceived) {
            $managerId = Company::getManagerId();
            $cachePath = CachePathHelper::getCachePathNewOrdersCount($managerId);
            $obCache = Cache::createInstance();
            $obCache->cleanDir($cachePath);
        }

        OrderPropsDks::updateByOrderId(
            $obOrder->getId(),
            [
                'UF_DATE_STATUS' => $obOrder->getField('DATE_STATUS'),
                'UF_EMP_STATUS_ID' => $obOrder->getField('EMP_STATUS_ID'),
                'UF_STATUS_ID' => $obOrder->getField('STATUS_ID'),
                'UF_CREATED_CALL' => false,
            ]
        );
    }

    /**
     * @param int $orderId
     *
     * @return string
     */
    private static function getOrderType(int $orderId): string
    {
        if (!array_key_exists($orderId, self::$arOrderList)) {
            self::$arOrderList[$orderId] = 'UNDEFINED';
            if ($orderIdHl = OrderProps::getRowIdByOrderId($orderId)) {
                self::$arOrderList[$orderId] = 'OPT';
            } elseif ($orderIdHl = OrderPropsDks::getRowIdByOrderId($orderId)) {
                self::$arOrderList[$orderId] = 'DKS';
            }
        }
        return self::$arOrderList[$orderId];
    }

    /**
     * @param $orderId
     *
     * @return bool
     */
    private static function isOrderOpt($orderId): bool
    {
        $orderType = self::getOrderType($orderId);
        return $orderType == 'OPT';
    }

    /**
     * @param $orderId
     *
     * @return bool
     */
    private static function isOrderDks($orderId): bool
    {
        $orderType = self::getOrderType($orderId);
        return $orderType == 'DKS';
    }

    /**
     * @param Main\Event $obEvent
     */
    public static function onOrderPropsValueUpdateHandler(Main\Event $obEvent)
    {
        $arProp = $obEvent->getParameter('fields');
        $orderId = $arProp['ORDER_ID'];

        try {
            if (!empty($orderId)) {
                if (self::isOrderOpt($orderId)) {
                    self::updateOrderPropsValueOpt($arProp, $orderId);
                } elseif (self::isOrderDks($orderId)) {
                    self::updateOrderPropsValueDks($arProp, $orderId);
                }
            }
        } catch (\Exception $exception) {
            $obLog = Log::getLogAdapter();
            $obLog->errorPhp($exception->getMessage());
        }
    }

    private static function updateOrderPropsValueOpt(array $arProp, int $orderId): void
    {
        $arPropOrder = [
            'DEALER_ID' => 'UF_CFO',
            'TENDER' => 'UF_TENDER',
            'ACTIONS_DESCRIPTION' => 'UF_ACTION',
            'ACTIONS_ID' => 'UF_ACTIONS_ID',
            'BOOKMARK' => 'UF_BOOKMARK',
            'CLIENT_ID' => 'UF_CLIENT_ID',
            'ARCHIVE' => 'UF_ARCHIVE',
            'BONUS_AMOUNT' => 'UF_BONUS_AMOUNT',
            'AGREEMENT' => 'UF_AGREEMENT',
            'COUNTERPARTY' => 'UF_COUNTERPARTY',
            'DELIVERY_TC_NAME' => 'UF_DELIVERY_TC_NAME',
            'DELIVERY_TYPE' => 'UF_DELIVERY_TYPE',
            'UP_ADDRESS_DELIV' => 'UF_DELIVERY_ADDRESS',
        ];

        if (array_key_exists($arProp['CODE'], $arPropOrder)) {
            $code = $arPropOrder[$arProp['CODE']];
            $propValue = $arProp['VALUE'];
            $arFieldHl[$code] = $propValue;

            if (($code === 'UF_ARCHIVE' || $code === 'UF_TENDER') && $propValue !== 'Y') {
                $arFieldHl[$code] = 'N';
            }

            if ($code === 'UF_DELIVERY_TYPE') {
                $arFieldHl[$code] = OrderProps::getEnumIdByCode($code, $arFieldHl[$code]);
            }

            OrderProps::updateByOrderId($orderId, $arFieldHl);
        }
    }

    private static function updateOrderPropsValueDks(array $arProp, int $orderId): void
    {
        $arProp['CODE'] = str_replace(['UP_', 'OZ_'], '', $arProp['CODE']);

        $arPropOrder = [
            'IDP' => 'UF_CFO',
            'DEALER_ID' => 'UF_SHOP',
            'DEALER_UUID' => 'UF_SHOP_UUID',
            'PAYER_ID' => 'UF_PAYER_ID',
            'KPP' => 'UF_KPP',
            'ADDRESS_UR' => 'UF_ADDRESS_UR',
            'ADDRESS' => 'UF_ADDRESS',
            'COMPANY' => 'UF_COMPANY',
            'PROFILE' => 'UF_PROFILE',
            'GIFT_DESCRIPTION' => 'UF_GIFT_DESCRIPTION',
            'CONTACT_PERSON' => 'UF_CONTACT_PERSON',
            'CELL_PHONE' => 'UF_CELL_PHONE',
            'WORK_HOURS' => 'UF_WORK_HOURS',
            'EMAIL' => 'UF_EMAIL',
            'PAY_TYPE' => 'UF_PAY_TYPE',
            'PICKUP' => 'UF_PICKUP',
            'UNRELATED' => 'UF_UNRELATED',
            'CITY' => 'UF_CITY',
        ];

        if (array_key_exists($arProp['CODE'], $arPropOrder)) {
            $code = $arPropOrder[$arProp['CODE']];
            $propValue = $arProp['VALUE'];

            if ($arProp['CODE'] === 'UNRELATED') {
                $propValue = $propValue === 'Y';
            }
            /**
             * @todo удалить 2024.12.09
             * https://youtrack.intsite.org/issue/so-139259
             * запрещаем затирать DEALER_UUID в hl`блоке
             */
            $isEmptyDealerUuid = $arProp['CODE'] === 'DEALER_UUID' && empty($propValue);
            if (!$isEmptyDealerUuid) {
                $arFieldHl[$code] = $propValue;

                OrderPropsDks::updateByOrderId($orderId, $arFieldHl);
            }
        }
    }

    /**
     * @param Main\Event $obEvent
     *
     * @throws Main\ArgumentException
     * @throws Main\LoaderException
     * @throws Main\NotImplementedException
     */
    public static function onSaleOrderSavedHandler(Main\Event $obEvent)
    {
        global $USER;

        // привязка заказа к ИДП
        /** @var Order $obOrder */
        $obOrder = $obEvent->getParameter("ENTITY");

        if ($obOrder->getField('LID') === 'so') {
            // извлечение свойств заказа
            $personTypeID = $obOrder->getPersonTypeId();
            $arPropList = getSaleOrderPropList($personTypeID);
            //признак тендера
            $valueTender = '';
            $arPropCollection = $obOrder->getPropertyCollection();
            foreach ($arPropCollection as $obProp) {
                if ($obProp->getField('CODE') == 'TENDER') {
                    $valueTender = $obProp->getValue();
                }
            }
            $isOrderTender = $valueTender === 'Y';
            // извлечение товаров из корзины
            $arItemList = [];
            $obItemList = $obEvent->getParameter("ENTITY")->getBasket()->getBasketItems();
            /** @var BasketItem $obItem */
            $countPzk = $countNotEnought = $countLessMin = 0;
            foreach ($obItemList as $obItem) {
                $arItemList[] = $obItem->getFieldValues();
                $obPropertyCollection = $obItem->getPropertyCollection();
                foreach ($obPropertyCollection->getPropertyValues() as $code => $arProps) {
                    if ($code == 'IS_PZK' && $arProps['VALUE'] == 'Y') {
                        $countPzk++;
                    }
                    if ($code == 'IS_NOT_ENOUGH' && $arProps['VALUE'] == 'Y') {
                        $countNotEnought++;
                    }
                    if ($code == 'LESS_MIN' && $arProps['VALUE'] == 'Y') {
                        $countLessMin++;
                    }
                }
            }
            if (!$isOrderTender && $obOrder->isNew()) {
                // определение списка сработавших акций
                $arActionList = Info::getInstance()->getForBasket($arItemList);
                $actions2Xml = [];
                foreach ($arActionList as $arAction) {
                    if (in_array($arAction['ACTION_KIND'], ['ПодарокЗаСумму', 'ПодарокЗаКоличество'])) {
                        if (!empty($arAction['STEP_NUMBER'])) {
                            $giftCode = $arAction['ACTION_KIND'] === 'ПодарокЗаСумму' ? 0 : 1;
                            $actions2Xml[] = $arAction['XML_ID'] . '-' . $arAction['STEP_NUMBER'] . ':' . $giftCode;
                        }
                    }
                    if (!empty($arAction['GIFT_CODE']) && !empty($arAction['GIFT_COUNT'])) {
                        $arActionsID[] = $arAction['XML_ID'];
                        $arActionsResult[] =
                            $arAction['XML_ID'] . ':' . $arAction['GIFT_CODE'] . ':' . $arAction['GIFT_COUNT'];
                    } else if (in_array(
                                   $arAction['ACTION_KIND'],
                                   ['СкидкаЗаАссортимент', 'СкидкаЗаКоличество', 'УбойныеЦены']
                               )
                               && !empty($arAction['REWARD'])
                               && empty($arAction['IS_FAKE'])) {
                        $arActionsID[] = $arAction['XML_ID'];
                        $arActionsResult[] = $arAction['XML_ID'] . ':%:' . $arAction['REWARD'];
                    }
                }
                // установка значения св-в
                $arValue = [];
                if (!empty($arPropList['ACTIONS_ID']) && !empty($arActionsID)) {
                    $arValue[$arPropList['ACTIONS_ID']['ID']] = ':' . implode(':', $arActionsID) . ':';
                }
                if (!empty($arPropList['ACTIONS_DESCRIPTION']) && !empty($arActionsResult)) {
                    $arValue[$arPropList['ACTIONS_DESCRIPTION']['ID']] = implode(';', $arActionsResult);
                }
                if (!empty($arPropList['GIFTS']) && !empty($actions2Xml)) {
                    $arValue[$arPropList['GIFTS']['ID']] = implode(', ', $actions2Xml);
                }
                // сохранение свойств
                if (!empty($arValue)) {
                    $arPropCollection = $obOrder->getPropertyCollection();
                    foreach ($arPropCollection as $obProp) {
                        if (array_key_exists($obProp->getPropertyId(), $arValue)) {
                            $obProp->setValue($arValue[$obProp->getPropertyId()]);
                            $obProp->save();
                        }
                    }
                }
            }

            $arFieldHl = [
                'UF_ORDER_ID' => $obOrder->getId(),
                'UF_DATE_INSERT' => $obOrder->getField('DATE_INSERT'),
                'UF_DATE_STATUS' => $obOrder->getField('DATE_STATUS'),
                'UF_USER_ID' => $obOrder->getField('USER_ID'),
                'UF_EMP_STATUS_ID' => $obOrder->getField('EMP_STATUS_ID'),
                'UF_STATUS_ID' => $obOrder->getField('STATUS_ID'),
                'UF_BASKET_PRICE' => $obOrder->getField('PRICE'),
                'UF_BASKET_COUNT' => $obOrder->getBasket()
                    ->count(),
                'UF_COUNT_PZK' => $countPzk,
                'UF_COUNT_NOT_ENOUGH' => $countNotEnought,
                'UF_COUNT_LESS_MIN' => $countLessMin,
                'UF_PAYER' => $obOrder->getField('PAYER'),
                'UF_CONSIGNEE' => $obOrder->getField('CONSIGNEE'),
                'UF_CONTRACT' => $obOrder->getField('CONTRACT'),
            ];
            $arPropOrder = [
                'DEALER_ID' => 'UF_CFO',
                'TENDER' => 'UF_TENDER',
                'ACTIONS_DESCRIPTION' => 'UF_ACTION',
                'ACTIONS_ID' => 'UF_ACTIONS_ID',
                'BOOKMARK' => 'UF_BOOKMARK',
                'CLIENT_ID' => 'UF_CLIENT_ID',
                'ARCHIVE' => 'UF_ARCHIVE',
                'BONUS_AMOUNT' => 'UF_BONUS_AMOUNT',
                'AGREEMENT' => 'UF_AGREEMENT',
                'COUNTERPARTY' => 'UF_COUNTERPARTY',
                'DELIVERY_TC_NAME' => 'UF_DELIVERY_TC_NAME',
                'DELIVERY_TYPE' => 'UF_DELIVERY_TYPE',
                'UP_ADDRESS_DELIV' => 'UF_DELIVERY_ADDRESS',
            ];
            if (!isUserErpPartner()) {
                $arPropertyOrder1C = [
                    'PAYER' => 'UF_PAYER',
                    'CONSIGNEE' => 'UF_CONSIGNEE',
                    'CONTRACT' => 'UF_CONTRACT',
                ];
                $arPropOrder = array_merge($arPropOrder, $arPropertyOrder1C);
            }
            $arPropCollection = $obOrder->getPropertyCollection();
            foreach ($arPropCollection as $obProp) {
                if (array_key_exists($obProp->getField('CODE'), $arPropOrder)) {
                    $code = $arPropOrder[$obProp->getField('CODE')];
                    $arFieldHl[$code] = $obProp->getValue();
                    if ($code === 'UF_DELIVERY_TYPE') {
                        $arFieldHl[$code] = OrderProps::getEnumIdByCode($code, $arFieldHl[$code]);
                    }
                }
            }
            try {
                $arFieldHl['UF_ARCHIVE'] = ($arFieldHl['UF_ARCHIVE'] === "Y" ? "Y" : "N");
                $arFieldHl['UF_TENDER'] = ($arFieldHl['UF_TENDER'] === "Y" ? "Y" : "N");

                $rsUser = UserEoTable::getList(
                    [
                        'filter' => ['ID' => $obOrder->getField('USER_ID')],
                        'select' => [
                            'TRADE_FORMAT_CODE' => 'TF.ID',
                            'TRADE_FORMAT_EMPLOYEE_CODE' => 'TF_EMPLOYEE_CLIENT.ID',
                        ],
                        'limit' => 1,
                    ]
                );

                if ($arUser = $rsUser->Fetch()) {
                    $arFieldHl['UF_TF_ID'] = $arUser['TRADE_FORMAT_CODE'] ?: $arUser['TRADE_FORMAT_EMPLOYEE_CODE'];
                }

                if ($obOrder->isNew()) {
                    $obCountry = Country\CountryEo::getByUserId();
                    $arFieldHl['UF_COUNTRY'] = $obCountry->getId();

                    $obPartner = \Opt\HighloadBlock\Erp\Partner\PartnerEo::getByUserId();
                    $isDeliveryAddress = $arFieldHl["UF_DELIVERY_ADDRESS"] && $arFieldHl["UF_DELIVERY_ADDRESS"] != 'none';
                    if ($isDeliveryAddress) {
                        $arFieldHl['UF_ADDRESS_NEW'] =
                            !in_array($arFieldHl["UF_DELIVERY_ADDRESS"], $obPartner->getUfDeliveryAddress());
                    }

                    $arFieldHl['UF_DATE_CONFIRM'] = $obOrder->getField('DATE_INSERT');
                    // сохранение позаказного набора
                    OrderPropsReady::addByOrderEntity($obOrder);

                    OrderProps::add($arFieldHl);
                    if (!empty($arFieldHl['UF_BONUS_AMOUNT'])) {
                        // уменьшение баланса
                        $rsResult = Bonus\Client::decreaseBalance(
                            [
                                'USER_ID' => $arFieldHl['UF_USER_ID'],
                                'AMOUNT' => (float) $arFieldHl['UF_BONUS_AMOUNT'],
                            ]
                        );
                        // запись в историю
                        if ($rsResult->isSuccess()) {
                            Bonus\History::add(
                                [
                                    'OPERATION' => 'WITHDRAW',
                                    'USER_ID' => $arFieldHl['UF_USER_ID'],
                                    'ORDER_ID' => $obOrder->getId(),
                                    'AMOUNT' => -1 * (float) $arFieldHl['UF_BONUS_AMOUNT'],
                                ]
                            );
                        }
                    }
                } else {
                    OrderPropsEoTable::updateChangedFields($obOrder->getId(), $arFieldHl);
                }

                // очистка кеша списка пользователей на странице /zakaz/cabinet/history/
                \CBitrixComponent::includeComponentClass("cabinet:orders");
                $obCache = Cache::createInstance();
                $obCache->CleanDir(\OrdersSo::getPathCache() . $arFieldHl['UF_CLIENT_ID']);
                // сброс кеша содержащего список акций в которых участвовали товары при оформлении заказа
                $obCache->CleanDir(Info::getInstance()->getClientActionsFromOrdersCache());
            } catch (\Exception $exception) {
                $obLog = Log::getLogAdapter();
                $obLog->errorPhp($exception->getMessage());
            }
        }
    }

    /**
     * @param Main\Event $obEvent
     *
     * @throws Main\SystemException
     */
    public static function onSaleOrderBeforeSavedHandler(Main\Event $obEvent)
    {
        global $USER;
        // привязка заказа к ИДП
        /** @var Order $obOrder */
        $obOrder = $obEvent->getParameter('ENTITY');
        if (!empty($obOrder) && $obOrder->isNew()) {
            $arPropCollection = $obOrder->getPropertyCollection();
            $valueBookmark = $arPropCollection->getItemByOrderPropertyCode(self::$codeBookmark)
                ? $arPropCollection->getItemByOrderPropertyCode(self::$codeBookmark)
                    ->getValue() : '';
            $valueReadyOrder = $arPropCollection->getItemByOrderPropertyCode(self::$codeReadyOrder)
                ? $arPropCollection->getItemByOrderPropertyCode(self::$codeReadyOrder)
                    ->getValue() : '';
            // проверка заказа на меньше минимальной суммы
            $sum = $obOrder->getField('PRICE');
            if (empty($valueBookmark)) {
                // проверка заказа на меньше минимальной суммы
                $obAccessOrder = new Access((float)$sum);
                if (!$obAccessOrder->canMakeOrder() && !($valueReadyOrder === 'Да' && $obAccessOrder->isReadyOrder())) {
                    $orderSum =
                        User::canMakeReadyOrder() ? $obAccessOrder->getReadyOrderMinSum() : $obAccessOrder->getMinSum();
                    return new EventResult(
                        EventResult::ERROR, new ResultError(
                        'У заказа сумма меньше минимальной суммы ' . $orderSum, 'ORDER_MIN_SUM'
                    ), 'sale'
                    );
                }
            }
            // извлечение свойств заказа
            $personTypeID = $obOrder->getPersonTypeId();
            $arPropList = getSaleOrderPropList($personTypeID);
            // установка значения св-ва
            $arValue = [];
            if (!empty($arPropList['DEALER_ID'])) {
                $arValue[$arPropList['DEALER_ID']['ID']] = Cfo::getCfoCode();
            }
            if (!empty($arPropList['CLIENT_ID'])) {
                $arValue[$arPropList['CLIENT_ID']['ID']] =
                    isUserClientManager() ? $USER->GetID() : Company::getManagerId();
            }
            global $USER;
            $userId = $USER->GetID();
            $obBonusOrder = \Opt\HighloadBlock\Bonus\Order::getInstance($userId, $sum, 'GLOBAL');
            if (!$obBonusOrder->canOrder()) {
                $arValue[$arPropList['BONUS_AMOUNT']['ID']] = null;
            }
            // сохранение свойства
            foreach ($arPropCollection as $obProp) {
                if (array_key_exists($obProp->getPropertyId(), $arValue)) {
                    $obProp->setValue($arValue[$obProp->getPropertyId()]);
                }
                // добаление значений свойств, если у пользователя нет действующих договоров
                $codeProperty = $obProp->getField('CODE');
                $arUpdateProperty = ['PAYER', 'CONSIGNEE', 'CONTRACT'];
                $isNeedUpdateProperty = in_array($codeProperty, $arUpdateProperty);
                if ($isNeedUpdateProperty) {
                    $valueProperty = $obProp->getValue();
                    if ($valueProperty === '' && !isUserErpPartner()) {
                        //выводим старый договор
                        if ($codeProperty === 'CONTRACT') {
                            $obContract = new \Opt\User\Client\Contract();
                            $arContract = $obContract->getByUserID($USER->GetID());
                            $newValueProperty = $arContract['ID'];
                        } else {
                            $newValueProperty = $USER->GetID();
                        }
                        $obProp->setValue($newValueProperty);
                    }
                }
            }

            // проверяем на заполненость обязательный полей
            if(!$obOrder->getId()) {
                $error = '';
                if (isUserErpPartner()) {
                    $deliveryType = $arPropCollection->getItemByOrderPropertyId($arPropList['DELIVERY_TYPE']['ID'])
                        ->getValue();
                    $deliveryTcName = $arPropCollection->getItemByOrderPropertyId($arPropList['DELIVERY_TC_NAME']['ID'])
                        ->getValue();
                    $deliveryUpAddress =
                        $arPropCollection->getItemByOrderPropertyId($arPropList['UP_ADDRESS_DELIV']['ID'])
                            ->getValue();

                    if (($deliveryType == 'ADDRESS' || $deliveryType == 'TC')
                        && (!$deliveryUpAddress || ctype_digit($deliveryUpAddress))
                    ) {
                        $error .= 'Необходимо указать новый адрес доставки' . "\r\n";
                    }
                    if ($deliveryType == 'TC_FROM_STOCK' && (!$deliveryTcName || ctype_digit($deliveryTcName))) {
                        $error .= 'Необходимо указать название транспортной компании'."\r\n";
                    }
                } else {
                    $payer = $arPropCollection->getItemByOrderPropertyId($arPropList['PAYER']['ID'])
                        ->getValue();
                    $consignee = $arPropCollection->getItemByOrderPropertyId($arPropList['CONSIGNEE']['ID'])
                        ->getValue();
                    $deliveryUpAddress =
                        $arPropCollection->getItemByOrderPropertyId($arPropList['UP_ADDRESS_DELIV']['ID'])
                            ->getValue();

                    if (!$payer || !ctype_digit($payer)) {
                        $error .= 'Необходимо указать плательщика'."\r\n";
                    }
                    if (!$consignee || !ctype_digit($consignee)) {
                        $error .= 'Необходимо указать грузополучателя'."\r\n";
                    }

                    if (!$deliveryUpAddress || ctype_digit($deliveryUpAddress)) {
                        $error .= 'Необходимо указать новый адрес доставки'."\r\n";
                    }
                }

                if ($error) {
                    return new EventResult(
                        EventResult::ERROR,
                        ResultError::create(new \Bitrix\Main\Error($error))
                    );
                }
            }
        }
    }

    /**
     * @param $newOrderId
     * @param $eventName
     * @param $arFields
     *
     * @return bool
     * @throws Main\ArgumentException
     * @throws Main\LoaderException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
    public static function onOrderNewSendEmailHandler($newOrderId, &$eventName, &$arFields)
    {
        global $USER;
        if ($eventName === 'SALE_NEW_ORDER' && isUserNeedApproveOrders()) {
            return false;
        }
        if ((int) $newOrderId > 0) {
            $bPrice = !userHasFlag('HIDE_PRICE');
            $strOrderList = "<table cellpadding=5>
			<tr>
				<th width=13px>№</th>
				<th width=65px>Код товара</th>
				<th width=440px>Наименование</th>
				<th width=40px>Кол-во</th>
				" . ($bPrice ? "<th width=70px>Цена за шт.</th><th width=70px>Сумма, руб</th>" : "") . "
			</tr>";
            $dbBasketItems = \CSaleBasket::GetList(
                ["NAME" => "ASC"],
                ["ORDER_ID" => $newOrderId],
                false,
                false,
                ["ID", "NAME", "QUANTITY", "PRICE", "PRODUCT_XML_ID"]
            );
            $i = 0;
            $arBasketItems = [];
            while ($arBasketItem = $dbBasketItems->Fetch()) {
                $arBasketItems[] = $arBasketItem;
            }
            foreach ($arBasketItems as $arBasketItem) {
                $i++;
                $class = ($i % 2 == 0) ? 'class="even"' : '';
                $summ = $arBasketItem['PRICE'] * (int) $arBasketItem['QUANTITY'];
                $strOrderList .= "
				<tr $class>
					<td align=center>$i</td>
					<td align=center>{$arBasketItem['PRODUCT_XML_ID']}</td>
					<td>{$arBasketItem['NAME']}</td>
					<td align=center>" . (int) $arBasketItem['QUANTITY'] . "</td>
					" . ($bPrice ? "<td align=right>" . PriceFormat($arBasketItem['PRICE']) . "</td><td align=right><b>"
                        . PriceFormat($summ) . "</b></td>" : "") . "
				<tr>\n";
            }
            $strOrderList .= "</table>";
            $arFields['ORDER_LIST'] = $strOrderList;
            $arUserFields = \CUser::GetByID($USER->GetID())
                ->Fetch();
            if ($arUserFields['EMAIL']) {
                $arFields['EMAIL'] = $arUserFields['EMAIL'];
            }
            $arFields['WORK_COMPANY'] = $arUserFields['WORK_COMPANY'];

            $cfoCode = \Opt\Main\Cfo::getCodeByUserId($arUserFields['ID']);
            $arFields['FILIAL_PHONE'] = \Opt\Main\Cfo::getProperty('PHONE', $cfoCode);

            $arOrderProp = OrderPropsValueTable::getRow(
                [
                    'filter' => ['ORDER_ID' => $newOrderId, 'CODE' => 'UP_ADDRESS_DELIV'],
                    'select' => ['DELIVERY_ADDRESS' => 'VALUE'],
                ]
            );
            if ($arOrderProp['DELIVERY_ADDRESS']) {
                $arFields['DELIVERY_ADDRESS'] = $arOrderProp['DELIVERY_ADDRESS'];
            }
            $arFields['CFO_EMAIL'] = Cfo::getProperty('MAIL');
            if ($bPrice) {
                $arFields['TOTAL_AMOUNT_ORDER'] =
                    '<b>Общая сумма заказа: <font color="D02433"><big>' . $arFields['PRICE']
                    . '</big></font></b><br><br>';
                $arFields['TOTAL'] =
                    '<span style="float:right;margin-right:10px"><b>Итого: <font color="D02433">' . $arFields['PRICE']
                    . '</font></b></span><br><br>';
            } else {
                $arFields['TOTAL_AMOUNT_ORDER'] = '';
                $arFields['TOTAL'] = '';
            }
        }
        return true;
    }

    /**
     * @param Main\Event $obEvent
     */
    public static function onSaleBasketItemEntitySavedHandler(Main\Event $obEvent)
    {
        Provider::ProgressBar('iteration',
            ($obEvent->getParameter("ENTITY")
                    ->getInternalIndex() + 1)
        );
    }

    /**
     * @param Order $obOrder
     */
    public static function onSaleComponentOrderResultPreparedHandler(Order $obOrder)
    {
        \Opt\Sale\Basket\Provider::ProgressBar('set', 15);
    }

    /**
     * @param $obOrder
     *
     * @throws Main\ArgumentException
     */
    public static function onSaleComponentOrderCreatedHandler(Order $obOrder)
    {
        if (isUserNeedApproveOrders($obOrder->getUserId())) {
            $obOrder->setField('STATUS_ID', 'CE');
        }
        $countItem = $obOrder->getBasket()
            ->count();
        \Opt\Sale\Basket\Provider::ProgressBar('init', $countItem);
    }

    /**
     *
     */
    public static function onSaleComponentOrderDeliveriesCalculatedHandler()
    {
        \Opt\Sale\Basket\Provider::ProgressBar('set', 10);
    }

    /**
     * Вызывается после получения данных (свойств заказа, платежной системы, службы доставки и т.п.), отправленных
     * клиентом.
     *
     * @param $arUserResult
     * @param $request
     * @param $arParams
     *
     * @throws Main\LoaderException
     */
    public static function onSaleComponentOrderUserResultHandler(&$arUserResult, $request, &$arParams)
    {
        $arBasketItemList = \Opt\Sale\Basket\Provider::BasketGet();
        $counter = \Opt\Sale\Basket\Provider::BasketCntNotMinPart($arBasketItemList);
        if ($counter > 0) {
            $deliveryServiceId = Cfo::getProperty('DELIVERY_SERVICE_ID');
            $deliveryExtraServiceId = Cfo::getProperty('DELIVERY_EXTRA_SERVICE_ID');
            if (!empty($deliveryServiceId) && !empty($deliveryExtraServiceId)) {
                $arUserResult['DELIVERY_EXTRA_SERVICES'][$deliveryServiceId][$deliveryExtraServiceId] = $counter;
            }
        }
    }

    /**
     * Сохранение сортировки корзины выбранной пользователем
     *
     * @param Main\Event $obEvent
     *
     * @return EventResult
     */
    public function OnSaleBasketBeforeSaved(Main\Event $obEvent): EventResult
    {
        $obBasket = $obEvent->getParameter("ENTITY");
        \Opt\Sale\Basket\Provider::setCurrentUserBasketSort($obBasket);

        return new EventResult(EventResult::SUCCESS);
    }
}
