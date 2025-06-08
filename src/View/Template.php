<?php

declare(strict_types = 1);

namespace Psancho\Galeizon\View;

use InvalidArgumentException;
use LogicException;
use Psancho\Galeizon\Pattern\Singleton;

class Template extends Singleton
{
    /** @var array<string, string> */
    public protected(set) array $dirpathes = [];

    public const string CORE = "core";
    public const string ROOT = "root";

    #[\Override]
    protected function build(): void
    {
        $this->push(self::CORE,  dirname(__DIR__, 2) . "/tpl/");
        $this->push(self::ROOT,  dirname(__DIR__, 5) . "/tpl/");
    }

    /**
     * si d'autres paramètres sont fournis, le template est modifié:<br>
     *  - avec str_replace si un tableau clés/valeurs est fourni,
     *  - avec sprintf dans le cas contraire
     *
     * @param string                       $name chemin relatif à /tpl du fichier à utiliser
     * @param string                       $set  jeu, par défaut le jeu du projet racine
     * @param null|array<string, string>|scalar  $param
     *     si array: alors tableau clés/valeurs pour alimenter str_replace
     *     sinon: 1er d'une liste de valeurs pour alimenter sprintf
     *
     * @throws InvalidArgumentException
     */
    public function format(string $name,
        string $set = self::ROOT,
        null|array|int|float|string|bool $param = null
    ): string
    {
        $argCount = func_num_args();
        if (0 === $argCount) {
            throw new InvalidArgumentException('Method expects at least 1 argument.');
        }

        $tpl = $this->load($name, $set);

        if (2 === $argCount) {
            // argument unique: pas de modif du tpl
            return $tpl;

        } else if (is_array($param)) {
            // 2e argument est un array, donc str_replace
            /** @var array<string, string> $param */
            $search = array_keys($param);
            $replace = array_values($param);
            $result = call_user_func('str_replace', $search, $replace, $tpl);
            assert(is_string($result));
            return $result;

        } else {
            // sauter le nom du template
            $argList = array_slice(func_get_args(), 1);
            //insertion du template comme 1er param de sprintf
            array_unshift($argList, $tpl);
            $result = call_user_func_array('sprintf', $argList);
            assert(is_string($result));
            return $result;
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    protected function load(string $name, string $set): string
    {
        $filePath = $this->dirpathes[$set]. $name . '.tpl';
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Template \"$set:$name\" not found.");
        }
        $data = file_get_contents($filePath);
        if ($data === false) {
            throw new LogicException("Error while reading template \"$set:$name\".");
        }
        return $data;
    }

    /**
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function push(string $set, string $dirPath): void
    {
        if ($set === '' || $dirPath === '') {
            throw new InvalidArgumentException('Parameters cannot be empty.');
        } elseif (array_key_exists($set, $this->dirpathes)) {
            throw new LogicException("Instance [$set] already declared.");
        }
        $this->dirpathes[$set] = $dirPath;
    }
}
