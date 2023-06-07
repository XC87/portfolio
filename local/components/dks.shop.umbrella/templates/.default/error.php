<?php
/**
 * Bitrix vars
 * @var array $arResult
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<?= (new \Samson\UI\Components\Lib\Universal\DataDisplay\Blank(
    $arResult['ERROR_TEXT'],
)); ?>