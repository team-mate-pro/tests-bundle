<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle;

class ComposerFileReader
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;
    private bool $loaded = false;
    private readonly string $path;

    public function __construct(string $projectDir)
    {
        $this->path = $projectDir . '/composer.json';
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }

    public function isValid(): bool
    {
        return $this->load() !== null;
    }

    public function hasScript(string $scriptName): bool
    {
        $data = $this->load();

        if ($data === null) {
            return false;
        }

        $scripts = $data['scripts'] ?? [];

        if (!is_array($scripts)) {
            return false;
        }

        return array_key_exists($scriptName, $scripts);
    }

    /** @return array<string, mixed>|null */
    private function load(): ?array
    {
        if ($this->loaded) {
            return $this->data;
        }

        $this->loaded = true;

        if (!file_exists($this->path)) {
            return null;
        }

        $content = file_get_contents($this->path);

        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $this->data = $data;

        return $this->data;
    }
}
