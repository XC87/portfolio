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

$APPLICATION->SetPageProperty('MainContentClass', 'Page--dksSiteManagementEdit');
\Opt\Main\StaticFile::addJsComponentTemplateToFooter($component->getName());
?>
<div class="contentWrapper Header">
    <h2 class="Header__title">Редактирование сайта</h2>
</div>

<div class="controlPanel controlPanel--filters">
    <ul class="contentWrapper filters filters--defined">
        <li class="filter">
            <h4 class="filter__header">Логин</h4>
            <div class="filter__data"><?= $arResult['LOGIN'] ?></div>
        </li>
        <?php if ($arResult['UF_SITE']) { ?>
            <li class="filter">
                <h4 class="filter__header">Сайт</h4>
                <div class="filter__data"><?= $arResult['UF_SITE'] ?></div>
            </li>
        <?php } ?>
        <li class="filter">
            <h4 class="filter__header">ID магазина</h4>
            <div class="filter__data"><?= $arResult['ID'] ?></div>
        </li>
    </ul>
</div>

<form class="Form" action="?ID=<?= $arResult['ID'] ?>" method="post">
    <input type="hidden" name="EDIT_ID" value="<?= $arResult['ID'] ?>">
    <input type="hidden" name="OLD_MAIN_REGION" value="<?= $arResult['MAIN_REGION_UUID'] ?>">
    <div class="contentWrapper">
        <div class="Form__set">
            <div class="Form__field inputFull">
                <div class="Form__title">
                    <label for="identifier">Идентификатора магазина</label>
                </div>
                <input class="Form__input required" id="identifier" type="text" name="UF_IDENTIFIER" value="<?= $arResult['UF_IDENTIFIER'] ?>">
                <div class="Form__error"><?= $arResult['ERROR_LIST']['UF_IDENTIFIER'] ?></div>
            </div>

            <div class="Form__field inputFull">
                <div class="Form__title">
                    <label for="store">Название магазина</label>
                </div>
                <input class="Form__input required" id="store" type="text" name="UF_SHOP_TITLE" value="<?= $arResult['UF_SHOP_TITLE'] ?>">
                <div class="Form__error"><?= $arResult['ERROR_LIST']['UF_SHOP_TITLE'] ?></div>
            </div>

            <div class="Form__field inputFull Form__field--cities">
                <div class="Form__title">Города обслуживания</div>

                <?php
                $APPLICATION->IncludeComponent(
                    "widget:rich.select",
                    '',
                    [
                        'NAME' => 'REGIONS[]',
                        'ITEMS' => $arResult['REGION_LIST'],
                        'SELECTED' => $arResult['REGIONS_UUID'],
                        'CLASS' => 'js-regions',
                        'PLACEHOLDER' => 'Не выбрано',
                        'SHOW_FILTER' => true,
                        'FILTER_CLASS' => 'js-richSelectFilterCities',
                        'SHOW_SELECT_ALL' => true,
                    ]
                ); ?>
            </div>

            <div class="Form__field inputFull Form__field--cities">
                <div class="Form__title">Основной город</div>
                <select name="REGION_MAIN" class="Form__input js-mainregions">
                    <option value="">Не выбрано</option>
                    <?php
					foreach ($arResult['MAIN_REGION_LIST'] as $value => $name) { ?>
                        <option value="<?= $value ?>" <?= $arResult['MAIN_REGION_UUID'] == $value ? ' selected'
                            : '' ?>><?= $name ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
    </div>
    <div class="contentWrapper controlPanel">
        <button class="btn btnMain Spinner--potential">
            <span class="Spinner"></span>
            Сохранить
        </button>
        <button class="btn btnMain btnOutline" type="button"
                onclick="window.location='/desk/dks/shop/umbrella/'">Отмена
        </button>
    </div>
</form>