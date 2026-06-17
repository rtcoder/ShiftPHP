<?php

namespace Shift\Console\Generator;

final class StubRenderer
{
    public function __construct(
        private readonly string $stubPath = __DIR__ . '/stubs'
    ) {
    }

    /**
     * @param array<string, string> $variables
     */
    public function render(string $stub, array $variables): string
    {
        $path = rtrim($this->stubPath, '/') . '/' . $stub . '.stub';

        if (!is_file($path)) {
            throw new \RuntimeException("Stub {$stub} not found.");
        }

        $content = file_get_contents($path);

        if (!is_string($content)) {
            throw new \RuntimeException("Stub {$stub} cannot be read.");
        }

        foreach ($variables as $name => $value) {
            $content = str_replace('{{ ' . $name . ' }}', $value, $content);
        }

        return $content;
    }
}
