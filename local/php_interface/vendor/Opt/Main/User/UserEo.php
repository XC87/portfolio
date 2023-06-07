<?php

namespace Opt\Main\User;

use Opt\HighloadBlock\TradeFormat\TradeFormatEoTable;

/**
 * Class UserEo
 * @package Opt\Main\User
 *
 * @method getId
 * @method getActive
 * @method setActive($value)
 * @method getLogin
 * @method setLogin($value)
 * @method getEmail
 * @method setEmail($value)
 * @method getWorkCompany
 * @method setWorkCompany($value)
 * @method getUfWorkNameCompany
 * @method setUfWorkNameCompany($value)
 * @method getUfManager
 * @method setUfManager($value)
 * @method getUfLogin1c
 * @method getUfBasketSort
 * @method fillUfLogin1c
 *
 * @mixin \Bitrix\Main\ORM\Objectify\EntityObject
 */
class UserEo extends EO_UserEo
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;

    public static function getCurrent() :?UserEo
    {
        global $USER;
        if ($USER->IsAuthorized()) {
            $rsUser = \Opt\Main\User\UserEoTable::getById($USER->GetID());
            /** @var \Opt\Main\User\UserEo $arUser */
            if ($arUser = $rsUser->FetchObject()) {
                return $arUser;
            }
        }
        return null;
    }

    public function getTradeFormat() {
        return $this->fillTf() ?? $this->fillTfEmployeeClient() ?? (new TradeFormatEoTable)->createObject();
    }

    public function getActionTradeFormat() {
        return $this->fillTfAction() ?? $this->fillTfActionEmployeeClient() ?? (new TradeFormatEoTable)->createObject();
    }
}