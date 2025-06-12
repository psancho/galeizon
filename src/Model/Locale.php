<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Model;

use Psr\Http\Message\ServerRequestInterface;

class Locale
{
    /**
     * À partir du contenu du header Accept-language,
     * construit le tableau des locales, ordonnées par indice de qualité
     *
     * par ex: "fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5"
     *
     * par défaut, l'indice q (qualité) vaut 1
     *
     * @return list<string>
     */
    public static function headerToOrderedArray(string $header): array
    {
        $localeList = array_map('trim', explode(',', $header));
        array_walk($localeList, function (&$entry, $key) {
            $entry = array_map('trim', explode(';', $entry));
            if (count($entry) === 1) {
                $entry[] = 1.0;
            } else {
                preg_match('/q=(.*)/', $entry[1], $matches);
                assert(array_key_exists(1, $matches));
                $entry[1] = (float) $matches[1];
            }
            $entry[] = $key;
        });

        usort($localeList, function ($left, $right) {
            $test = $right[1] <=> $left[1];
            return $test === 0 ? ($left[2] <=> $right[2]) : $test;
        });

        return array_column($localeList, 0);
    }

    /**
     * Construit le tableau ordonné des langues acceptées
     *
     * à partir du header Accept-language ou du param locale (clé modifiable).
     *
     * Si le param locale est renseigné, il est prioritaire sur le header
     *
     * @param string $localeKey param query pour la locale, 'locale' par défaut
     *
     * @return list<string>
     */
    public static function localesFromRequest(ServerRequestInterface $request, string $localeKey = 'locale'): array
    {
        $headers = $request->getHeader('Accept-language');
        $headerLang = reset($headers) ?: '';
        $acceptedLocales = self::headerToOrderedArray($headerLang);
        $firstLocale = self::getFromRequestQuery($request, $localeKey);
        if ($firstLocale !== '') {
            array_unshift($acceptedLocales, $firstLocale);
        }
        return $acceptedLocales;
    }

    private static function getFromRequestQuery(ServerRequestInterface $request, string $key): string
    {
        /** @var list<string> $queryParams */
        $queryParams = $request->getQueryParams();
        return array_key_exists($key, $queryParams) ? $queryParams[$key] : '';
    }

    /**
     * Sélectionne la meilleure correspondance entre les locales demandées et celles disponibles
     *
     * Si auncune correspondance n'est trouvée, retourne la 1ère locale demandée ou une chaîne vide à défaut
     *
     * @param list<string> $demand    liste des locales acceptées, ordonnée par préférence
     * @param list<string> $available liste des locales disponibles dans le jeu de données
     */
    public static function chooseLocale(array $demand, array $available, string $default = ''): string
    {
        if ($default !== '') {
            $demand[] = $default;
        }

        foreach ($demand as $locale) {
            if (in_array($locale, $available, true)) {
                return $locale;
            }
        }

        foreach ($demand as $locale) {
            $lang = explode('-', $locale)[0];
            if (in_array($lang, $available, true)) {
                return $lang;
            }
        }

        foreach ($demand as $locale) {
            $lang = explode('-', $locale)[0];
            $langs = array_filter($available, function ($locale) use ($lang) {
                return explode('-', $locale)[0] === $lang;
            });
            if (count($langs) > 0) {
                return reset($langs);
            }
        }

        return reset($demand) ?: '';
    }
}
