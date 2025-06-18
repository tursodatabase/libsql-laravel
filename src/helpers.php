<?php

use Libsql\Blob;

function arrayToStdClass(array $array): array
{
    $result = [];

    foreach ($array as $item) {
        $formattedItem = [];

        foreach ($item as $key => $value) {
            if (is_array($value) && !is_vector($value)) {
                // Encode only the nested array as a JSON string
                $formattedItem[$key] = json_encode($value);
            } else {
                $formattedItem[$key] = $value;
            }
        }

        // Convert the formatted item to a stdClass
        $result[] = (object) $formattedItem;
    }

    return $result;
}

function stdClassToArray(\stdClass|array $object): array
{
    if (is_array($object)) {
        return array_map('stdClassToArray', $object);
    }

    if (!$object instanceof \stdClass) {
        return $object;
    }

    $array = [];

    foreach (get_object_vars($object) as $key => $value) {
        $array[$key] = (is_array($value) || $value instanceof \stdClass)
            ? stdClassToArray($value)
            : $value;
    }

    return $array;
}

function reorderArrayKeys(array $data, array $keyOrder): array
{
    return array_map(function ($item) use ($keyOrder) {
        $ordered = array_fill_keys($keyOrder, null);

        return array_merge($ordered, $item);
    }, $data);
}

function is_vector($value): bool
{
    if (!is_array($value)) {
        return false;
    }

    foreach ($value as $element) {
        if (!is_numeric($element)) {
            return false;
        }
    }

    return array_keys($value) === range(0, count($value) - 1);
}

function decode(array $result): array
{
    return array_map(function ($row) {
        return array_map(function ($value) {
            if ($value instanceof Blob) {
                return $value->blob;
            }

            if (!is_string($value)) {
                return $value;
            }

            if (isValidDateOrTimestamp($value)) {
                return $value;
            }

            if ($decoded = json_decode($value, true)) {
                return $decoded;
            }

            return $value;
        }, $row);
    }, $result);
}

function isValidDateOrTimestamp($string, $format = null): bool
{
    if (is_numeric($string) && (int) $string > 0 && (int) $string <= PHP_INT_MAX) {
        return true;
    }

    if (is_numeric($string) && strlen($string) === 4 && (int) $string >= 1000 && (int) $string <= 9999) {
        return true;
    }

    $formats = $format ? [$format] : ['Y-m-d H:i:s', 'Y-m-d'];

    foreach ($formats as $fmt) {
        $dateTime = \DateTime::createFromFormat($fmt, $string);
        if ($dateTime && $dateTime->format($fmt) === $string) {
            return true;
        }
    }

    return false;
}
