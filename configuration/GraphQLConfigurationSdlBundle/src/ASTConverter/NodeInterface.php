<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationSdlBundle\ASTConverter;

use GraphQL\Language\AST\Node;
use Overblog\GraphQLBundle\Configuration\TypeConfiguration;

interface NodeInterface
{
    public static function toConfiguration(string $name, Node $node): TypeConfiguration;
}
