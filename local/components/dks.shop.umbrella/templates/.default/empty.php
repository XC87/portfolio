<?php
/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @var array $arResult
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<div class="contentWrapper Header">
    <h2 class="Header__title"><? $APPLICATION->ShowTitle() ?></h2>
</div>
<?= $arResult['strFilter'] ?>
<?= (new \Samson\UI\Components\Lib\Universal\DataDisplay\Blank(
    Loc::getMessage('SO_NO_FOUND'),
    Loc::getMessage('SO_NO_FOUND_URL', ['#URL#' => $arResult['LIST_URL']]),
)); ?>