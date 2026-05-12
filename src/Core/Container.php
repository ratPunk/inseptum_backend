<?php
declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Minimal DI container with shared (singleton) bindings.
 */
class Container
{
    /** @var array<string, Closure> */
    private array $bindings = [];
    /** @var array<string, mixed> */
    private array $instances = [];

    public function bind(string $id, Closure $factory): void
    {
        $this->bindings[$id] = $factory;
        unset($this->instances[$id]);
    }

    public function instance(string $id, $value): void
    {
        $this->instances[$id] = $value;
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    /**
     * Resolve a binding. Auto-wires concrete classes when no binding is registered.
     */
    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        if (isset($this->bindings[$id])) {
            return $this->instances[$id] = ($this->bindings[$id])($this);
        }
        if (class_exists($id)) {
            return $this->instances[$id] = $this->build($id);
        }
        throw new RuntimeException("Container: cannot resolve '$id'");
    }

    private function build(string $class)
    {
        $ref = new \ReflectionClass($class);
        $ctor = $ref->getConstructor();
        if ($ctor === null) {
            return new $class();
        }
        $args = [];
        foreach ($ctor->getParameters() as $p) {
            $type = $p->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->get($type->getName());
            } elseif ($p->isDefaultValueAvailable()) {
                $args[] = $p->getDefaultValue();
            } else {
                throw new RuntimeException("Container: cannot resolve param '{$p->getName()}' of $class");
            }
        }
        return $ref->newInstanceArgs($args);
    }
}
