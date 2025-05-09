<?php

namespace Luminix\Bi\Filters;

class GenericFilter implements Filter
{
    public string $key;
    public string $name;
    public ?string $component = null;
    public ?string $column = null;
    public ?string $relation = null;
    public ?array $options = null;

    public function __construct(string $key, string $name)
    {
        $this->key = $key;
        $this->name = $name;
    }

    public static function create(string $key, string $name): self
    {
        return new self($key, $name);
    }

    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'component' => $this->component,
            'column' => $this->column,
            'relation' => $this->relation,
            'options' => $this->options,
            'type' => class_basename($this)
        ];
    }
}
