<?php

namespace App\Command;

use App\Service\JsonFinder;
use App\Service\JsonReader;
use App\UrlBuilder;
use phpDocumentor\Reflection\PseudoTypes\LowercaseString;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
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
            ->addArgument('url_of_api', InputArgument::REQUIRED, 'Api URL (ex: https://api.library.org/books/[id])')
            ->addOption('dry-run', false, InputOption::VALUE_NONE, 'Simulates the command instead of running it')
            ->addOption('clean-after', false, InputOption::VALUE_NONE, 'Delete the files after reading them')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title("Slingshot Command");

        $pathToJsonDocument = $input->getArgument('path_to_json_document');
        $httpMethod = $input->getArgument('http_method');
        $apiUrl = $input->getArgument('url_of_api');

        $urlBuilder = new UrlBuilder($apiUrl);

        $io->section(
            sprintf(
                "Getting Json Documents %s %s...",
                strtoupper($httpMethod) === 'PUT' ? 'in' : 'from',
                strtoupper($httpMethod) === 'PUT' ? "$pathToJsonDocument" : "$apiUrl",
            )
        );

        $filePaths = $this->jsonFinder->find($pathToJsonDocument);

        foreach ($filePaths as $filePath) {
            $io->text(["-> $filePath"]);
        }
        $io->newline();
        $io->text(sprintf("(%s elements)", count($filePaths)));

        $this->logger->debug("Found the following JSON paths :", $filePaths);
        
        $io->section(
            sprintf(
                '%s%s Json Documents %s %s',
                $input->getOption('dry-run') === true ? '(not actually) ' : '',
                strtoupper($httpMethod) === 'PUT' ? 'Putting' : 'Getting',
                strtoupper($httpMethod) === 'PUT' ? 'to' : 'in',
                strtoupper($httpMethod) === 'PUT' ? "$apiUrl" : "$pathToJsonDocument",
            )
        );

        if ($input->getOption('dry-run') !== true) {
            foreach ($filePaths as $filePath) {
                $fileContent = $this->jsonReader->read($filePath);
                
                $io->text(
                    sprintf(
                        '%s %s %s %s',
                        strtoupper($httpMethod) === 'PUT' ? 'Sending' : 'Getting',
                        "$filePath",
                        strtoupper($httpMethod) === 'PUT' ? 'to' : 'in',
                        strtoupper($httpMethod) === 'PUT' ? $urlBuilder->build($fileContent) : "$pathToJsonDocument",
                    )
                );
                
                $response = $this->makeRequest($fileContent, $httpMethod, $urlBuilder);
                
                if ($input->getOption('clean-after') && !unlink($filePath)) {
                    $io->error("Failed to delete file '$filePath'");
                }
            }
        }

        $io->success("Call successful!");

        return Command::SUCCESS;
    }

    protected function makeRequest(
        array $jsonContent,
        string $httpMethod,
        UrlBuilder $urlBuilder
    ): ResponseInterface {
        $apiUrl = $urlBuilder->build($jsonContent);

        if (in_array($httpMethod, self::payloadMethods)) {
            $options = ['json' => $jsonContent];
        }
        $response = $this->client->request($httpMethod, $apiUrl, $options);

        $this->logger->debug('File processed', $response->toArray());

        return $response;
    }
}
