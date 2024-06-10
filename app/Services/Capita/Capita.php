<?php

namespace Ceremonies\Services\Capita;

use Ceremonies\Services\Helpers;
use Ceremonies\Services\Logger;

class Capita
{

    private bool $production = true;
    private $siteId = 330;
    private $portalId = 373210934;
    private $hmacKey = '+HBNr6PIY6rmwuiP4stVITj0OhwF62bSfJ1qkpkUZ+2m9AYrMvuXhq1jsqF+B52L/Z3OpqvRh3kloOM5cjEj3g==';
    private $hmacKeyId = 456;

    private \SoapClient $client;

    /**
     * Unique reference for the next request.
     *
     * @var null
     */
    private $reference = null;

    /**
     * URL's for this site used by Capita.
     */
    private $returnUrl = '/wp-json/sc/v1/payments/return';
    private $backUrl = '/wp-json/sc/v1/payments/back';
    private $errorUrl = '/wp-json/sc/v1/payments/error';

    /**
     * Set up the SOAP client ready to make requests.
     *
     * @throws \SoapFault
     */
    public function __construct()
    {
        $this->client = new \SoapClient($this->getWsdl(), [
            'location' => $this->getApiUrl(),
        ]);
    }

    private function getApiUrl()
    {
        return $this->production ? 'https://sbs.e-paycapita.com/scp/scpws/scpClient' : 'https://sbsctest.e-paycapita.com/scp/scpws';
    }

    private function getWsdl()
    {
        return $this->production ? 'https://sbs.e-paycapita.com:443/scp/scpws/scpSimpleClient.wsdl' : 'https://sbsctest.e-paycapita.com:443/scp/scpws/scpSimpleClient.wsdl';
    }

    /**
     * List the functions available on the soap client, can be used as a way to test
     * the connection.
     *
     * @return array
     */
    public function testConnection()
    {
        return $this->client->__getFunctions();
    }

    /**
     * Builds up the array of routing parameters.
     *
     * @return array
     */
    private function getRoutingParams()
    {
        return array(
            'returnUrl' => $this->getUrl('returnUrl'),
            'backUrl' => $this->getUrl('backUrl'),
            'errorUrl' => $this->getUrl('errorUrl'),
            'siteId' => $this->siteId,
            'scpId' => $this->portalId,
        );
    }

    /**
     * Invoke a payment request to Capita, returns a reference and a URL
     * for the user to be redirected to.
     *
     * @param $data
     * @return array|void
     */
    public function invokePayment($data)
    {
        try {

            $sale = [
                'saleSummary' => [
                    'description' => $this->formatLineItemName($data['payment_line_name']),
                    'amountInMinorUnits' => $this->formatPrice($data['payment_line_amount']),
                ],
            ];

            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    $paymentLine = [
                        'lineId' => $item['id'],
                        'itemSummary' => [
                            'description' => $item['description'],
                            'amountInMinorUnits' => $this->formatPrice($item['amount']),
                        ],
                        'quantity' => 1,
                    ];

                    // Only add fund code if it exists
                    if ($this->getFundCode($item['description'])) {
                        $paymentLine['lgItemDetails'] = [
                            'fundCode' => $this->getFundCode($item['description'])
                        ];
                    }

                    $sale['items'][] = $paymentLine;
                }
            }

            $response = $this->client->__soapCall('scpSimpleInvoke', [
                'parameters' => [
                    'credentials' => $this->getCredentials(),
                    'requestType' => 'payOnly',
                    'requestId' => rand(0, 1000),
                    'routing' => $this->getRoutingParams(),
                    'panEntryMethod' => 'ECOM',
                    'sale' => $sale,
                ]
            ]);
            return Helpers::objectToArray($response);
        } catch (\Exception $e) {
            Logger::log('Failed to invoke Capita payment:', [
                'errorMessage' => $e->getMessage(),
                'data' => [
                    'returnUrl' => $this->getUrl('returnUrl'),
                    'backUrl' => $this->getUrl('backUrl'),
                    'errorUrl' => $this->getUrl('errorUrl'),
                    'siteId' => $this->siteId,
                    'scpId' => $this->portalId,
                    'description' => $this->formatLineItemName($data['payment_line_name']),
                    'amountInMinorUnits' => $this->formatPrice($data['payment_line_amount']),
                ]
            ]);
        }

    }

    private function getFundCode($item)
    {

        $item = strtolower($item);

        /**
         * Codes from SCC:
         * SR534066016 -> All ceremony payments
         * SR534069151 -> Certificates
         * SR534069152 -> Notices of marriage
         *
         * NOTE: Capita limits these codes to 5 characters. Given that they all
         * share the same first 6 characters, we can just return the first 5 characters
         */
        if (str_contains($item, 'notice of marriage')) {
            return '69152';
        } else if (str_contains($item, 'certificate')) {
            return '69151';
        } else {
            return '66016';
        }

    }

    /**
     * Queries the state of a payment within Capita.
     *
     * @param string $reference scpReference from Capita.
     * @return array|void
     */
    public function queryPayment($reference)
    {
        try {
            $response = $this->client->__soapCall('scpSimpleQuery', [
                'parameters' => [
                    'credentials' => $this->getCredentials(),
                    'siteId' => $this->siteId,
                    'scpReference' => $reference,
                ]
            ]);
            return Helpers::objectToArray($response);
        } catch (\Exception $e) {
            Logger::log('Failed to query Capita payment:', [
                'errorMessage' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Builds the array of credentials needed for a Capita request.
     *
     * @return array[]
     */
    private function getCredentials()
    {
        return [
            'subject' => [
                'subjectType' => 'CapitaPortal',
                'identifier' => $this->portalId,
                'systemCode' => 'SCP'
            ],
            'requestIdentification' => [
                'uniqueReference' => $this->getReference(),
                'timeStamp' => $this->getTimestamp(),
            ],
            'signature' => [
                'algorithm' => 'Original',
                'hmacKeyID' => $this->hmacKeyId,
                'digest' => $this->getDigest()
            ]
        ];
    }

    /**
     * Builds up the digest string required to authenticate with Capita.
     *
     * @return string
     */
    private function getDigest()
    {

        // 1. Concatenate subjectType, identifier, uniqueReference, timestamp, algorithm and hmacKeyID into a single
        // string, with a '!' inserted between each concatenated value.
        // E.g. the result might be: CapitaPortal!37!X326736B!20110203201814!Original!2

        $credentials = array(
            'subjectType' => 'CapitaPortal',
            'identifier' => $this->portalId,
            'uniqueReference' => $this->getReference(),
            'timestamp' => $this->getTimestamp(),
            'algorithm' => 'Original',
            'hmacKeyID' => $this->hmacKeyId,
        );
        $credentialsToHash = implode("!", $credentials);

        $key = base64_decode($this->hmacKey);
        $hash = hash_hmac('sha256', $credentialsToHash, $key, true);
        return base64_encode($hash);
    }

    /**
     * Gets the current timestamp, in the format required by Capita.
     * @return string
     */
    private function getTimestamp()
    {
        return gmdate("YmdHis");
    }

    /**
     * Generates a unique reference to attach to the Capita
     * request.
     *
     * @return string|null
     */
    private function getReference()
    {
        if ($this->reference) {
            return $this->reference;
        }

        $this->reference = (string)rand(1, 10000);

        return $this->reference;

    }

    /**
     * Formats a price into an int for Capita.
     * Eg: £124.99 -> 12499
     *
     * @param $price
     * @return int
     */
    private function formatPrice($price)
    {

        if (is_string($price)) {
            $price = str_replace('£', '', $price);
            $price = str_replace(',', '', $price);
            $price = floatval($price);
        }

        $price = $price * 100;
        return intval($price);

    }

    /**
     * Prepends text to the line item name
     *
     * @param $name
     * @return string
     */
    private function formatLineItemName($name)
    {
        return 'Staffordshire Ceremonies - ' . $name;
    }

    /**
     * Formats the URL's passed to Capita to have the correct
     * domain name.
     *
     * @param string $type
     * @return string
     */
    private function getUrl(string $type): string
    {
        return home_url() . $this->$type;
    }

}

/**
 *
 * The Capita request process as outlined in their docs:
 *
 * 1. The client makes an “invoke” web service call, providing details of a payment and/or store card transaction which
 * the client requires the SCP to process.
 * 2. The SCP validates the request, starts a new transaction, and returns a response containing two items of data:
 * ▪ A unique identifier for the transaction.
 * ▪ A URL to which the user’s browser should now be redirected. (This URL points to a location within
 * the SCP web application).
 * 3. The client redirects the user’s browser.
 * 4. On receiving the redirect, the SCP:
 * ▪ Retrieves the transaction (which is identified by a key within the redirect URL).
 * ▪ Processes the transaction, displaying pages on the user’s browser and prompting for input as
 * necessary.
 * ▪ Stores the end result of the processing (success, card declined, etc).
 * ▪ Redirects the browser back to the client application. (The redirect URL is specified by the <returnUrl>
 * element in the original request).
 * 5. The client makes a “query” web service call to the SCP, passing in the request identifier that was
 * returned in step 2 above.
 * 6. The SCP retrieves the transaction status and returns it.
 */