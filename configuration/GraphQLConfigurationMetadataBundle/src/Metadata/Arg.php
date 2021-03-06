<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata;

use Attribute;

/**
 * Annotation for GraphQL argument.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"ANNOTATION","PROPERTY","METHOD"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Arg extends Metadata
{
    /**
     * Argument name.
     */
    public string $name;

    /**
     * Argument description.
     */
    public ?string $description;

    /**
     * Argument type.
     */
    public string $type;

    /**
     * Default argument value.
     *
     * @var mixed
     */
    public $default;

    public bool $isDefaultSet = false;

    /**
     * @param string      $name        The name of the argument
     * @param string      $type        The type of the argument
     * @param string|null $description The description of the argument
     * @param mixed|null  $default     Default value of the argument
     */
    public function __construct(string $name, string $type, ?string $description = null, $default = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        if (func_num_args() > 3) {
            $this->default = $default;
            $this->isDefaultSet = true;
        }
    }
}
