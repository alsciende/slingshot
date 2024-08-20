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
use Symfony\Component\Console\Helper\Table;
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
    
    const style = [
        'jsonPath'      => "\033[32m",          // Green
        'apiUrl'        => "\033[33m",          // Yellow
        'method'        => "\033[35m",          // Magenta
        'badCode'       => "\033[38;5;214m",    // Orange
        'okCode'        => "\033[34m",          // Blue
        'sideNote'      => "\033[90m",          // Grey
        'reset'         => "\033[0m",           // White
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
            ->addOption('dump-paths', false, InputOption::VALUE_NONE, 'Displays all the paths before sending them.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Slingshot Command");

        // Get the files location
        $pathToJsonDocument = $input->getArgument('path_to_json_document');
        
        // Get the HTTP Method
        $httpMethod = $input->getArgument('http_method');
        
        // Handle invalid methods
        if (!in_array($httpMethod, self::payloadMethods)) {
            $io->error("$httpMethod : method not supported");
            return Command::INVALID;
        }
        
        // Get the API url and instanciate its Builder
        $apiUrl = $input->getArgument('url_of_api');
        $urlBuilder = new UrlBuilder($apiUrl);

        // Logging path detection section
        $io->section(
            sprintf(
                "[%s] JSON %s: %s",
                strtoupper($httpMethod),
                is_dir($pathToJsonDocument) ? 'Directory' : 'File',
                $pathToJsonDocument,
                $apiUrl,
            )
        );

        // Find all the json documents in case the path is a directory
        $filePaths = $this->jsonFinder->find($pathToJsonDocument);

        // Print every path found in the provided directory if dump-paths is enabled
        if ($input->getOption('dump-paths')) {
            foreach ($filePaths as $filePath) {
                $io->text(["-> $filePath"]);
            }
            $io->newline();
        }
        // Print the total count of elements.
        $io->text(sprintf("%s elements found", count($filePaths)));
        
        // Logging request section
        $io->section(
            sprintf(
                '%s[%s] API template: %s',
                $input->getOption('dry-run') === true ? '(dry-run) ' : '',
                strtoupper($httpMethod),
                $apiUrl,
            )
        );

        // Calculate the largest path for the padding later
        $maxFilePathLength = max(array_map('strlen', $filePaths));

        // Iterate through every file path and giving it an index
        foreach ($filePaths as $i => $filePath) {
            // Read the content of the current file and build the actual Api path with its fields
            $fileContent = $this->jsonReader->read($filePath);
            $apiUrl = $urlBuilder->build($fileContent);

            // Make the request and store its response, unless dry-mode is activated
            $response = '<dry-run>';
            if ($input->getOption('dry-run') !== true) {
                $response = $this->makeOutgoingRequest($fileContent, $httpMethod, $apiUrl);
            }
            // Check if the request was succesful
            $codeColor = $response === '<dry_run>' ? 'sideNote' : ($response > 399 ? 'badCode' : 'okCode'); 

            // Log each iteration
            $paddedIndex = str_pad($i, strlen(count($filePaths)) + 1);
            $pathPadding = $maxFilePathLength - strlen($filePath);
            $paddedPath = $filePath.str_repeat(' ', $pathPadding);
            $paddedApi = $apiUrl.str_repeat(' ', $pathPadding);
            $io->text(
                sprintf(
                    "%s| %s  ==>  [%s] %s  (code %s)",
                    self::style['sideNote'].    '#'.$paddedIndex    .self::style['reset'],
                    self::style['jsonPath'].    $paddedPath         .self::style['reset'],
                    self::style['method'].      $httpMethod         .self::style['reset'],
                    self::style['apiUrl'].      $paddedApi          .self::style['reset'],
                    self::style[$codeColor].    $response           .self::style['reset'],
                )
            );

            // Delete the current file when clean-after is activated
            if ($input->getOption('clean-after') && !unlink($filePath)) {
                $io->error("Failed to delete file '$filePath'");
            }
        }

        // Log and return success
        $io->success("Call successful!");
        return Command::SUCCESS;
    }

    protected function makeOutgoingRequest(
        array $jsonContent,
        string $httpMethod,
        string $apiUrl,
    ): int {

        // Prepare the request options
        $options = ['json' => $jsonContent];
    
        try {
            // Make the HTTP request
            $response = $this->client->request($httpMethod, $apiUrl, $options);
        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            // Handle client/server errors (4xx and 5xx)
            $this->logger->error('HTTP request failed', [
                'url' => $apiUrl,
                'method' => $httpMethod,
                'status_code' => $e->getResponse()->getStatusCode(),
                'response_content' => $e->getResponse()->getContent(false),
            ]);
        } catch (\Exception $e) {
            // Handle any other kind of exceptions
            $this->logger->critical('An unexpected error occurred', [
                'error' => $e->getMessage(),
            ]);
        }
        // Log the successful response
        return $response->getStatusCode();
    }
    
}
