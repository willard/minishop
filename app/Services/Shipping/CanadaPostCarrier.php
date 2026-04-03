<?php

namespace App\Services\Shipping;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class CanadaPostCarrier implements ShippingCarrierContract
{
    private const CONTENT_TYPE = 'application/vnd.cpc.ship.rate-v4+xml';

    private const SANDBOX_URL = 'https://ct.soa-gw.canadapost.ca/rs/ship/price';

    private const PRODUCTION_URL = 'https://soa-gw.canadapost.ca/rs/ship/price';

    public function driverKey(): string
    {
        return 'canada_post';
    }

    /**
     * @return Collection<int, ShippingRateData>
     *
     * @throws RequestException
     */
    public function getRates(ShipmentData $shipment): Collection
    {
        $username = config('services.canada_post.username');
        $password = config('services.canada_post.password');
        $customerNumber = config('services.canada_post.customer_number');
        $isSandbox = config('services.canada_post.sandbox', true);

        $xml = $this->buildRequestXml($shipment, $customerNumber);

        $response = Http::withBasicAuth($username, $password)
            ->withHeaders(['Content-Type' => self::CONTENT_TYPE, 'Accept' => self::CONTENT_TYPE])
            ->timeout(5)
            ->withBody($xml, self::CONTENT_TYPE)
            ->post($isSandbox ? self::SANDBOX_URL : self::PRODUCTION_URL);

        $response->throw();

        return $this->parseResponse($response->body());
    }

    private function buildRequestXml(ShipmentData $shipment, ?string $customerNumber): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');

        $scenario = $doc->createElement('mailing-scenario');
        $scenario->setAttribute('xmlns', 'http://www.canadapost.ca/ws/ship/rate-v4');
        $doc->appendChild($scenario);

        if ($customerNumber) {
            $scenario->appendChild($doc->createElement('customer-number', htmlspecialchars($customerNumber)));
        }

        $parcel = $doc->createElement('parcel-characteristics');
        $parcel->appendChild($doc->createElement('weight', $shipment->weightKg()));
        $scenario->appendChild($parcel);

        $originNode = $doc->createElement('origin-postal-code', htmlspecialchars(
            strtoupper(preg_replace('/\s+/', '', $shipment->originPostcode))
        ));
        $scenario->appendChild($originNode);

        $destination = $doc->createElement('destination');
        $domestic = $doc->createElement('domestic');
        $domestic->appendChild($doc->createElement('postal-code', htmlspecialchars(
            strtoupper(preg_replace('/\s+/', '', $shipment->destinationPostcode))
        )));
        $destination->appendChild($domestic);
        $scenario->appendChild($destination);

        return $doc->saveXML();
    }

    /**
     * @return Collection<int, ShippingRateData>
     */
    private function parseResponse(string $body): Collection
    {
        $rates = collect();

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);

        if ($xml === false) {
            return $rates;
        }

        $xml->registerXPathNamespace('cp', 'http://www.canadapost.ca/ws/ship/rate-v4');

        foreach ($xml->xpath('//cp:price-quote') as $quote) {
            $serviceCode = (string) $quote->{'service-code'};
            $serviceName = (string) $quote->{'service-name'};
            $due = (float) $quote->{'price-details'}->{'due'};
            $expectedDelivery = isset($quote->{'service-standard'}->{'expected-delivery-date'})
                ? (string) $quote->{'service-standard'}->{'expected-delivery-date'}
                : null;

            if ($serviceCode && $serviceName && $due > 0) {
                $rates->push(new ShippingRateData(
                    carrier: $this->driverKey(),
                    serviceCode: $serviceCode,
                    serviceName: $serviceName,
                    amountCents: (int) round($due * 100),
                    expectedDelivery: $expectedDelivery ?: null,
                    shippingMethodId: null,
                ));
            }
        }

        return $rates;
    }
}
