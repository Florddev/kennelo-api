<?php

declare(strict_types=1);

namespace App\Support\OpenApi;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\Combined\AllOf;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Illuminate\Support\Str;

final class LoadedRelationSchemas implements DocumentTransformer
{
    private OpenApi $document;

    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $this->document = $document;

        foreach ($document->components->schemas as $schema) {
            $this->walk($schema);
        }

        foreach ($document->paths as $path) {
            $this->walk($path);
        }
    }

    private function walk(object $node): void
    {
        foreach (get_object_vars($node) as $property => $value) {
            $node->{$property} = $this->visit($value);
        }
    }

    private function visit(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map($this->visit(...), $value);
        }

        if ($value instanceof AllOf && ($variant = $this->variant($value)) !== null) {
            return $variant;
        }

        if (is_object($value) && ! $value instanceof Reference && Str::startsWith($value::class, 'Dedoc\\Scramble\\Support\\Generator\\')) {
            $this->walk($value);
        }

        return $value;
    }

    private function variant(AllOf $allOf): ?Reference
    {
        [$reference, $constraint] = [...$allOf->items, null, null];

        if (
            count($allOf->items) !== 2
            || ! $reference instanceof Reference
            || ! $constraint instanceof ObjectType
            || $constraint->properties !== []
            || $constraint->required === []
        ) {
            return null;
        }

        $base = $reference->resolve();

        if (! $base instanceof Schema || ! $base->type instanceof ObjectType) {
            return null;
        }

        $name = $reference->getUniqueName().'With'.collect($constraint->required)->map(fn (string $property): string => Str::studly($property))->implode('');
        $components = $this->document->components;

        if (! $components->hasSchema($name)) {
            $type = $base->type->clone();
            $required = [...$type->required, ...$constraint->required];
            $type->setRequired(array_values(array_filter(
                array_keys($type->properties),
                fn (string $property): bool => in_array($property, $required, true),
            )));

            $components->addSchema($name, Schema::fromType($type));
            $this->walk($type);
        }

        $variant = $components->getSchemaReference($name);
        $variant->nullable($allOf->nullable);

        if ($allOf->description !== '') {
            $variant->setDescription($allOf->description);
        }

        return $variant;
    }
}
