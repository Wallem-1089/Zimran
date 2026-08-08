<?php

declare(strict_types=1);

interface DocumentStorageInterface
{
    public function store(
        string $temporaryPath,
        string $storageKey,
        bool $quarantined
    ): array;

    public function openStream(string $storageKey, bool $quarantined = false): mixed;

    public function exists(string $storageKey, bool $quarantined = false): bool;

    public function deleteTemporaryFile(string $temporaryPath): bool;

    public function quarantine(string $storageKey): bool;

    public function moveFromQuarantine(string $storageKey): bool;

    public function remove(string $storageKey, bool $quarantined = false): bool;

    public function getMetadata(string $storageKey, bool $quarantined = false): array;
}
