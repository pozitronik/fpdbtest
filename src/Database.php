<?php
declare(strict_types=1);

namespace pozitronik\FpDbTest;

use Exception;
use mysqli;

/**
 * Class Database
 * @public string $valueQuoteCharacter Строка, используемая для экранирования строк в значениях
 * @public string $fieldQuoteCharacter Строка, используемая для экранирования строк в ключах
 * @public  bool $allowMarkerEscape true: разрешает экранирование символа '?' как '??'. false: подстановка производится всегда
 */
class Database implements DatabaseInterface
{

    public string $valueQuoteCharacter = "'";
    public string $fieldQuoteCharacter = "`";
    public bool $allowMarkerEscape = true;
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
                    $result .= $this->formatIdentifier($value);
                    break;
                case '#':  // согласно условию, токен применим только и идентификаторам, но не значениям
                    $result .= $this->formatIdentifier($value);
                    break;
                default:
                    $result .= $this->formatValue($value);
                    --$pos; //корректировка сдвига
                    break;
            }
        }

        return $result;
    }

    /**
     * @param string|int|float|bool|null $value
     * @return string
     */
    private function formatValue(string|int|float|bool|null $value): string
    {
        if (null === $value) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (!is_numeric($value)) {
            return "{$this->valueQuoteCharacter}{$value}{$this->valueQuoteCharacter}";
        }

        return (string)$value;
    }

    /**
     * @param array $value
     * @return string
     */
    private function formatArrayValue(array $value): string
    {
        return implode(', ', array_map([$this, 'formatValue'], $value));
    }

    /**
     * @param string $fieldName
     * @return string
     */
    private function quoteFieldName(string $fieldName): string
    {
        return "{$this->fieldQuoteCharacter}{$fieldName}{$this->fieldQuoteCharacter}";
    }

    /**
     * @param array|string|int|float|bool|null $identifier
     * @return string
     */
    private function formatIdentifier(mixed $identifier): string
    {
        if (is_array($identifier)) {
            $resultPairs = [];
            if ($this->isAssoc($identifier)) {
                foreach ($identifier as $key => $value) {
                    if (is_array($value)) { // IN
                        $resultPairs[] = "{$this->quoteFieldName($key)} IN ({$this->formatArrayValue($value)})";
                    } else { // =
                        $resultPairs[] = "{$this->quoteFieldName($key)} = {$this->formatValue($value)}";
                    }
                }
                return implode(', ', $resultPairs);
            }
            return  implode(', ', array_map([$this, 'quoteFieldName'], $identifier));
        }
        return $this->formatValue($identifier);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function skip()
    {
//        throw new Exception();
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


}
