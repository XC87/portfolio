<?
/**
 * Bitrix vars
 * @global CMain $APPLICATION
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Opt\Autotest\Demoaccessattribute as DemoAttr;

\Opt\Main\StaticFile::addJsComponentTemplateToFooter($component->getName());
\Opt\Main\StaticFile::addBufferToFooter('/js/jquery.mask.min.js');
\Opt\Main\StaticFile::addBufferToFooter('/js/global.js');
?>
<div class="contentWrapper Header">
	<h2 class="Header__title"><? $APPLICATION->ShowTitle() ?></h2>
</div>

<? if (!empty($arResult['FILTER'])) { ?>
	<div class="controlPanel controlPanel--filters">
        <?= $arResult['FILTER'] ?>
	</div>
<? } ?>
<? if (!empty($arResult['USER'])) { ?>
	<div class="Items  Items--demoAccess">
		<div class="Items__list" <?= DemoAttr::getAttribute(DemoAttr::DEMO_USER_LIST_DESK) ?>>
			<div class="Items__header">
				<div class="Item__box Item__box--role">Роль</div>
				<div class="Item__box Item__box--discount">Скидка</div>
				<div class="Item__box Item__box--date">Действует до</div>
				<div class="Item__box Item__box--login">Логин</div>
				<div class="Item__box Item__box--password">Пароль</div>
				<div class="Item__box Item__box--action"></div>
			</div>
			<div class="Items__group">
                <?php
                foreach ($arResult['USER'] as $arUser) {
                    ?>
					<div class="Item" <?= DemoAttr::getLoginDemoUser($arUser['LOGIN']) ?> <?= DemoAttr::getAttribute(DemoAttr::DEMO_ITEM_USER_DESK) ?>>
						<div class="Item__box Item__box--role" <?= DemoAttr::getAttribute(
                            DemoAttr::DEMO_ITEM_USER_ROLE_DESK
                        ) ?>><?= $arUser['GROUP_TEXT'] ?></div>
						<div class="Item__box Item__box--discount"><?= abs($arUser['UF_DISCOUNT']) ?>%</div>
						<div class="Item__box Item__box--date"><?
                            if ($arUser['UF_DATE_DEACTIVE']) {
                                echo $arUser['UF_DATE_DEACTIVE']->format('d.m.Y H:i');
							} ?></div>
						<div class="Item__box Item__box--login" <?= DemoAttr::getAttribute(
                            DemoAttr::DEMO_ITEM_USER_LOGIN_DESK
                        ) ?>><?= $arUser['LOGIN'] ?></div>
						<div class="Item__box Item__box--password" <?= DemoAttr::getAttribute(
                            DemoAttr::DEMO_ITEM_USER_PASSWORD_DESK
                        ) ?>><?= $arUser['PASSWORD'] ?></div>
						<div class="Item__box Item__box--action">
							<button class="btnCopy btnOutline js-copy" js-value-login-password="логин: <?= $arUser['LOGIN'] ?> пароль: <?= $arUser['PASSWORD'] ?>"></button>
						</div>
					</div>
                <?php } ?>
			</div>
		</div>
	</div>
<?php } else { ?>
	<?php
    echo(new \Samson\UI\Components\Lib\Universal\DataDisplay\Blank(
        'Нет активных демо-доступов',
        'Создайте новый демо-доступ, выбрав учётную запись, скидку, срок действия',
    ));
	?>
<?php } ?>
<?php if ($arResult['LAST_USER']) {
$arUser = $arResult['LAST_USER'];
?>
<div style="display:none">
	<div class="FancyModal FancyModal--createDemoAccess">
		<h2 class="FancyModal__header">Учетная запись создана</h2>
		<div class="FancyModal__content">
			<div class="Specs Specs--default">
				<div class="Specs__item">
					<div class="Specs__name">Роль</div>
					<div class="Specs__value"><?= $arUser['GROUP_TEXT'] ?></div>
				</div>

				<div class="Specs__item">
					<div class="Specs__name">Скидка</div>
					<div class="Specs__value"><?= abs($arUser['UF_DISCOUNT']) ?>%</div>
				</div>

				<div class="Specs__item">
					<div class="Specs__name">Действует до</div>
					<div class="Specs__value"><?= $arUser['UF_DATE_DEACTIVE']->format('d.m.Y H:i') ?></div>
				</div>

				<div class="Specs__item">
					<div class="Specs__name">Логин</div>
					<div class="Specs__value"><?= $arUser['LOGIN'] ?></div>
				</div>

				<div class="Specs__item">
					<div class="Specs__name">Пароль</div>
					<div class="Specs__value"><?= $arUser['PASSWORD'] ?></div>
				</div>
			</div>
		</div>
		<div class="FancyModal__control">
			<button class="btn btnMain js-fancybox-close js-copy" js-value-login-password="логин: <?= $arUser['LOGIN'] ?> пароль: <?= $arUser['PASSWORD'] ?>"
			">Скопировать и закрыть</button>
			<button class="btn btnMain btnOutline js-fancybox-close">Закрыть</button>
		</div>
	</div>
    <?php } ?>
</div>