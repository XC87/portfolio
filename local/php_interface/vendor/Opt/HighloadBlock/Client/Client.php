<?php
declare(strict_types=1);

namespace Opt\HighloadBlock\Client;

class Client extends \Opt\HighloadBlock\Base
{
    /**
     * ��������� �������� ��������
     * @return string
     */
    static function getEntityName(): string
    {
        return 'Client';
    }
}