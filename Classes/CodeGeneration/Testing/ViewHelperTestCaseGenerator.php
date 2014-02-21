<?php
namespace FluidTYPO3\Builder\CodeGeneration\Testing;

use FluidTYPO3\Builder\CodeGeneration\AbstractClassGenerator;
use FluidTYPO3\Builder\CodeGeneration\ClassGeneratorInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Utility\FrontendSimulatorUtility;
use TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

class ViewHelperTestCaseGenerator
	extends AbstractClassGenerator
	implements ClassGeneratorInterface {

	const TEMPLATE_CLASS = 'ViewHelper/TestCase/Class';
	const TEMPLATE_SUPPORT_PREPARE_INSTANCE = 'ViewHelper/TestCase/Method/PrepareInstanceMethod';
	const TEMPLATE_SUPPORT_INJECT_OBJECTMANAGER = 'ViewHelper/TestCase/Method/InjectObjectManager';
	const TEMPLATE_TEST_CREATE_INSTANCE  = 'ViewHelper/TestCase/Method/CanCreateViewHelper';
	const TEMPLATE_TEST_RENDER  = 'ViewHelper/TestCase/Method/CanRenderViewHelper';
	const TEMPLATE_TEST_INITIALIZE_INSTANCE  = 'ViewHelper/TestCase/Method/CanInitializeViewHelper';
	const TEMPLATE_TEST_PREPARE_ARGUMENTS  = 'ViewHelper/TestCase/Method/CanPrepareArguments';
	const TEMPLATE_TEST_SET_VIEWHELPERNODE  = 'ViewHelper/TestCase/Method/CanSetViewHelperNode';

	/**
	 * @var string
	 */
	protected $viewHelperClassName = NULL;

	/**
	 * @param string $viewHelperClassName
	 * @return void
	 */
	public function setViewHelperClassName($viewHelperClassName) {
		$this->viewHelperClassName = $viewHelperClassName;
	}

	/**
	 * @return string
	 */
	public function generate() {
		if (NULL === $this->viewHelperClassName) {
			return NULL;
		}
		$reflection = new ReflectionClass($this->viewHelperClassName);
		if (TRUE === $reflection->isAbstract()) {
			return NULL;
		}
		$GLOBALS['TSFE']->cObj = $this->objectManager->get('tslib_cObj');
		FrontendSimulatorUtility::simulateFrontendEnvironment($GLOBALS['TSFE']->cObj);
		$GLOBALS['TSFE']->sys_page = $this->objectManager->get('t3lib_PageSelect');
		$GLOBALS['TSFE']->tmpl = $this->objectManager->get('t3lib_TStemplate');
		$this->appendCommonProperties();
		$this->appendCommonTestMethods();
		$rendered = $this->renderClass(self::TEMPLATE_CLASS, $this->viewHelperClassName . 'Test');
		FrontendSimulatorUtility::resetFrontendEnvironment();
		return $rendered;
	}

	/**
	 * @return void
	 */
	protected function appendCommonProperties() {
		$this->appendProperty('objectManager', 'Tx_Extbase_Object_ObjectManagerInterface');
	}

	/**
	 * @return void
	 */
	protected function appendRenderMethodTestIfPossible() {
		$viewHelperInstance = $this->getPreparedViewHelperInstance();
		$hasRequiredArguments = $this->classAnalysisService->assertClassMethodHasRequiredArguments($viewHelperInstance, 'render');
		if (FALSE === $hasRequiredArguments) {
			try {
				$viewHelperInstance->render();
				$this->appendMethodFromSourceTemplate(self::TEMPLATE_TEST_RENDER, array('class' => $this->viewHelperClassName));
			} catch (Exception $error) {

			}
		}
	}

	/**
	 * @return void
	 */
	protected function appendCommonTestMethods() {
		$nodeClassName = (FALSE !== strpos($this->viewHelperClassName, '_') ? 'Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode' : '\\TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode');
		$variables = array(
			'class' => $this->viewHelperClassName,
			'nodeclass' => $nodeClassName,
		);
		$this->appendMethodFromSourceTemplate(self::TEMPLATE_SUPPORT_INJECT_OBJECTMANAGER, $variables);
		$this->appendMethodFromSourceTemplate(self::TEMPLATE_SUPPORT_PREPARE_INSTANCE, $variables);
		$this->appendMethodFromSourceTemplate(self::TEMPLATE_TEST_CREATE_INSTANCE, $variables);
		$this->appendMethodFromSourceTemplate(self::TEMPLATE_TEST_INITIALIZE_INSTANCE, $variables);
		$this->appendMethodFromSourceTemplate(self::TEMPLATE_TEST_PREPARE_ARGUMENTS, $variables);
		$this->appendMethodFromSourceTemplate(self::TEMPLATE_TEST_SET_VIEWHELPERNODE, $variables);
		$this->appendRenderMethodTestIfPossible();
	}

	/**
	 * @param array $arguments
	 * @return Tx_Fluid_Core_ViewHelper_AbstractViewHelper
	 */
	protected function getPreparedViewHelperInstance($arguments = array()) {
		$nodeClassName = (FALSE !== strpos($this->viewHelperClassName, '_') ? 'Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode' : '\\TYPO3\\CMS\\Fluid\\Core\\Parser\\SyntaxTree\\ViewHelperNode');
		$renderingContextClassName = (FALSE !== strpos($this->viewHelperClassName, '_') ? 'Tx_Fluid_Core_Rendering_RenderingContext' : '\\TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContext');
		$controllerContextClassName = (FALSE !== strpos($this->viewHelperClassName, '_') ? 'Tx_Extbase_MVC_Controller_ControllerContext' : '\\TYPO3\\CMS\\Extbase\\MVC\\Controller\\ControllerContext');
		$requestClassName = (FALSE !== strpos($this->viewHelperClassName, '_') ? 'Tx_Extbase_MVC_Web_Request' : '\\TYPO3\\CMS\\Extbase\\MVC\\Web\\Request');

		/** @var Request $request */
		$request = $this->objectManager->get($requestClassName);
		/** @var $viewHelperInstance AbstractViewHelper */
		$viewHelperInstance = $this->objectManager->get($this->viewHelperClassName);
		/** @var ViewHelperNode $node */
		$node = $this->objectManager->get($nodeClassName, $viewHelperInstance, $arguments);
		/** @var ControllerContext $controllerContext */
		$controllerContext = $this->objectManager->get($controllerContextClassName);
		$controllerContext->setRequest($request);
		/** @var RenderingContext $renderingContext */
		$renderingContext = $this->objectManager->get($renderingContextClassName);
		$renderingContext->setControllerContext($controllerContext);

		$viewHelperInstance->setRenderingContext($renderingContext);
		$viewHelperInstance->setViewHelperNode($node);
		return $viewHelperInstance;
	}

}
