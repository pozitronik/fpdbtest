<?php
declare(strict_types=1);

namespace pozitronik\FpDbTest;

use Exception;
use mysqli;

/**
 * Class Database
 * @public string $valueQuoteCharacter Строка, используемая для экранирования строк в значениях
 * @public string $identifierQuoteCharacter Строка, используемая для экранирования строк в идентификаторах
 * @public bool $allowMarkerEscape true: разрешает экранирование символа '?' как '??'. false: подстановка производится всегда
 * @public mixed $ignoreMarker Значение, используемое, как skip-маркер
 */
class Database implements DatabaseInterface
{

    public string $valueQuoteCharacter = "'";
    public string $identifierQuoteCharacter = "`";
    public bool $allowMarkerEscape = true;
    public mixed $skipMarker = '/*!IGNORE!*/';

    /** @noinspection PhpPropertyOnlyWrittenInspection Атрибут требуется в задании, но для демонстрации исполнения не используется */
    private mysqli|null $mysqli = null;


    /**
     * @param mysqli|null $mysqli
     */
    public function __construct(mysqli|null $mysqli = null)
    {
        /** @noinspection UnusedConstructorDependenciesInspection */
        $this->mysqli = $mysqli;
    }

    /**
     * Разбивает строку на токены условных блоков, проверяя корректность выражений
     * @param string $query
     * @return array
     * @throws Exception
     */
    private function tokenizeQueryConditions(string $query): array
    {
        $length = strlen($query);
        $result = [];
        $current = '';
        $depth = 0;

        for ($i = 0; $i < $length; $i++) {
            $char = $query[$i];

            // Проверяем начало блока
            if ('{' === $char) {
                if (0 === $depth) { // Если это начало нового блока
                    if ('' !== $current) {
                        $result[] = [
                            'condition' => false,
                            'value' => $current
                        ];
                    }
                    $current = '';
                } elseif ($depth > 0) {
                    throw new Exception("Nested conditional expression");
                }
                $depth++;
            } elseif ('}' === $char) {
                $depth--;
                if (0 === $depth) { // Если это конец блока
                    if ('' !== $current) {
                        $result[] = [
                            'condition' => true,
                            'value' => $current
                        ];
                    }
                    $current = '';
                } elseif ($depth < 0) {
                    throw new Exception("Unmatched braces");
                }
            } else {
                $current .= $char;
            }
        }

        if ($depth > 0) {
            throw new Exception("Незакрытая открывающая скобка");
        }

        if ('' !== $current) {
            $result[] = [
                'condition' => false,
                'value' => $current
            ];
        }

        return $result;
    }

    /**
     * Подставляет значения вместо маркеров внутри переданной подстроки
     * @param string $subQuery
     * @param array $args
     * @param bool $conditional
     * @return string
     * @throws Exception
     */
    public function buildSubQuery(string $subQuery, array &$args = [], bool $conditional = false): string
    {
        // Инициализация результата и текущей позиции в строке
        $result = '';
        $pos = 0;
        $length = strlen($subQuery);

        // Обработка шаблона
        while ($pos < $length) {
            // Находим следующий вопросительный знак
            $nextPos = strpos($subQuery, '?', $pos);
            if (false === $nextPos) {
                $result .= substr($subQuery, $pos);
                break;
            }

            // Добавляем текст до вопросительного знака
            $result .= substr($subQuery, $pos, $nextPos - $pos);
            $pos = $nextPos;

            if ($this->allowMarkerEscape && (isset($subQuery[$pos + 1]) && '?' === $subQuery[$pos + 1])) {// Проверяем, следует ли за вопросительным знаком другой вопросительный знак
                $result .= '?';
                $pos += 2; // Пропускаем оба знака вопроса
                continue;
            }

            $specifier = $subQuery[$pos + 1] ?? '';
            $pos += 2;

            if (null === $value = array_shift($args)) { // Извлекаем первый элемент параметров
                throw new Exception("Insufficient parameters");
            }
            if ($conditional && $value === $this->skip()) { // внутри условного выражения встречен маркер пропуска
                return '';
            }
            switch ($specifier) {
                case 'd':
                    $result .= (int)$value;
                    break;
                case 'f':
                    $result .= (float)$value;
                    break;
                case 'a':
                    $result .= $this->formatArray($value);
                    break;
                case '#':  // согласно условию, токен применим только и идентификаторам, но не значениям
                    $result .= $this->formatIdentifier($value);
                    break;
                default:
                    $result .= $this->formatScalar($value);
                    --$pos; //корректировка сдвига
                    break;
            }
        }

        return $result;
    }

    /**
     * @param string $query
     * @param array $args
     * @return string
     * @throws Exception
     */
    public function buildQuery(string $query, array $args = []): string
    {
        $result = '';
        $queryParts = $this->tokenizeQueryConditions($query);
        foreach ($queryParts as $queryPart) {
            $result .= $this->buildSubQuery($queryPart['value'], $args, $queryPart['condition']);
        }
        return $result;
    }


    /**
     * Форматирует и, при необходимости, экранирует скалярное (+null) значение в соответствии с заданными правилами
     * @param string|int|float|bool|null $scalar Форматируемое значение
     * @param bool $isIdentifier Значение используется в идентификаторе (влияет на экранирование)
     * @return string
     * @example
     *      formatValue("abc") => 'abc'
     *      formatValue("abc", true) => `abc`
     *      formatValue(1) => 1
     *      formatValue(3.14) => 3.14
     *      formatValue(true) => 1
     *      formatValue(false) => 0
     *      formatValue(null) => NULL
     */
    private function formatScalar(string|int|float|bool|null $scalar, bool $isIdentifier = false): string
    {
        if (null === $scalar) {
            return 'NULL';
        }

        if (is_bool($scalar)) {
            return $scalar ? '1' : '0';
        }

        if (!is_numeric($scalar)) {
            return $isIdentifier ? $this->quoteIdentifier($scalar) : $this->quoteValue($scalar);
        }

        return (string)$scalar;
    }

    /**
     * Форматирует данные массива в соответствии с заданными правилами
     * Для ассоциативных массивов ключи всегда экранируются
     * @param array $array Форматируемый массив
     * @param bool $isIdentifier Данные используются в идентификаторе (влияет на экранирование)
     * @return string
     */
    private function formatArray(array $array, bool $isIdentifier = false): string
    {
        if ($this->isAssoc($array)) {
            $resultPairs = [];
            foreach ($array as $key => $value) {
                if (is_array($value)) { // IN
                    $resultPairs[] = "{$this->quoteIdentifier($key)} IN ({$this->formatArrayValue($value)})";
                } else { // =
                    $resultPairs[] = "{$this->quoteIdentifier($key)} = {$this->formatScalar($value)}";
                }
            }
            return $isIdentifier ? implode(', ', array_map([$this, 'quoteIdentifier'], $array)) : implode(', ', $resultPairs);
        }
        return $this->formatArrayValue($array, $isIdentifier);
    }

    /**
     * Форматирует значения (но не ключи) массива в соответствии с заданными правилами
     * @param array $value Форматируемый массив
     * @param bool $isIdentifier Значения используются в идентификаторе (влияет на экранирование)
     * @return string
     */
    private function formatArrayValue(array $value, bool $isIdentifier = false): string
    {
        return implode(', ', array_map(fn($value) => $this->formatScalar($value, $isIdentifier), $value));
    }

    /**
     * Форматирует данные в идентификаторе
     * @param array|string|int|float|bool|null $identifier
     * @return string
     */
    private function formatIdentifier(mixed $identifier): string
    {
        if (is_array($identifier)) {
            return $this->formatArray($identifier, true);
        }
        return $this->formatScalar($identifier, true);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function skip(): mixed
    {
        return $this->skipMarker;
    }

    /**
     * @param array $array
     * @return bool
     */
    private function isAssoc(array $array): bool
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }

    /**
     * @param string $fieldName
     * @return string
     */
    private function quoteIdentifier(string $fieldName): string
    {
        return "{$this->identifierQuoteCharacter}{$fieldName}{$this->identifierQuoteCharacter}";
    }

    /**
     * @param string $value
     * @return string
     */
    private function quoteValue(string $value): string
    {
        return "{$this->valueQuoteCharacter}{$value}{$this->valueQuoteCharacter}";
    }

}
