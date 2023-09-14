<?php

namespace pinecone\twig;

use pinecone\Plugin;

use Craft;
use yii\base\Component;


use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;
use Twig\Extension\GlobalsInterface;

/**
 * Address From Lat Lng service
 */
class PineconeExtension extends AbstractExtension implements GlobalsInterface
{
    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'Pinecone Twig Extension';
    }
    // The names of these 'get' functions are important.
    public function getGlobals(): array
    {
        // Keys are variable names!
        return [
            'getTests' => 'barf',
        ];
    }

    public function getFunctions(): array
    {
        return [
            // new TwigFunction('is_array', [$this, 'isArray']),

        ];
    }

    public function getFilters()
    {
        return [
            // new TwigFilter('is_string', [$this, 'isString']),
        ];
    }

    // I'm dumb so leaving these in here for future
//     public function isArray($value): bool
//     {
//         return is_array($value);
//     }
//
//     public function isString($value): bool
//     {
//         return is_string($value);
//     }
}
