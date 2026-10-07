<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:dropbox-token',
    description: 'Exchange Dropbox authorization code for permanent refresh_token and access_token, and update .env.local automatically'
)]
class DropboxTokenCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'code',
            InputArgument::OPTIONAL,
            'The authorization code received from Dropbox'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $code = $input->getArgument('code');

        $appKey = trim((string)($_ENV['DROPBOX_APP_KEY'] ?? '5ktai8sczd822zh'));
        $appSecret = trim((string)($_ENV['DROPBOX_APP_SECRET'] ?? 'jltdw9b6caogj7t'));

        if (empty($code)) {
            $io->title('Dropbox OAuth 2 Token Generator');
            $io->text('Step 1: Open this authorization link in your browser:');
            $authUrl = sprintf(
                'https://www.dropbox.com/oauth2/authorize?client_id=%s&response_type=code&token_access_type=offline',
                $appKey
            );
            $io->newLine();
            $io->writeln(sprintf('<info>%s</info>', $authUrl));
            $io->newLine();
            $io->text('Step 2: Click "Continue" and "Allow". Copy the authorization code shown.');
            $code = $io->ask('Step 3: Paste the authorization code here');
        }

        if (empty($code)) {
            $io->error('Authorization code is required.');
            return Command::FAILURE;
        }

        $code = trim($code);
        $io->text('Exchanging authorization code with Dropbox API...');

        try {
            $response = $this->httpClient->request('POST', 'https://api.dropboxapi.com/oauth2/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($appKey . ':' . $appSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                ],
                'timeout' => 20,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode !== 200) {
                $io->error('Failed to get token from Dropbox: ' . json_encode($data, JSON_PRETTY_PRINT));
                return Command::FAILURE;
            }

            $accessToken = $data['access_token'] ?? null;
            $refreshToken = $data['refresh_token'] ?? null;
            $accountId = $data['account_id'] ?? null;

            if (!$accessToken) {
                $io->error('No access token returned in response.');
                return Command::FAILURE;
            }

            $io->success('Successfully retrieved Dropbox tokens!');
            $io->definitionList(
                ['Account ID' => $accountId ?: 'N/A'],
                ['Access Token' => substr($accessToken, 0, 15) . '... (length: ' . strlen($accessToken) . ')'],
                ['Refresh Token' => $refreshToken ? (substr($refreshToken, 0, 15) . '... (Permanent)') : 'Not returned (already generated before)']
            );

            // Update .env.local automatically
            $envLocalPath = $this->projectDir . '/.env.local';
            if (file_exists($envLocalPath)) {
                $content = file_get_contents($envLocalPath);

                // Update or append DROPBOX_APP_KEY
                if (str_contains($content, 'DROPBOX_APP_KEY=')) {
                    $content = preg_replace('/DROPBOX_APP_KEY=.*/', 'DROPBOX_APP_KEY=' . $appKey, $content);
                } else {
                    $content .= "\nDROPBOX_APP_KEY=" . $appKey;
                }

                // Update or append DROPBOX_APP_SECRET
                if (str_contains($content, 'DROPBOX_APP_SECRET=')) {
                    $content = preg_replace('/DROPBOX_APP_SECRET=.*/', 'DROPBOX_APP_SECRET=' . $appSecret, $content);
                } else {
                    $content .= "\nDROPBOX_APP_SECRET=" . $appSecret;
                }

                // Update or append DROPBOX_ACCESS_TOKEN
                if (str_contains($content, 'DROPBOX_ACCESS_TOKEN=')) {
                    $content = preg_replace('/DROPBOX_ACCESS_TOKEN=.*/', 'DROPBOX_ACCESS_TOKEN=' . $accessToken, $content);
                } else {
                    $content .= "\nDROPBOX_ACCESS_TOKEN=" . $accessToken;
                }

                // Update or append DROPBOX_REFRESH_TOKEN if present
                if ($refreshToken) {
                    if (str_contains($content, 'DROPBOX_REFRESH_TOKEN=')) {
                        $content = preg_replace('/DROPBOX_REFRESH_TOKEN=.*/', 'DROPBOX_REFRESH_TOKEN=' . $refreshToken, $content);
                    } else {
                        $content .= "\nDROPBOX_REFRESH_TOKEN=" . $refreshToken;
                    }
                }

                file_put_contents($envLocalPath, $content);
                $io->success('Updated .env.local file with your new tokens!');
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Exception occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
