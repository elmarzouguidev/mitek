<?php

namespace App\Support;

/**
 * Suggestions for the city field (checkout and shipping zones). Customers may still type any city:
 * shipping zones are matched on the typed name, with unknown cities falling back to the default zone.
 */
final class MoroccanCities
{
    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            'Agadir', 'Al Hoceïma', 'Azrou', 'Beni Mellal', 'Berkane', 'Berrechid', 'Casablanca', 'Chefchaouen',
            'Dakhla', 'El Jadida', 'Errachidia', 'Essaouira', 'Fès', 'Guelmim', 'Ifrane', 'Kénitra', 'Khémisset',
            'Khouribga', 'Laâyoune', 'Larache', 'Marrakech', 'Meknès', 'Mohammedia', 'Nador', 'Ouarzazate', 'Oujda',
            'Rabat', 'Safi', 'Salé', 'Settat', 'Tanger', 'Taza', 'Témara', 'Tétouan',
        ];
    }
}
