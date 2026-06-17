<?php

namespace Shift\Validation;

class Validator
{
    public function validate(array $data, array $rules): array
    {
        $validated = [];
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = $this->normalizeRules($fieldRules);
            $exists = array_key_exists($field, $data);
            $value = $data[$field] ?? null;

            if (!$exists && !in_array('required', $fieldRules, true)) {
                continue;
            }

            foreach ($fieldRules as $rule) {
                $error = $this->validateRule((string) $field, $value, $exists, $rule);

                if ($error !== null) {
                    $errors[$field][] = $error;
                }
            }

            if (!isset($errors[$field]) && $exists) {
                $validated[$field] = $this->castValue($value, $fieldRules);
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    private function normalizeRules(string|array $rules): array
    {
        if (is_string($rules)) {
            return array_values(array_filter(explode('|', $rules)));
        }

        return $rules;
    }

    private function validateRule(string $field, mixed $value, bool $exists, string $rule): ?string
    {
        [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

        if ($name === 'required') {
            return $exists && $value !== null && $value !== '' ? null : 'The field is required.';
        }

        if (!$exists || $value === null || $value === '') {
            return null;
        }

        return match ($name) {
            'string' => is_string($value) ? null : 'The field must be a string.',
            'int' => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : 'The field must be an integer.',
            'bool' => $this->isBooleanLike($value) ? null : 'The field must be a boolean.',
            'array' => is_array($value) ? null : 'The field must be an array.',
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? null : 'The field must be a valid email address.',
            'min' => $this->passesMin($value, (float) $parameter) ? null : "The field must be at least {$parameter}.",
            'max' => $this->passesMax($value, (float) $parameter) ? null : "The field must be at most {$parameter}.",
            default => "Unknown validation rule '{$name}' for '{$field}'.",
        };
    }

    private function castValue(mixed $value, array $rules): mixed
    {
        if (in_array('int', $rules, true)) {
            return (int) $value;
        }

        if (in_array('bool', $rules, true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (in_array('string', $rules, true)) {
            return (string) $value;
        }

        return $value;
    }

    private function isBooleanLike(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        if (is_int($value)) {
            return in_array($value, [0, 1], true);
        }

        return is_string($value) && in_array(strtolower($value), ['true', 'false', '1', '0'], true);
    }

    private function passesMin(mixed $value, float $minimum): bool
    {
        if (is_array($value)) {
            return count($value) >= $minimum;
        }

        if (is_numeric($value)) {
            return (float) $value >= $minimum;
        }

        return is_string($value) && strlen($value) >= $minimum;
    }

    private function passesMax(mixed $value, float $maximum): bool
    {
        if (is_array($value)) {
            return count($value) <= $maximum;
        }

        if (is_numeric($value)) {
            return (float) $value <= $maximum;
        }

        return is_string($value) && strlen($value) <= $maximum;
    }
}
