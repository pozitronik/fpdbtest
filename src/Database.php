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

    private mysqli|null $mysqli = null;


    /**
     * @param mysqli|null $mysqli
     */
    public function __construct(mysqli|null $mysqli = null)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * @param string $query
     * @param array $args
     * @return string
     */
    public function buildQuery(string $query, array $args = []): string
    {
        // Инициализация результата и текущей позиции в строке
        $result = '';
        $pos = 0;
        $length = strlen($query);

        // Обработка шаблона
        while ($pos < $length) {
            // Находим следующий вопросительный знак
            $nextPos = strpos($query, '?', $pos);
            if (false === $nextPos) {
                $result .= substr($query, $pos);
                break;
            }

            // Добавляем текст до вопросительного знака
            $result .= substr($query, $pos, $nextPos - $pos);
            $pos = $nextPos;

            if ($this->allowMarkerEscape && (isset($query[$pos + 1]) && '?' === $query[$pos + 1])) {// Проверяем, следует ли за вопросительным знаком другой вопросительный знак
                $result .= '?';
                $pos += 2; // Пропускаем оба знака вопроса
                continue;
            }

            $specifier = $query[$pos + 1] ?? '';
            $pos += 2;

            $value = array_shift($args); // Извлекаем первый элемент параметров
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
