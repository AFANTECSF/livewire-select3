<?php

namespace JekHar\LivewireSelect2;

class Select2Manager
{
    protected array $globalConfig = [];
    protected array $customFilters = [];

    /**
     * Set global configuration for all select2 instances
     */
    public function configureDefaults(array $config): self
    {
        $this->globalConfig = array_merge($this->globalConfig, $config);
        return $this;
    }

    /**
     * Register a custom filter callback
     */
    public function registerFilter(string $name, callable $callback): self
    {
        $this->customFilters[$name] = $callback;
        return $this;
    }

    /**
     * Get global configuration
     */
    public function getConfig(): array
    {
        return $this->globalConfig;
    }

    /**
     * Get registered filter by name
     */
    public function getFilter(string $name): ?callable
    {
        return $this->customFilters[$name] ?? null;
    }
}