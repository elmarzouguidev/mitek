<?php

namespace App\Services\Core\Product;

use Random\RandomException;

class ProductReferenceGenerator
{
    private const Alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /** @throws RandomException */
    public function generate(): string
    {
        return 'MP-'.$this->segment().'-'.$this->segment();
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
