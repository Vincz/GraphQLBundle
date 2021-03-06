<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\Extension;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\Mapping\Annotation as MappingAnnotation;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\ClassesTypesMap;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\TypeGuesser\TypeGuessingException;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;
use RuntimeException;

class DoctrineTypeGuesserExtension extends TypeGuesserExtension
{
    protected ?AnnotationReader $annotationReader = null;

    /**
     * @var array<string, string|string[]>
     */
    protected array $doctrineMapping = [];

    public function __construct(ClassesTypesMap $classesTypesMap, array $doctrineMapping = [])
    {
        parent::__construct($classesTypesMap);
        $this->doctrineMapping = $doctrineMapping;
    }

    public function getName(): string
    {
        return 'Doctrine annotations ';
    }

    public function supports(Reflector $reflector): bool
    {
        return $reflector instanceof ReflectionProperty;
    }

    /**
     * @param ReflectionProperty $reflector
     */
    public function guessType(ReflectionClass $reflectionClass, Reflector $reflector, array $filterGraphQLTypes = []): ?string
    {
        if (!$reflector instanceof ReflectionProperty) {
            throw new TypeGuessingException('Doctrine type guesser only apply to properties.');
        }
        /** @var Column|null $columnAnnotation */
        $columnAnnotation = $this->getMetadata($reflector, Column::class);

        if (null !== $columnAnnotation) {
            $nullable = $columnAnnotation->nullable;
            $doctrineType = $columnAnnotation->type ?? 'string';
            $type = $this->resolveTypeFromDoctrineMapping($doctrineType, true === $nullable);
            if ($type) {
                return $type;
            }

            $type = $this->resolveTypeFromDoctrineType($doctrineType);
            if ($type) {
                return $nullable ? $type : sprintf('%s!', $type);
            } else {
                throw new TypeGuessingException(sprintf('Unable to auto-guess GraphQL type from Doctrine type "%s"', $columnAnnotation->type));
            }
        }

        $associationAnnotations = [
            OneToMany::class => true,
            OneToOne::class => false,
            ManyToMany::class => true,
            ManyToOne::class => false,
        ];

        foreach ($associationAnnotations as $associationClass => $isMultiple) {
            /** @var OneToMany|OneToOne|ManyToMany|ManyToOne|null $associationAnnotation */
            $associationAnnotation = $this->getMetadata($reflector, $associationClass);
            if (null !== $associationAnnotation) {
                $target = $this->fullyQualifiedClassName($associationAnnotation->targetEntity, $reflectionClass->getNamespaceName());
                $type = $this->classesTypesMap->resolveType($target, [TypeConfiguration::TYPE_OBJECT]);

                if ($type) {
                    $isMultiple = $associationAnnotations[get_class($associationAnnotation)];
                    if ($isMultiple) {
                        return sprintf('[%s!]', $type);
                    } else {
                        $isNullable = true;
                        /** @var JoinColumn|null $joinColumn */
                        $joinColumn = $this->getMetadata($reflector, JoinColumn::class);
                        if (null !== $joinColumn) {
                            $isNullable = $joinColumn->nullable;
                        }

                        return sprintf('%s%s', $type, $isNullable ? '' : '!');
                    }
                } else {
                    throw new TypeGuessingException(sprintf('Unable to auto-guess GraphQL type from Doctrine target class "%s" (check if the target class is a GraphQL type itself (with a @Metadata\Type metadata).', $target));
                }
            }
        }
        throw new TypeGuessingException(sprintf('No Doctrine ORM annotation found.'));
    }

    private function getMetadata(Reflector $reflector, string $annotationClass): ?MappingAnnotation
    {
        return $this->getAttribute($reflector, $annotationClass) ?: $this->getAnnotation($reflector, $annotationClass);
    }

    private function getAttribute(Reflector $reflector, string $annotationClass): ?MappingAnnotation
    {
        if (PHP_VERSION_ID >= 80000) {
            $attributes = $reflector->getAttributes($annotationClass);
        } else {
            $attributes = [];
        }

        $attribute = current($attributes) ?: null;
        if (null !== $attribute) {
            return $attribute->newInstance();
        }

        return null;
    }

    private function getAnnotation(Reflector $reflector, string $annotationClass): ?MappingAnnotation
    {
        $reader = $this->getAnnotationReader();
        $annotations = [];
        switch (true) {
            case $reflector instanceof ReflectionClass: $annotations = $reader->getClassAnnotations($reflector); break;
            case $reflector instanceof ReflectionMethod: $annotations = $reader->getMethodAnnotations($reflector); break;
            case $reflector instanceof ReflectionProperty: $annotations = $reader->getPropertyAnnotations($reflector); break;
        }
        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationClass) {
                /** @var MappingAnnotation $annotation */
                return $annotation;
            }
        }

        return null;
    }

    private function getAnnotationReader(): AnnotationReader
    {
        if (null === $this->annotationReader) {
            if (!class_exists(AnnotationReader::class) ||
                !class_exists(AnnotationRegistry::class)) {
                throw new RuntimeException('In order to use graphql annotation/attributes, you need to require doctrine annotations');
            }

            AnnotationRegistry::registerLoader('class_exists');
            $this->annotationReader = new AnnotationReader();
        }

        return $this->annotationReader;
    }

    /**
     * Resolve a FQN from classname and namespace.
     *
     * @internal
     */
    public function fullyQualifiedClassName(string $className, string $namespace): string
    {
        if (false === strpos($className, '\\') && $namespace) {
            return $namespace.'\\'.$className;
        }

        return $className;
    }

    /**
     * Resolve a GraphQLType from a user doctrine mapping type.
     */
    private function resolveTypeFromDoctrineMapping(string $doctrineType, bool $nullable): ?string
    {
        $typeMapping = $this->doctrineMapping[$doctrineType] ?? null;
        if (null === $typeMapping) {
            return null;
        }

        if (is_array($typeMapping)) {
            return $typeMapping[$nullable ? 0 : 1];
        } else {
            return sprintf('%s%s', $typeMapping, $nullable ? '' : '!');
        }
    }

    /**
     * Resolve a GraphQLType from a doctrine type.
     */
    private function resolveTypeFromDoctrineType(string $doctrineType): ?string
    {
        switch ($doctrineType) {
            case 'integer':
            case 'smallint':
            case 'bigint':
                return 'Int';
            case 'string':
            case 'text':
                return 'String';
            case 'bool':
            case 'boolean':
                return 'Boolean';
            case 'float':
            case 'decimal':
                return 'Float';
            default:
                return null;
        }
    }
}
