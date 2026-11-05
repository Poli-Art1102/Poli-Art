<?php
declare(strict_types=1);

/** Loads a module from a validated absolute entry point. */
final class ModuleLoader
{
    public function __construct(private readonly string $applicationRoot)
    {
    }

    public function load(string $entryPoint): void
    {
        $entryPoint = $this->resolveEntryPoint($entryPoint);
        require $entryPoint;
    }

    private function resolveEntryPoint(string $entryPoint): string
    {
        $root = realpath($this->applicationRoot);
        $resolvedEntryPoint = realpath($entryPoint);

        if ($root === false || $resolvedEntryPoint === false) {
            throw new RuntimeException('No se pudo resolver el módulo solicitado.');
        }

        $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($resolvedEntryPoint, $rootPrefix) || !is_file($resolvedEntryPoint)) {
            throw new RuntimeException('El punto de entrada del módulo no es válido.');
        }

        return $resolvedEntryPoint;
    }
}
