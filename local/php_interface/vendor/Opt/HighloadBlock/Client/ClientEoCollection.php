<?php

namespace Opt\HighloadBlock\Client;

class ClientEoCollection extends EO_ClientEo_Collection
{
    use \Opt\Main\Cache;
    use \Opt\Main\Orm;

    private $arUserToClientId = null;

    public function getByUserId($userId): \Opt\HighloadBlock\Client\ClientEo
    {
        if (is_null($this->arUserToClientId)) {
            $this->arUserToClientId = [];
            foreach ($this as $obClient) {
                $this->arUserToClientId[$obClient->getUfUserId()] = $obClient->getId();
            }
        }
        if (array_key_exists($userId, $this->arUserToClientId)) {
            return $this->getByPrimary($userId);
        } else {
            return new ClientEo();
        }
    }
}