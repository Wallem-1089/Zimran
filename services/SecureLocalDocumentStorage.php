<?php

declare(strict_types=1);

require_once __DIR__ . '/DocumentStorageInterface.php';

class SecureLocalDocumentStorage implements DocumentStorageInterface
{
    private string $root;

    public function __construct(string $storageRoot)
    {
        if (trim($storageRoot) === '' || str_contains($storageRoot, "\0")) {
            throw new InvalidArgumentException('A secure document storage root is required.');
        }

        $this->root = $this->normalizeRoot($storageRoot);
        $this->initializeStorage();
    }

    public function store(
        string $temporaryPath,
        string $storageKey,
        bool $quarantined
    ): array {
        if (!$this->validStorageKey($storageKey)
            || !is_file($temporaryPath)
            || !is_readable($temporaryPath)
        ) {
            return ['success' => false, 'errors' => ['Unable to store the uploaded file.']];
        }

        $destination = $this->path($storageKey, $quarantined);
        $directory = dirname($destination);
        if (!$this->ensureDirectory($directory) || file_exists($destination)) {
            return ['success' => false, 'errors' => ['Unable to allocate secure document storage.']];
        }

        $testing = strtolower((string)getenv('HMS_APP_ENV')) === 'testing';
        $moved = is_uploaded_file($temporaryPath)
            ? move_uploaded_file($temporaryPath, $destination)
            : ($testing && @rename($temporaryPath, $destination));
        if (!$moved && $testing) {
            $moved = @copy($temporaryPath, $destination);
            if ($moved) {
                @unlink($temporaryPath);
            }
        }
        if (!$moved) {
            return ['success' => false, 'errors' => ['Unable to move the uploaded file into secure storage.']];
        }

        @chmod($destination, 0600);
        clearstatcache(true, $destination);

        return [
            'success' => true,
            'data' => [
                'size' => (int)filesize($destination),
                'sha256' => (string)hash_file('sha256', $destination),
                'quarantined' => $quarantined
            ],
            'errors' => []
        ];
    }

    public function openStream(string $storageKey, bool $quarantined = false): mixed
    {
        if (!$this->exists($storageKey, $quarantined)) {
            return false;
        }
        return @fopen($this->path($storageKey, $quarantined), 'rb');
    }

    public function exists(string $storageKey, bool $quarantined = false): bool
    {
        return $this->validStorageKey($storageKey)
            && is_file($this->path($storageKey, $quarantined));
    }

    public function deleteTemporaryFile(string $temporaryPath): bool
    {
        if ($temporaryPath === '' || !file_exists($temporaryPath)) {
            return true;
        }
        return is_file($temporaryPath) && @unlink($temporaryPath);
    }

    public function quarantine(string $storageKey): bool
    {
        if (!$this->exists($storageKey, false)) {
            return false;
        }
        return $this->move($storageKey, false, true);
    }

    public function moveFromQuarantine(string $storageKey): bool
    {
        if (!$this->exists($storageKey, true)) {
            return false;
        }
        return $this->move($storageKey, true, false);
    }

    public function remove(string $storageKey, bool $quarantined = false): bool
    {
        if (!$this->validStorageKey($storageKey)) {
            return false;
        }
        $path = $this->path($storageKey, $quarantined);
        return !file_exists($path) || (is_file($path) && @unlink($path));
    }

    public function getMetadata(string $storageKey, bool $quarantined = false): array
    {
        if (!$this->exists($storageKey, $quarantined)) {
            return ['success' => false, 'data' => null, 'errors' => ['Stored document is unavailable.']];
        }
        $path = $this->path($storageKey, $quarantined);
        return [
            'success' => true,
            'data' => [
                'size' => (int)filesize($path),
                'sha256' => (string)hash_file('sha256', $path)
            ],
            'errors' => []
        ];
    }

    private function normalizeRoot(string $root): string
    {
        $root = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $root), DIRECTORY_SEPARATOR);
        if (!preg_match('/^(?:[A-Za-z]:\\\\|\/)/', $root)) {
            throw new InvalidArgumentException('Document storage root must be absolute.');
        }
        return $root;
    }

    private function initializeStorage(): void
    {
        foreach ([$this->root, $this->root . DIRECTORY_SEPARATOR . 'available', $this->root . DIRECTORY_SEPARATOR . 'quarantine'] as $directory) {
            if (!$this->ensureDirectory($directory)) {
                throw new RuntimeException('Secure document storage is unavailable.');
            }
        }

        $this->writeProtectionFile('.htaccess', "Require all denied\nOptions -Indexes -ExecCGI\nRemoveHandler .php .phtml .phar .cgi .pl .py .sh\n");
        $this->writeProtectionFile('web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><authorization><deny users=\"*\" /></authorization><directoryBrowse enabled=\"false\" /></system.webServer></configuration>");
    }

    private function writeProtectionFile(string $name, string $contents): void
    {
        $path = $this->root . DIRECTORY_SEPARATOR . $name;
        if (!file_exists($path)) {
            @file_put_contents($path, $contents, LOCK_EX);
        }
    }

    private function ensureDirectory(string $directory): bool
    {
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            return false;
        }
        @chmod($directory, 0700);
        return is_dir($directory) && is_writable($directory);
    }

    private function validStorageKey(string $storageKey): bool
    {
        return preg_match('/^[a-f0-9]{2}\/[a-f0-9]{64}\.(pdf|jpg|jpeg|png|txt)$/D', $storageKey) === 1;
    }

    private function path(string $storageKey, bool $quarantined): string
    {
        if (!$this->validStorageKey($storageKey)) {
            throw new InvalidArgumentException('Invalid document storage reference.');
        }
        $area = $quarantined ? 'quarantine' : 'available';
        $path = $this->root . DIRECTORY_SEPARATOR . $area . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $storageKey);
        $prefix = $this->root . DIRECTORY_SEPARATOR . $area . DIRECTORY_SEPARATOR;
        if (!str_starts_with($path, $prefix)) {
            throw new RuntimeException('Invalid document storage boundary.');
        }
        return $path;
    }

    private function move(string $storageKey, bool $fromQuarantine, bool $toQuarantine): bool
    {
        $source = $this->path($storageKey, $fromQuarantine);
        $destination = $this->path($storageKey, $toQuarantine);
        return $this->ensureDirectory(dirname($destination))
            && !file_exists($destination)
            && @rename($source, $destination);
    }
}
