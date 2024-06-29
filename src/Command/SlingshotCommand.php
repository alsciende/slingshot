<?php

namespace App\Command;

use App\Service\JsonFinder;
use App\Service\JsonReader;
use App\UrlBuilder;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[AsCommand(
    name: 'slingshot',
    description: 'Uses an HTTP method on a JSON file onto an API.',
)]
class SlingshotCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const payloadMethods = [
        Request::METHOD_POST,
        Request::METHOD_PUT,
        Request::METHOD_PATCH
    ];

    public function __construct(private JsonFinder $jsonFinder,
                                private JsonReader $jsonReader,
                                private HttpClientInterface $client)
    {   
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path_to_json_document', InputArgument::REQUIRED, 'Path to the json document (ex: /opt/data/books/book-1.json)')
            ->addArgument('http_method', InputArgument::REQUIRED, 'HTTP method (ex: [PUT, POST])')
            ->addArgument('url_of_api', InputArgument::REQUIRED, 'api URL (ex: https://api.library.org/books/[id])')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $pathToJsonDocument = $input->getArgument('path_to_json_document');
        $httpMethod = $input->getArgument('http_method');
        $apiUrl = $input->getArgument('url_of_api');

        $urlBuilder = new UrlBuilder($apiUrl);

        $filePaths = $this->jsonFinder->read($pathToJsonDocument);

        $this->logger->debug("Here are the JSON paths", $filePaths);
        
        foreach ($filePaths as $filePath) {
            $fileContent = $this->jsonReader->find($filePath);
            $response = $this->makeRequest($fileContent, $httpMethod, $urlBuilder);   
        }
        
        $io->success(
            sprintf(
                "Call successful!"
            )
        );

        return Command::SUCCESS;
    }

    protected function makeRequest(array $jsonContent, string $httpMethod, UrlBuilder $urlBuilder): ResponseInterface
    {
        $apiUrl = $urlBuilder->build($jsonContent);

        if (in_array($httpMethod, self::payloadMethods)) {
            $options = ['json' => $jsonContent];
        }
        $response = $this->client->request($httpMethod, $apiUrl, $options);

        $this->logger->debug('File processed', $response->toArray());

        return $response;
    }
}
