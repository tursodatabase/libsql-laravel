<?php

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

function decodeBlobs(array $row): array
{
    return array_map(function ($value) {
        return is_resource($value) ? stream_get_contents($value) : $value;
    }, $row);
}

function decodeDoubleBase64(array $result): array
{
    if (isset($result) && is_array($result)) {
        foreach ($result as &$row) {
            foreach ($row as $key => &$value) {
                if (is_string($value) && isValidDateOrTimestamp($value)) {
                    continue;
                }

                if (is_string($value) && $decoded = json_decode($value, true)) {
                    $value = $decoded;
                }

                if (is_string($value) && isValidBlob($value)) {
                    $value = base64_decode(base64_decode($value));
                }
            }
        }
    }

    return $result;
}

function isValidBlob(mixed $value): bool
{
    return (bool) preg_match('/^(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/', $value);
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
