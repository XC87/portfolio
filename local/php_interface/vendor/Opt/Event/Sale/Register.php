<?php

namespace Opt\Event\Sale;

$obEventManager = \Bitrix\Main\EventManager::getInstance();

// ���������� ����� ���������� ������ � �������
$obEventManager->addEventHandler(
    'sale',
    'OnBasketAdd',
    [Handler::class, 'onBasketAddHandler']
);

// ���������� ��� ���������� �������
$obEventManager->addEventHandler(
    'sale',
    'OnSaleBasketItemRefreshData',
    [Handler::class, 'onSaleBasketItemRefreshDataHandler']
);

// ���������� ����� ��������� ������ � ����� ������� ������
$obEventManager->addEventHandler(
    'sale',
    'OnOrderStatusSendEmail',
    [Handler::class, 'onOrderStatusSendEmailHandler']
);

// ���������� ��� ����������, ���� ������ ������ ��� �������
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

// ���������� � ����� ����������, ����� ����� � ��� ��������� �������� ��� ���������
$obEventManager->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    [Handler::class, 'onSaleOrderSavedHandler']
);

// ���������� � ����� ������ �������� ����������
$obEventManager->addEventHandler(
    'sale',
    'OnSaleOrderBeforeSaved',
    [Handler::class, 'onSaleOrderBeforeSavedHandler']
);

// 	���������� ����� ��������� ������ � ����� ������
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