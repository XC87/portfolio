<?php
namespace Opt\Main\User;

class UserEoCollection extends EO_UserEo_Collection
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;
    /**
     * @param string $manager1cLogin
     * @param bool $isDemo
     *
     * @return self|\Opt\Main\User\UserEo[]
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getForManager(string $manager1cLogin, bool $isDemo = false): self
    {
        $obQuery = UserEoTable::query();
        $obQuery->setSelect(
            [
                'ID',
                'DATE_REGISTER',
                'LOGIN',
                'EMAIL',
                'ACTIVE',
                'WORK_COMPANY',
                'UF_MANAGER',
                'UF_DISCOUNT',
                'UF_DATE_DEACTIVE',
                'IS_CLIENT_MANAGER',
                'GROUPS'
            ]
        );
        $obQuery->where('UF_MANAGER', $manager1cLogin);
        $obQuery->where('ACTIVE', 'Y');
        if ($isDemo) {
            $obQuery->whereLike('LOGIN', 'demo\_%');
        }
        $obQuery->exec();
        $obCollection = $obQuery->fetchCollection();
        return $obCollection;
    }
}
