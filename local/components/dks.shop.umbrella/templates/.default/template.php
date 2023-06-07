<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @var       $APPLICATION CMain
 */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$APPLICATION->SetPageProperty('MainContentClass', 'Page--dksSiteManagement');
\Opt\Main\StaticFile::addJsComponentTemplateToFooter($component->getName());
?>
<div class="contentWrapper Header">
    <h2 class="Header__title"><? $APPLICATION->ShowTitle() ?></h2>
</div>
<?= $arResult['strFilter'] ?>
<?= $arResult["NAV_STRING"] ?>
<div class="Items Items--dksSites">
    <div class="Items__list">
        <div class="Items__header">
            <div class="Item__box Item__box--login"><?= Loc::getMessage('SO_LOGIN') ?></div>
            <div class="Item__box Item__box--site"><?= Loc::getMessage('SO_SITE') ?></div>
            <div class="Item__box Item__box--identifier"><?= Loc::getMessage('SO_IDENTIFIER') ?></div>
            <div class="Item__box Item__box--store"><?= Loc::getMessage('SO_NAME') ?></div>
            <div class="Item__box Item__box--city"><?= Loc::getMessage('SO_CITY_MAIN') ?></div>
            <div class="Item__box Item__box--serviceCities"><?= Loc::getMessage('SO_CITY') ?></div>
        </div>

        <?
        foreach ($arResult["ITEM"] as $arSite) {
            ?>
            <div class="Item">
                <div class="Item__box Item__box--login"><a href="<?= str_replace(
                        '#ID#',
                        $arSite['ID'],
                        $arParams['DETAIL_URL']
                    ) ?>"><?= $arSite['LOGIN'] ?></a></div>
                <div class="Item__box Item__box--site"><?= $arSite['UF_SITE'] ?: '&mdash;' ?></div>
                <div class="Item__box Item__box--identifier"><?= $arSite['UF_IDENTIFIER'] ?: '&mdash;' ?></div>
                <div class="Item__box Item__box--store"><?= $arSite['UF_SHOP_TITLE'] ?: '&mdash;' ?></div>
                <div class="Item__box Item__box--city"><?= $arSite['MAIN_REGION'] ?: '&mdash;' ?></div>
                <div class="Item__box Item__box--serviceCities"><?= $arSite['REGIONS'] ?: '&mdash;' ?></div>
            </div>
            <?
        }
        ?>
    </div>
</div>
<?= $arResult["NAV_STRING"] ?>
