<?php

namespace Opt\HighloadBlock\Helpers;

use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

abstract class AbstractUfTable extends DataManager
{

    public static function getObjectClass()
    {
        return str_replace('EoTable', 'Eo', get_called_class());
    }

    public static function getCollectionClass()
    {
        return str_replace('EoTable', 'EoCollection', get_called_class());
    }

    public static function getMap()
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'required' => true,
            ]),
            new TextField('VALUE', [
                'required' => true,
            ]),
        ];
    }

    public static function generateParentMap(string $fieldName, string $tableClass): array
    {
        return [
            (new ArrayField($fieldName))->configureSerializationPhp(),
            new ReferenceField($fieldName . '_TABLE', $tableClass, ['=this.ID' => 'ref.ID']),
            new ExpressionField($fieldName . '_SINGLE', '%s', $fieldName . '_TABLE.VALUE', [
                'data_type' => IntegerField::class,
            ]),
        ];
    }
}