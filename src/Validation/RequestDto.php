<?php

namespace Shift\Validation;

use Shift\Request;

abstract class RequestDto
{
    public static function fromRequest(Request $request): static
    {
        return static::fromArray($request->getJson());
    }

    public static function fromArray(array $data): static
    {
        $validated = (new Validator())->validate($data, static::rules());

        return new static(...$validated);
    }

    public static function rules(): array
    {
        return [];
    }
}
