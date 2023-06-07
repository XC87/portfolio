<?php

namespace Opt\HighloadBlock\Client\Location;

use Bitrix\Main;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class PriceEoTable extends Main\Entity\DataManager
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;

    public static function getObjectClass()
    {
        return PriceEo::class;
    }

    public static function getCollectionClass()
    {
        return PriceEoCollection::class;
    }

    public static function getTableName()
    {
        return 'hl_client_location_price';
    }

    public static function getEntityName()
    {
        return 'ClientLocationPrice';
    }

    public static function getMap()
    {
        return [
            'ID' => new IntegerField(
                'ID', [
                    'primary' => true,
                    'autocomplete' => true,
                ]
            ),
            'UF_PRICE_TYPE' => new StringField('UF_PRICE_TYPE'),
            'UF_CLIENT_CFO' => new StringField('UF_CLIENT_CFO'),
            'UF_PRICE_CFO' => new StringField('UF_PRICE_CFO'),
        ];
    }
}
