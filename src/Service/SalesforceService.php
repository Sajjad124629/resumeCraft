<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SalesforceService
{
    private HttpClientInterface $httpClient;
    private string $clientId;
    private string $clientSecret;
    private string $instanceUrl;

    public function __construct(
        ?HttpClientInterface $httpClient = null,
        ?string $clientId = null,
        ?string $clientSecret = null,
        ?string $instanceUrl = null,
        private ?LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
            'timeout' => 30,
        ]);
        $this->clientId = $clientId ?? ($_ENV['SALESFORCE_CONSUMER_KEY'] ?? '');
        $this->clientSecret = $clientSecret ?? ($_ENV['SALESFORCE_CONSUMER_SECRET'] ?? '');
        $this->instanceUrl = rtrim($instanceUrl ?? ($_ENV['SALESFORCE_INSTANCE_URL'] ?? 'https://login.salesforce.com'), '/');
    }

    /**
     * Obtains an OAuth2 access token from Salesforce using Client Credentials Flow.
     *
     * @return array{access_token: string, instance_url: string}
     * @throws \Exception
     */
    public function getAccessToken(): array
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new \Exception('Salesforce Consumer Key and Consumer Secret must be configured in .env or .env.local.');
        }

        $tokenUrl = $this->instanceUrl . '/services/oauth2/token';

        $response = $this->httpClient->request('POST', $tokenUrl, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
            'body' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->toArray(false);

        if ($statusCode !== 200 || !isset($content['access_token'])) {
            $errorDesc = $content['error_description'] ?? $content['error'] ?? 'Authentication failed with Salesforce.';
            $this->logger?->error('Salesforce token error: ' . json_encode($content));
            throw new \Exception('Salesforce OAuth Error: ' . $errorDesc);
        }

        return [
            'access_token' => $content['access_token'],
            'instance_url' => rtrim($content['instance_url'] ?? $this->instanceUrl, '/'),
        ];
    }

    /**
     * Creates an Account object in Salesforce.
     *
     * @param string $accessToken
     * @param string $instanceUrl
     * @param array $accountData
     * @return string Created Account ID
     * @throws \Exception
     */
    public function createAccount(string $accessToken, string $instanceUrl, array $accountData): string
    {
        $url = $instanceUrl . '/services/data/v60.0/sobjects/Account';

        $payload = [
            'Name' => $accountData['name'],
        ];

        if (!empty($accountData['phone'])) {
            $payload['Phone'] = $accountData['phone'];
        }
        if (!empty($accountData['website'])) {
            $payload['Website'] = $accountData['website'];
        }
        if (!empty($accountData['city'])) {
            $payload['BillingCity'] = $accountData['city'];
        }
        if (!empty($accountData['industry'])) {
            $payload['Industry'] = $accountData['industry'];
        }
        if (!empty($accountData['description'])) {
            $payload['Description'] = $accountData['description'];
        }

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $statusCode = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($statusCode !== 201 || empty($data['id'])) {
            $errors = isset($data[0]['message']) ? $data[0]['message'] : json_encode($data);
            throw new \Exception('Failed to create Salesforce Account: ' . $errors);
        }

        return $data['id'];
    }

    /**
     * Creates a Contact object in Salesforce linked to an Account.
     *
     * @param string $accessToken
     * @param string $instanceUrl
     * @param string $accountId
     * @param array $contactData
     * @return string Created Contact ID
     * @throws \Exception
     */
    public function createContact(string $accessToken, string $instanceUrl, string $accountId, array $contactData): string
    {
        $url = $instanceUrl . '/services/data/v60.0/sobjects/Contact';

        $lastName = !empty($contactData['lastName']) ? $contactData['lastName'] : ($contactData['firstName'] ?: 'Contact');

        $payload = [
            'AccountId' => $accountId,
            'FirstName' => $contactData['firstName'] ?? '',
            'LastName' => $lastName,
            'Email' => $contactData['email'] ?? '',
        ];

        if (!empty($contactData['phone'])) {
            $payload['Phone'] = $contactData['phone'];
        }
        if (!empty($contactData['title'])) {
            $payload['Title'] = $contactData['title'];
        }
        if (!empty($contactData['city'])) {
            $payload['MailingCity'] = $contactData['city'];
        }
        if (!empty($contactData['description'])) {
            $payload['Description'] = $contactData['description'];
        }

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $statusCode = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($statusCode !== 201 || empty($data['id'])) {
            $errors = isset($data[0]['message']) ? $data[0]['message'] : json_encode($data);
            throw new \Exception('Failed to create Salesforce Contact: ' . $errors);
        }

        return $data['id'];
    }

    /**
     * High-level method to sync user to Salesforce by creating an Account and a linked Contact.
     *
     * @param User $user
     * @param array $formData
     * @return array{success: bool, accountId: string, contactId: string, instanceUrl: string}
     * @throws \Exception
     */
    public function syncUserToSalesforce(User $user, array $formData): array
    {
        $auth = $this->getAccessToken();
        $accessToken = $auth['access_token'];
        $instanceUrl = $auth['instance_url'];

        $userDetails = $user->getUserDetails();
        $firstName = $userDetails?->getFirstName() ?? ($formData['firstName'] ?? '');
        $lastName = $userDetails?->getLastName() ?? ($formData['lastName'] ?? '');
        $email = $user->getEmail();
        $phone = $formData['phone'] ?? ($userDetails?->getPhone() ?? '');

        // Account Name: user provided company name or fallback to "User's Account"
        $accountName = !empty($formData['accountName']) 
            ? trim($formData['accountName']) 
            : trim($firstName . ' ' . $lastName . ' Account');
        if (empty($accountName) || $accountName === 'Account') {
            $accountName = ($user->getUserIdentifier() ?: 'Candidate') . ' Account';
        }

        // 1. Create Account
        $accountId = $this->createAccount($accessToken, $instanceUrl, [
            'name' => $accountName,
            'phone' => $phone,
            'website' => $formData['website'] ?? null,
            'city' => $formData['city'] ?? ($user->getUserDetails()?->getLocation() ?? $user->getCandidateProfile()?->getLocation() ?? null),
            'industry' => $formData['industry'] ?? 'Technology',
            'description' => $formData['description'] ?? 'Created from ResumeCraft Web App',
        ]);

        // 2. Create Contact linked to Account
        $roleName = $user->getRole()?->getName() ?? 'User';
        $contactDesc = sprintf(
            "ResumeCraft User #%d | Role: %s\nNotes: %s",
            $user->getId(),
            $roleName,
            $formData['description'] ?? 'None'
        );

        $contactId = $this->createContact($accessToken, $instanceUrl, $accountId, [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'title' => $formData['title'] ?? $roleName,
            'city' => $formData['city'] ?? ($user->getUserDetails()?->getLocation() ?? $user->getCandidateProfile()?->getLocation() ?? null),
            'description' => $contactDesc,
        ]);

        return [
            'success' => true,
            'accountId' => $accountId,
            'contactId' => $contactId,
            'instanceUrl' => $instanceUrl,
        ];
    }
}
