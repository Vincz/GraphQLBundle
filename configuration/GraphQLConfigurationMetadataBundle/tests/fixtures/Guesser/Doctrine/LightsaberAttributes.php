<?php

declare(strict_types=1);

namespace Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Tests\fixtures\Guesser\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Overblog\GraphQL\Bundle\ConfigurationMetadataBundle\Metadata as GQL;

#[ORM\Entity]
#[GQL\Type]
class LightsaberAttributes
{
    #[ORM\Column]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $color;

    #[ORM\Column(type: 'text')]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $text;

    #[ORM\Column(type: 'string')]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $string;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $size;

    #[ORM\OneToMany(targetEntity: 'Hero')]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $holders;

    #[ORM\ManyToOne(targetEntity: 'Hero')]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $creator;

    #[ORM\OneToOne(targetEntity: 'Crystal')]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $crystal;

    #[ORM\ManyToMany(targetEntity: 'Battle')]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $battles;

    #[ORM\OneToOne(targetEntity: 'Hero')]
    #[ORM\JoinColumn(nullable: false)]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $currentHolder;

    #[ORM\Column(type: 'text[]')]
    #[GQL\Field]
    #[GQL\Deprecated('No more tags on lightsabers')]
    protected array $tags;

    #[ORM\Column(type: 'float')]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $float;

    #[ORM\Column(type: 'decimal')]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $decimal;

    #[ORM\Column(type: 'bool')]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $bool;

    #[ORM\Column(type: 'boolean')]
    #[GQL\Field]
    // @phpstan-ignore-next-line
    protected $boolean;
}
