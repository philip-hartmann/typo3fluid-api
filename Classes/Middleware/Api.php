<?php
namespace PhilipHartmann\TYPO3FluidApi\Middleware;

use Egulias\EmailValidator\Warning\EmailTooLong;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Fluid\View\StandaloneView;

class Api implements MiddlewareInterface
{
    protected $extKey = 'typo3fluid_api';

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Core\Configuration\ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    public function __construct(
        ConfigurationManagerInterface $configurationManager,
        ExtensionConfiguration $extensionConfiguration,
        ResponseFactoryInterface $responseFactory,
        TypoScriptService $typoScriptService
    ) {
        $this->configurationManager = $configurationManager;
        $this->extensionConfiguration = $extensionConfiguration;
        $this->responseFactory = $responseFactory;
        $this->typoScriptService = $typoScriptService;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $apiUrlSegment = rtrim($this->extensionConfiguration->get($this->extKey, 'apiUrlSegment'), '/');

        if (empty($apiUrlSegment)) {
            $apiUrlSegment = '/api/typo3fluid';
        }

        if ($request->getRequestTarget() !== $apiUrlSegment) {
            return $handler->handle($request);
        }

        try {
            $requestBody = json_decode($request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->buildResponse([
                "error" => "JSON ERROR: " . $e->getMessage(),
            ]);
        }

        $apiPassword = $this->extensionConfiguration->get($this->extKey, 'apiPassword');

        if (!empty($apiPassword)
            && (empty($requestBody['password']) || $apiPassword !== $requestBody['password'])
        ) {
            return $this->buildResponse([
                "error" => "Access denied!",
            ]);
        }

        if (empty($requestBody['extension'])) {
            return $this->buildResponse([
                "error" => "Extension name not set!"
            ]);
        }

        if (empty($requestBody['template']) && empty($requestBody['partial'])) {
            return $this->buildResponse([
                "error" => "Template or partial not set!"
            ]);
        }

        return $this->buildResponse([
            "data" => $this->parseTemplate(
                $requestBody['extension'],
                $requestBody['template'],
                $requestBody['partial'],
                !empty($requestBody['section']) ? $requestBody['section'] : null,
                !empty($requestBody['arguments']) ? $requestBody['arguments'] : []
            )
        ]);
    }

    private function parseTemplate(String $extension, $template, $partial, $section, $arguments)
    {
        $setup = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );

        if (empty($setup['plugin.']['tx_' . strtolower($extension) . '.'])) {
            return "Could not find any configuration for the extension key `$extension`.";
        }

        $pluginConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['plugin.']['tx_' . strtolower($extension) . '.']);

        $templatePaths = new TemplatePaths($pluginConfiguration['view']);

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->getRenderingContext()->setTemplatePaths($templatePaths);
        $view->setFormat('html');

        $parsedTemplate = '';

        if (!empty($template)) {
            $view->assignMultiple($arguments);
            $parsedTemplate = $view->render($template);
        } elseif (!empty($partial)) {
            $parsedTemplate = $view->renderPartial($partial, $section, $arguments);
        }

        return $parsedTemplate;
    }

    private function buildResponse($body): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();

        $response = $response->withHeader('Access-Control-Allow-Headers', 'Content-Type');
        $response = $response->withHeader('Access-Control-Allow-Methods', '*');
        $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        $response = $response->withHeader('Content-Type', 'application/json; charset=utf-8');

        $response->getBody()->write(json_encode($body));

        return $response;
    }
}
