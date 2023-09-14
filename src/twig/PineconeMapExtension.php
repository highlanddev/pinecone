<?php

namespace pinecone\twig;

use pinecone\Plugin;

use Craft;
use yii\base\Component;

use ether\simplemap\services\GeoService;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

use Twig\Extension\GlobalsInterface;

/**
 * Address From Lat Lng service
 */
class PineconeMapExtension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'Pinecone Map Twig Extension';
    }
    // The names of these 'get' functions are important.


    public function getFunctions(): array
    {
        return [

            new TwigFunction('addressFromLatLng', [$this, 'addressFromLatLng']),
            new TwigFunction('normalizeAddressString', [$this, 'normalizeAddressString']),
        ];
    }

    public function addressFromLatLng(float $lat, float $lng): ?array
    {
        return [
            'address' => GeoService::addressFromLatLng($lat, $lng),
            'didrun'=>'yes'
            ];
    }

    public function normalizeAddressString(mixed $location, string $country = null): array
    {
        try {
            $locationLatLng = GeoService::normalizeLocation($location);
            return [
            'address' => GeoService::addressFromLatLng($locationLatLng['lat'], $locationLatLng['lng'])
            ];
        } catch (Exception $e) {
            Craft::error($e->getMessage(), 'pinecone');

            return [
                'address' => ''
            ];
        }
    }
}
