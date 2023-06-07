<?php

namespace Opt\Event\Sale;

$obEventManager = \Bitrix\Main\EventManager::getInstance();

// Вызывается после добавления записи в корзину
$obEventManager->addEventHandler(
    'sale',
    'OnBasketAdd',
    [Handler::class, 'onBasketAddHandler']
);

// Вызывается при обновлении корзины
$obEventManager->addEventHandler(
    'sale',
    'OnSaleBasketItemRefreshData',
    [Handler::class, 'onSaleBasketItemRefreshDataHandler']
);

// Вызывается перед отправкой письма о смене статуса заказа
$obEventManager->addEventHandler(
    'sale',
    'OnOrderStatusSendEmail',
    [Handler::class, 'onOrderStatusSendEmailHandler']
);

// Вызывается при сохранении, если статус заказа был изменен
$obEventManager->addEventHandler(
    'sale',
    'OnSaleStatusOrderChange',
    [Handler::class, 'onSaleStatusOrderChangeHandler']
);

$obEventManager->addEventHandler(
    'sale',
    'OrderPropsValueOnAfterUpdate',
    [Handler::class, 'onOrderPropsValueUpdateHandler']
);

$obEventManager->addEventHandler(
    'sale',
    'OrderPropsValueOnAfterAdd',
    [Handler::class, 'onOrderPropsValueUpdateHandler']
);

// Происходит в конце сохранения, когда заказ и все связанные сущности уже сохранены
$obEventManager->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    [Handler::class, 'onSaleOrderSavedHandler']
);

// Происходит в самом начале процесса сохранения
$obEventManager->addEventHandler(
    'sale',
    'OnSaleOrderBeforeSaved',
    [Handler::class, 'onSaleOrderBeforeSavedHandler']
);

// 	Вызывается перед отправкой письма о новом заказе
$obEventManager->addEventHandler(
    'sale',
    'OnOrderNewSendEmail',
    [Handler::class, 'onOrderNewSendEmailHandler']
);

$obEventManager->addEventHandler(
    'sale',
    'OnSaleBasketItemEntitySaved',
    [Handler::class, 'onSaleBasketItemEntitySavedHandler']
);

$obEventManager->addEventHandler(
    'sale',
    'OnSaleComponentOrderResultPrepared',
    [Handler::class, 'onSaleComponentOrderResultPreparedHandler']
);

$obEventManager->addEventHandler(
    'sale',
    'OnSaleComponentOrderCreated',
    [Handler::class, 'onSaleComponentOrderCreatedHandler']
);

$obEventManager->addEventHandler(
    'sale',
    'OnSaleComponentOrderDeliveriesCalculated',
    [Handler::class, 'onSaleComponentOrderDeliveriesCalculatedHandler']
);

$obEventManager->addEventHandler(
    'sale',
    'OnSaleComponentOrderUserResult',
    [Handler::class, 'onSaleComponentOrderUserResultHandler']
);

$obEventManager->addEventHandler(
    'sale',
    'OnSaleBasketBeforeSaved',
    [Handler::class, 'OnSaleBasketBeforeSaved']
);