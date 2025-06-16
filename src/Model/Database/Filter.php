<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Model\Database;

/**
 * Permet de compléter une SQL avec:
 * <ul><li>la clause WHERE, basée sur une liste de propriétés</li>
 * <li>la liste des paramètres associée à la liste de propriétés de la clause WHERE</li>
 * <li>la clause SORT, basée sur une chaîne simple</li>
 * <li>la clause LIMIT, basée sur une pagination (taille de page, n° de page)</li></ul>
 * Toute classe étendant cette abstraction DOIT implémenter la méthode getClause()
 *
 * Exemple:<pre>
 * $where = '';
 * $clause =  $filter->getClause();
 * if (!empty($clause->clauseList)) {
 *     $where = "\nwhere " . implode (' and ', $clause->clauseList);
 * }
 *
 * $groupBy = "\ngroup by my_group_clause";
 *
 * $orderBy = '';
 * if (!empty($filter->sort)) {
 *     $orderBy = "\norder by " . implode (', ', $filter->sort);
 * }
 *
 * $limit = '';
 * if (!empty($filter->paging)) {
 *     $limit = sprintf("\nlimit %d, %d", $filter->paging->offset, $filter->paging->perPage);
 * }
 *
 * $sql = $selectFrom . $where . $groupBy . $orderBy . $limit;</pre>
 */
abstract class Filter
{
    /**
     * Cette variable DOIT être redéfinie si parseSort() est utilisé
     *
     * @var list<string>
     */
    protected static array $columnList = [];
    /**
     * Cette variable PEUT être redéfinie si les critères de la $columnList ont besoins d'être réécrits
     *
     * @var array<string, string>
     */
    protected static array $replaceSorts = [];

    public function __construct(
        public ?Paging $paging = null,
        /** @var array<string> */
        public array $sort = [],
    )
    {
        $this->setClauseWhere();
    }

    /** @return $this */
    abstract protected function setClauseWhere(): self;

    public function where(): string
    {
        return '';
    }

    public function orderBy(): string
    {
        return '';
    }

    public function limit(): string
    {
        return '';
    }

    /**
     * parse et injecte la chaîne de tri
     *
     * @param string $sortString  liste séparée par des ','
     *        chaque item pouvant être préfixé par '-' pour indiquer l'ordre inverse
     *
     * @return $this
     */
    public function parseSort(string $sortString): self
    {
        $sortList = explode(',', $sortString);

        $usedList = [];
        $this->sort = [];
        $re = "/^\s*(-?)\s*(\w+)\s*$/";

        foreach ($sortList as $sortItem) {
            $match = null;
            $count = preg_match($re, $sortItem, $match);

            // je teste si...:
            // - j'ai un critère fait avec des lettres
            // - qui n'est pas vide
            // - qui n'est pas déjà utilisé dans la chaîne sort
            // - et qui correspond à une colonne BDD
            if (is_int($count) && $count > 0 && isset($match[2]) && strlen($match[2]) > 0
                && !in_array($match[2], $usedList, true)
                && in_array($match[2], static::$columnList, true)
            ) {
                $usedList[] = $match[2];
                $direction = (isset($match[1]) && strlen($match[1]) > 0) ? ' DESC' : '';
                $critrerium = array_key_exists($match[2], static::$replaceSorts)
                    ? static::$replaceSorts[$match[2]]
                    : '`' . $match[2] . '`';
                $this->sort[] = $critrerium . $direction;
            }
        }

        return $this;
    }
}
