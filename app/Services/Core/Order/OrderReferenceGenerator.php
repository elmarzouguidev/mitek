<?php

namespace App\Services\Core\Order;

use Random\RandomException;

class OrderReferenceGenerator
{
    private const Alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /** @throws RandomException */
    public function generate(): string
    {
        return 'MO-'.$this->segment().'-'.$this->segment();
    }

    /** @throws RandomException */
    private function segment(): string
    {
        $segment = '';
        $lastIndex = strlen(self::Alphabet) - 1;
        for ($index = 0; $index < 6; $index++) {
            $segment .= self::Alphabet[random_int(0, $lastIndex)];
        }

        return $segment;
    }
}
