<?php
declare(strict_types=1);

namespace Opt\Main\User;

use Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\FloatField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\TextField;

class UtsUserTable extends DataManager
{
	public static function getTableName()
	{
		return 'b_uts_user';
	}

	public static function getMap()
	{
		return [
			new IntegerField(
				'VALUE_ID',
				[
					'primary' => true,
					'default' => 0
				]
			),
			new IntegerField(
				'UF_COD'
			),
			new TextField(
				'UF_COMPANY'
			),
			new TextField(
				'UF_CITY'
			),
			new IntegerField(
				'UF_DILLER'
			),
			new TextField(
				'UF_IDP'
			),
			new FloatField(
				'UF_ORDER_LIMIT'
			),
			new IntegerField(
				'UF_BOOKMARK_LIMIT'
			),
			new TextField(
				'UF_BOOKMARK_COMMENTS'
			),
			new IntegerField(
				'UF_IS_INDIVIDUAL'
			),
			new TextField(
				'UF_MANAGER'
			),
			new TextField(
				'UF_IM_SEARCH'
			),
			new TextField(
				'UF_LINK_CLIENT'
			),
			new IntegerField(
				'UF_IS_SUBSCRIBED'
			),
			new TextField(
				'UF_FAVORITE_GOODS'
			),
			new FloatField(
				'UF_DEALER_ID'
			),
			new IntegerField(
				'UF_HIDE_PRICE'
			),
			new FloatField(
				'UF_DISCOUNT'
			),
			new TextField(
				'UF_LOGIN1C'
			),
			new TextField(
				'UF_PORTAL_ID'
			),
			new IntegerField(
				'UF_STUDENT'
			),
			new TextField(
				'UF_TOUR'
			),
			new TextField(
				'UF_ADV_CHANNEL_LIST'
			),
			new IntegerField(
				'UF_RESET_DIALER'
			),
			new DatetimeField(
				'UF_DATE_DEACTIVE'
			),
			new IntegerField(
				'UF_BASKET_SORT'
			),
		];
	}
}
