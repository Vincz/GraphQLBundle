<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationYamlBundle\Definition;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class InterfaceTypeDefinition extends TypeWithOutputFieldsDefinition
{
    public function getDefinition(): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $node */
        $node = self::createNode('_interface_config');

        /** @phpstan-ignore-next-line */
        $node
            ->children()
                ->append($this->nameSection())
                ->append($this->outputFieldsSection())
                ->append($this->resolveTypeSection())
                ->append($this->descriptionSection())
                ->append($this->extensionsSection())
                ->arrayNode('interfaces')
                    ->prototype('scalar')->info('One of internal or custom interface types.')->end()
                ->end()
            ->end();

        return $node;
    }
}
